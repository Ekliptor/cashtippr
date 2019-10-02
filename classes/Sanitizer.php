<?php
class CTIP_Sanitizer {
	protected $adminNotices = array();
	
	public function __construct() {
		//$this->tpl = $tpl;
	}
	
	/**
	 * Get a sanitizer function (as PHP callable) based on the type of the default value.
	 * Sanitizers validate the input and can print admin notices on error.
	 * @param mixed $defaultValue
	 * @return callable
	 */
	public function sanitizeByType($defaultValue) {
				
		switch (gettype($defaultValue))
		{
			case 'boolean':			return array($this, 'formatBoolean');
			case 'integer':			return array($this, 'formatInteger');
			case 'double':			return array($this, 'formatFloat'); // float
			case 'string':			return array($this, 'formatString');
		}
		return array($this, 'formatUnknown'); // shouldn't be reached since array, object,... can not be passed in via HTML forms
	}
	
	public function addAdminNotices(CTIP_AdminNotice $notice) {
		$this->adminNotices[] = $notice;
		static $addedRedirect = false;
		if ($addedRedirect === true)
			return;
		$addedRedirect = true;
		
		// admin notices have to be shown after options.php redirects back to admin.php
		// so we cache them in query args
		//add_filter('redirect_post_location', array( $this, 'addAdminNoticeQueryVar' ), 99); // use the more general hook for posts + settings
		add_filter('wp_redirect', array( $this, 'addAdminNoticeQueryVar' ), 99);
	}
	
	public function addAdminNoticeQueryVar(string $location, int $status = 302) {
		remove_filter( 'wp_redirect', array( $this, 'addAdminNoticeQueryVar' ), 99 );
		$notices = array();
		foreach ($this->adminNotices as $notice) {
			$notices[] = $notice->urlEncode();
		}
		$noticeQuery = implode(',', $notices); // comma is not part of bas64 encoding
		return add_query_arg( array( 'notices' => $noticeQuery ), $location );
	}
	
	/**
	 * Format a boolean input.
	 * Note: When using checkboxes the value will not be present in form data if unchecked.
	 * @param bool|string $newValue
	 * @param string $settingName
	 * @return bool
	 */
	public function formatBoolean($newValue, string $settingName): bool {
		if ($newValue === true || $newValue === false)
			return $newValue;
		$newValue = strtolower(sanitize_text_field($newValue));
		return $newValue === 'true' || $newValue === '1';
	}
	
	public function formatInteger($newValue, string $settingName): int {
		return (int)sanitize_text_field($newValue);
	}
	
	public function formatFloat($newValue, string $settingName): float {
		return (float)sanitize_text_field($newValue);
	}
	
	public function formatString($newValue, string $settingName): string {
		return sanitize_text_field($newValue);
	}
	
	public function formatStringMultiLine($newValue, string $settingName): string {
		return sanitize_textarea_field($newValue);
	}
		
	public function formatUnknown($newValue, string $settingName) {
		return sanitize_text_field($newValue);
	}
	
	/**
	 * Sanitize a user input of html via text/textarea input. This will also keep <script> tags.
	 * @param string $html
	 * @return string
	 */
	public function sanitizeHtml(string $html): string {
		// from _sanitize_text_fields()
		$filtered = wp_check_invalid_utf8( $html );

		$filtered = trim( $filtered );
	
		$found = false;
		while ( preg_match('/%[a-f0-9]{2}/i', $filtered, $match) ) {
			$filtered = str_replace($match[0], '', $filtered);
			$found = true;
		}
	
		if ( $found ) {
			// Strip out the whitespace that may now exist after removing the octets.
			$filtered = trim( preg_replace('/ +/', ' ', $filtered) );
		}
	
		return $filtered;
	}
	
	public function isValidBitcoinCashAddress(string $address): bool {
		return strlen($address) >= 25; // TODO improve
	}
}
?>