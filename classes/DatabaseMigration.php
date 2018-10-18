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
	
	public function migrate(): bool {
		$queries = array();
		switch ($this->lastVersion) {
			// add migration queries in order from oldest version to newest
			case '1':
				// longest BCH address string is 54? bitcoincash:qp76l5qeztrmjxpas35wgznn3zxdj7e89qcj2v6mlq
				// but allow 64 chars to be safe
				$table = \Cashtippr::getTableName('transactions');
				$queries[] = "ALTER TABLE `$table` ADD `address` VARCHAR(64) NOT NULL AFTER `txid`";
				$queries[] = "ALTER TABLE `$table` ADD `amount` FLOAT NOT NULL AFTER `address`";
		}
		if (empty($queries))
			return true; // say successful
		return $this->runQueries($queries);
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
}
?>