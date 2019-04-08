<?php
namespace Ekliptor\Cashtippr;

class DatabaseMigration {
	/** @var string */
	protected $lastVersion;
	/** @var string */
	protected $currentVersion;
	/** @var array */
	protected $lastError = array();
	
	public function __construct(string $lastVersion, string $currentVersion) {
		if (!$lastVersion)
			$lastVersion = '1';
		$this->lastVersion = $lastVersion;
		$this->currentVersion = $currentVersion;
	}
	
	public static function checkAndMigrate() {
		add_action('plugins_loaded', function() {
			$lastVersion = get_option('cashtippr_version');
			if ($lastVersion === CASHTIPPR_VERSION)
				return;
			$migrate = new DatabaseMigration($lastVersion, CASHTIPPR_VERSION);
			if ($migrate->ensureLatestVersion() === false) { // TODO can be switched to migrate() call just like in plugin_activcation()
				\Cashtippr::notifyErrorExt("Error ensuring latest DB version on migration", $migrate->getLastError());
				return;
			}
			update_option ( 'cashtippr_version', CASHTIPPR_VERSION );
		});
	}
	
	public function migrate(): bool {
		$queries = array();
		// TODO also add crons here if we add more later
		switch ($this->lastVersion) {
			// add migration queries in order from oldest version to newest
			case '1':
				// longest BCH address string is 54? bitcoincash:qp76l5qeztrmjxpas35wgznn3zxdj7e89qcj2v6mlq
				// but allow 64 chars to be safe
				$table = \Cashtippr::getTableName('transactions');
				if ($this->columnExists($table, 'address') === false) {
					$queries[] = "ALTER TABLE `$table` ADD `address` VARCHAR(64) NOT NULL AFTER `txid`";
					$queries[] = "ALTER TABLE `$table` ADD `amount` FLOAT NOT NULL AFTER `address`";
				}
		}
		if (empty($queries))
			return true; // say successful
		return $this->runQueries($queries);
	}
	
	/**
	 * Fix function if migrate() didn't work on some instances previously.
	 */
	public function ensureLatestVersion(): bool {
		$table = \Cashtippr::getTableName('transactions');
		if ($this->columnExists($table, 'amount') === false) {
			$this->lastVersion = '1';
			return $this->migrate();
		}
		return true;
	}
	
	public function getLastError(): array {
		return $this->lastError;
	}
	
	protected function runQueries(array $queries): bool {
		global $wpdb;
		foreach ($queries as $query) {
			$result = $wpdb->query($query);
			if ($result === false) {
				$this->lastError = array(
						'query' => $query,
						'error' => $wpdb->last_error
				);
				return false; // abort
			}
		}
		return true;
	}
	
	protected function columnExists(string $table, string $column) {
		global $wpdb;
		$rows = $wpdb->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
		return empty($rows) ? false : true;
	}
}
?>