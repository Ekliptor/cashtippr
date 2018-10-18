<?php
/**
 * A class storing sessions in the Wordpress MySQL database.
 * TODO add serializer library to store session data in BLOB for better performance (instead of strings php serialize function)
 * 		and to allow saving all data of binary string sessions (such as the null byte).
 */
class CTIP_MysqlWpSessionHandler extends CTIP_AbstractSessionHandler {
	const REFRESH_SESSION_ACTIVE_SEC = 300;
	const STORE_EMPTY_SESSIONS = false;
	
	/** @var string The table to store session data in. */
	protected $table;
	/** @var int How often to force writing (unchanged) session data. */
	protected $refreshSessionSec = 0;
	/** @var bool Store empty sessions to DB. */
	protected $storeEmptySessions = false;
	
	/** @var string The session data as it was read. Used to save DB write ops. */
	protected $lastReadDataHash = '';
	/** @var int The unix timestamp when the session datas was last written to DB. */
	protected $lastActiveSec = 0;
	/** @var bool Flag whether garbage collection should run on session close. */
	protected $gcCalled = false;
	
	// we could store more data as user IPs for analytics. but doesn't really belong in session class
	
	/**
	 * Register a session handler using the Wordpress database connection $wpdb.
	 * @param string $table The table name to store sessions in.
	 * @param array $options
	 * 		int refreshSessionSec: How often to force writing unchanged data back to DB. Use 0 to always write them to DB.
	 * 		bool storeEmptySessions: Write new empty sessions to DB or discard them to save DB write ops.
	 * 			WARNING: This might cause lost session data writes on web apps running many php scripts in parallel per user (only if all session data gets
	 * 						removed while keeping the session).
	 * @return MysqlWpSessionHandler
	 */
	public static function register(string $table, $options = array()) {
		return new self($table, $options);
	}
	
	/**
	 * Creates the table to store sessions in. Call this during website installation.
	 * @param string $table The table nam
	 * @return bool true if the table has been created, false otherwise
	 */
	public static function createTable(string $table): bool {
		global $wpdb;
		if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table)
			return true; // table already exists
		$success = $wpdb->query("CREATE TABLE `$table` (
				  `id` varchar(40) NOT NULL,
				  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  `last_active` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				  `user_id` bigint(20) NOT NULL DEFAULT '0',
				  `data` text NOT NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;") !== false;
		if ($success === false)
			return false;
		$success = $wpdb->query("ALTER TABLE `$table`
				  ADD PRIMARY KEY (`id`),
				  ADD KEY `last_active` (`last_active`);") !== false;
		return $success === true;
	}
	
	private function __construct(string $table, $options = array()) {
		$this->table = $table;
		$this->refreshSessionSec = isset($options['refreshSessionSec']) ? $options['refreshSessionSec'] : static::REFRESH_SESSION_ACTIVE_SEC;
		$this->storeEmptySessions = isset($options['storeEmptySessions']) ? $options['storeEmptySessions'] : static::STORE_EMPTY_SESSIONS;
		session_set_save_handler($this, true);
	}
	
	public function open($savePath, $sessionName) {
		return true; // $wpdb will always be connected or the script will bail
	}
	
	public function close() {
		global $wpdb;
		if ($this->gcCalled === true) {
			$this->gcCalled = false;
			$maxlifetime = $this->getMaxLifetime(); // overwrite value to ensure min session lifetime (forced low values on certain shared hosts)
			$query = $wpdb->prepare("DELETE FROM {$this->table} WHERE last_active < %s", array('last_active' => date('Y-m-d H:i:s', time() - $maxlifetime)));
			$wpdb->query($query);
		}
		return true;
	}
	
	public function read($sessionId) {
		global $wpdb;
		$query = $wpdb->prepare("SELECT UNIX_TIMESTAMP(last_active) AS lastActiveSec, data FROM {$this->table} WHERE id = %s", array('id' => $sessionId));
		$row = $wpdb->get_row($query);
		if (empty($row))
			return '';
		if ($this->refreshSessionSec !== 0)
			$this->lastReadDataHash =$this->getDataHash($row->data);
		$this->lastActiveSec = $row->lastActiveSec;
		return $row->data;
	}
	
	public function write($sessionId, $data) { // data is already a string
		// we could write a username/id too for registered users..
		global $wpdb;
		if ($this->storeEmptySessions === false && $data === '')
			return true;
		if ($this->refreshSessionSec !== 0 && $this->lastReadDataHash === $this->getDataHash($data) && $this->lastActiveSec + $this->refreshSessionSec < time())
			return true; // write is called even if the session data didn't change. save DB write ops
		$numRows = $wpdb->replace($this->table, array(
				'id' => $sessionId,
				'last_active' => date('Y-m-d H:i:s'),
				'user_id' => get_current_user_id(),
				'data' => $data
		));
		return $numRows > 0; // 1 for new session, 2 for recurring session
	}
	
	public function destroy($sessionId) {
		global $wpdb;
		$wpdb->delete($this->table, array('id' => $sessionId)); // === 1, but swallow possible errors
		return true;
	}
	
	public function gc($maxlifetime) {
		// from Laravel:
		// We delay gc() to close() so that it is executed outside the transactional and blocking read-write process.
        // This way, pruning expired sessions does not block them from being started while the current session is used.
        $this->gcCalled = true;
		return true;
	}
	
	protected function getDataHash($sessionData) {
		if ($sessionData === '')
			return '';
		return hash('sha512', $sessionData);
	}
}
?>