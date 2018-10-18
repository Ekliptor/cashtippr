<?php
abstract class CTIP_AbstractSessionHandler implements SessionHandlerInterface {
	const DEFAULT_MAX_LIFETIME_SEC = 86400; // default PHP session lifetime
	
	protected function getMaxLifetime() {
		return max((int)ini_get("session.gc_maxlifetime"), static::DEFAULT_MAX_LIFETIME_SEC); // some shared hosters enforce short times
	}
}
?>