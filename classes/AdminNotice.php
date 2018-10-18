<?php
class CTIP_AdminNotice {
	/** @var string */
	public $noticeHtml;
	/** @var string */
	public $noticeLevel;
	/** @var bool */
	public $dismissible;
	/** @var bool */
	public $echo;
	
	/**
     * Creates a notice for the top in the admin panel.
     * @param string $noticeHtml The HTML to show. Should be wrapped in p tags.
     * @param string $noticeLevel The level (color) of the error: error, warning, success, or info
     * @param boolean $dismissible True if the error message can be closed by the user.
     * @param boolean $echo True to echo the message directly, false to return the string.
     */
	public function __construct(string $noticeHtml, string $noticeLevel, $dismissible = true, $echo = true) {
		$this->noticeHtml = $noticeHtml;
		$this->noticeLevel = $noticeLevel;
		$this->dismissible = $dismissible;
		$this->echo = $echo;
	}
	
	public function print() {
		$dismissibleClass = $this->dismissible === true ? ' is-dismissible' : '';
		$html = sprintf('<div class="notice notice-%s%s">
	          %s
	         </div>', $this->noticeLevel, $dismissibleClass, $this->noticeHtml);
		if ($this->echo === false)
			return $html;
		echo $html;
	}
	
	public function urlEncode() {
		return static::base64UrlEncode(gzencode(json_encode($this)));
	}
	
	public static function urlDecode(string $str) {
		// using PHP unserialize() with user data is not safe!
		$stdClassObject = json_decode(gzdecode(static::base64UrlDecode($str)));
		return new CTIP_AdminNotice($stdClassObject->noticeHtml, $stdClassObject->noticeLevel, $stdClassObject->dismissible, $stdClassObject->echo);
	}
	
	public static function base64UrlEncode($data) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_'), '=');
	}
	
	public static function base64UrlDecode($data) {
		return base64_decode( strtr( $data, '-_', '+/') . str_repeat('=', 3 - ( 3 + strlen( $data )) % 4 ));
	}
}
?>