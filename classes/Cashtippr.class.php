<?php
use Ekliptor\Cashtippr\DatabaseMigration;
use Ekliptor\CashP\BlockchainApi\AbstractBlockchainApi;
use Ekliptor\CashP\BlockchainApi\Http\WordpressHttpAgent;

require_once CASHTIPPR__PLUGIN_DIR . 'classes/autoload.php';
require_once CASHTIPPR__PLUGIN_DIR . 'classes/Settings.class.php';
require_once CASHTIPPR__PLUGIN_DIR . 'classes/Sanitizer.php';
// TODO move some classes to Admin class if we really don't need them here
require_once CASHTIPPR__PLUGIN_DIR . 'classes/TemplateEngine.class.php';
require_once CASHTIPPR__PLUGIN_DIR . 'classes/ButtonTemplates.php';
require_once CASHTIPPR__PLUGIN_DIR . 'classes/Session/AbstractSessionHandler.php';
require_once CASHTIPPR__PLUGIN_DIR . 'classes/Session/MemcachedSessionHandler.php';
require_once CASHTIPPR__PLUGIN_DIR . 'classes/Session/MysqlWpSessionHandler.php';

require_once CASHTIPPR__PLUGIN_DIR . 'classes/AdminNotice.php';

class Cashtippr {
	const DEBUG = true;
	//const SESSION_NAME = "ctip";
	const SESSION_LIFETIME_SEC = 365 * DAY_IN_SECONDS; // TODO validate max input for membership lenght
	const CLEANUP_TRANSACTIONS_H = 25;
	const CONSENT_COOKIE_NAME = 'ct-ck';
	const BCH_TX_FEE = array(
			'USD' => 0.021 // use a higher value
	);
	const BCH_TX_FEE_TOLERANCE = 0.00075;
	const BCH_DUST_LIMIT_SAT = 546; // https://github.com/Bitcoin-ABC/bitcoin-abc/blob/9bbaacc1e0d86a7a18352068b93ca0ccab45f2ea/src/test/transaction_tests.cpp#L546
	const BADGER_WALLET_JS = "https://developer.bitcoin.com/badger/badgerButton-1.0.1.js";
	const MAX_AGE_TX_ACCEPT = 15*10; // max 15 blocks older
	
	/** @var Cashtippr */
	private static $instance = null;
	private static $prefix = "ct_";
	private static $cron_events = array (
			'cleanup_transactions'
		);
	private static $cron_events_hourly = array(
			'update_currency_rates'
		);
	
	/** @var CTIP_Settings */
	protected $settings;
	/** @var ButtonTemplates */
	protected $buttonTpl;
	/** @var array URL parts of this WP site as the result of: parse_url(site_url('/')) */
	protected $siteUrlParts = null;
	/** @var CTIP_Sanitizer */
	protected $sanitizer = null;
	
	/** @var int */
	protected $showingTipButtons = 0;
	
	/** @var WP_Post A reference to the post/page currently showing. */
	protected $post = null;
	
	/** @var int */
	protected $postWordLen = 0;
	/** @var int */
	protected $hiddenWords = 0;
	/** @var bool */
	protected $includedMoneybuttonScript = false;
	/** @var string */
	protected $qrcodeStatic;
	/** @var string */
	protected $closeImage;
	/** @var string */
	protected $loadingImage;
	
	private function __construct() {
		$this->qrcodeStatic = plugins_url( 'img/qrcode.svg', CASHTIPPR__PLUGIN_DIR . 'cashtippr.php' );
		$this->closeImage = plugins_url( 'img/close50.png', CASHTIPPR__PLUGIN_DIR . 'cashtippr.php' );
		$this->loadingImage = plugins_url( 'img/loading.gif', CASHTIPPR__PLUGIN_DIR . 'cashtippr.php' );
	}
	
	public static function getInstance() {
		if (self::$instance === null)
			self::$instance = new self ();
		return self::$instance;
	}
	
	public static function getTableName($tableName) {
		global $wpdb;
		return $wpdb->prefix . self::$prefix . $tableName;
	}
	
	public function init() {
		$this->siteUrlParts = parse_url(site_url('/'));
		//if (CashtipprAdmin::adminLoaded() === true) // if we are not on an admin page we don't include the source
		if (class_exists('CashtipprAdmin', false) === true) // must be delayed until init call so that source is ready
			$this->settings = new CTIP_Settings($this, CashtipprAdmin::getInstance($this));
		else
			$this->settings = new CTIP_Settings($this);
		$this->buttonTpl = new ButtonTemplates($this);
		$this->sanitizer = new CTIP_Sanitizer();
		
		// init hooks
		// note that functions must be public because they are called from event stack
		add_action('init', array(self::$instance, 'startSession'), 11);
		add_shortcode('tippr_button', array(self::$instance, 'showTipprButton'));
		add_shortcode('tippr_hide', array(self::$instance, 'showTipprButton'));
		
		add_action( 'wp_enqueue_scripts', array (self::$instance, 'addPluginScripts' ) );
		add_action( 'the_post', array(self::$instance, 'getPost') );
		add_filter( 'the_content', array(self::$instance, 'restrictPostText') );
		add_action( 'wp_footer', array(self::$instance, 'addFooterCode') );
		
		// Crons
		add_action ( 'cleanup_transactions', array (self::$instance, 'cleanupTransactions' ) );
		add_action ( 'update_currency_rates', array (self::$instance, 'updateCurrencyRates' ) );
		add_action ( 'ct_unused_address_search', array (self::$instance, 'searchUnusedAddress' ) );
		
		if ($this->settings->get('rate_usd_bch') === 0.0)
			add_action ( 'shutdown', array ($this, 'updateCurrencyRates' ) );
		
		//add_action( 'upgrader_process_complete', array (self::$instance, 'onUpgrade' ), 10, 2);	
	}
	
	public static function plugin_activation() {
		if (version_compare ( $GLOBALS ['wp_version'], CASHTIPPR__MINIMUM_WP_VERSION, '<' )) {
			load_plugin_textdomain ( 'ekliptor' );
			$message = '<strong>' . sprintf ( esc_html__ ( 'Cashtippr plugin %s requires WordPress %s or higher.', 'ekliptor' ), CASHTIPPR_VERSION, CASHTIPPR__MINIMUM_WP_VERSION ) . '</strong> ' . sprintf ( __ ( 'Please <a href="%1$s">upgrade WordPress</a> to a current version.', 'ekliptor' ), 'https://codex.wordpress.org/Upgrading_WordPress' );
			static::bailOnActivation ( $message, false );
		}
		
		$sessionTableSuccess = CTIP_MysqlWpSessionHandler::createTable(static::getTableName('sessions'));
		$transactionsTableSuccess = static::createTransactionsTable();
		if ($sessionTableSuccess === false || $transactionsTableSuccess === false) {
			load_plugin_textdomain ( 'ekliptor' );
			$message = '<strong>' . esc_html__ ( 'Error creating required MySQL tables.', 'ekliptor' ) . '</strong> ' . sprintf ( __ ( 'Please ensure that you have sufficient database privileges. If you still encounter this problem afterwards, please <a href="%1$s">file a bug report</a>.', 'ekliptor' ), 'https://cashtippr.com/' );
			static::bailOnActivation ( $message, false );
		}
		$tables = get_option('cashtippr_tables', array());
		if (in_array(static::getTableName('sessions'), $tables) === false)
			$tables[] = static::getTableName('sessions');
		if (in_array(static::getTableName('transactions'), $tables) === false)
			$tables[] = static::getTableName('transactions');
		update_option('cashtippr_tables', $tables);
		
		// ensure directories exist
		$dataDirs = array(
				CASHTIPPR__PLUGIN_DIR . 'data',
				CASHTIPPR__PLUGIN_DIR . 'data/temp',
				CASHTIPPR__PLUGIN_DIR . 'data/temp/qr'
		);
		foreach ($dataDirs as $dir) {
			if (file_exists($dir) === true)
				continue;
			if (mkdir($dir) === false) { // TODO even though we don't create php files, using WP filesystem API would still be better
				load_plugin_textdomain ( 'ekliptor' );
				$message = '<strong>' . esc_html__ ( 'Error creating data folder.', 'ekliptor' ) . '</strong> ' . sprintf ( __ ( 'Please ensure that your WordPress installation has write permissions on the /plugins folder (0755) or create this folder manually with permissions 0755: %s', 'ekliptor' ), $dir );
				static::bailOnActivation ( $message, false );
			}
		}
		
		foreach ( self::$cron_events as $cron_event ) {
			$timestamp = wp_next_scheduled ( $cron_event );
			if (!$timestamp)
				wp_schedule_event(time(), 'daily', $cron_event);
		}
		foreach ( self::$cron_events_hourly as $cron_event ) {
			$timestamp = wp_next_scheduled ( $cron_event );
			if (!$timestamp)
				wp_schedule_event(time(), 'hourly', $cron_event);
		}
		
		if (!get_option ( 'cashtippr_memcached_secret' ))
			update_option ( 'cashtippr_memcached_secret', static::getRandomString(12) ); // a prefix to avoid cache key collisions with other apps
		if (!get_option ( 'cashtippr_is_installed' )) {
			//update_option ( 'cashtippr_seed', static::getRandomString(40) ); // our transaction IDs are unique random strings. not needed for now
		}
		$lastVersion = get_option ( 'cashtippr_version' );
		if ($lastVersion === false) { // we didn't store the full version then
			$migrate = new DatabaseMigration($lastVersion, CASHTIPPR_VERSION);
			if ($migrate->migrate() === false)
				static::notifyErrorExt('Error migrating database', $migrate->getLastError());
		}
		update_option ( 'cashtippr_is_installed', '1' );
		update_option ( 'cashtippr_version', CASHTIPPR_VERSION );
	}
	
	public static function plugin_deactivation() {
		//global $wpdb;
		// Remove any scheduled cron jobs.
		$events = array_merge(self::$cron_events, self::$cron_events_hourly);
		foreach ( $events as $cron_event ) {
			$timestamp = wp_next_scheduled ( $cron_event );
			if ($timestamp) {
				wp_unschedule_event ( $timestamp, $cron_event );
			}
		}
		
		// tables are only being dropped on uninstall. also cashtippr_settings
		
		delete_option('cashtippr_is_installed');
		//delete_option('cashtippr_seed');
		//delete_option('cashtippr_memcached_secret'); // deleted on uninstall
	}
	
	public function startSession() {
		// easy way: always start a session. otherwise we have to know if there is a tip button on the page before sending HTTP headers
		// TODO write memcached session data to mysql by cron to minimize the risk of lost data
		// TODO copy data between memcached + mysql on settings change
		// TODO support for multiple sessions (if another WP plugin uses PHP sessions too): add setting to
		// always open & close session on every WP hook using sessing data (slower performance). or just drop PHP sessions for our own implementation
		
		if ($this->settings->get('use_memcached') === true) {
			$memcachedServer = array($this->settings->get('memcached_host'), $this->settings->get('memcached_port'));
			CTIP_MemcachedSessionHandler::registerFromServers(array($memcachedServer), array(
					'prefix' => get_option('cashtippr_memcached_secret'),
					'expirationSec' => static::SESSION_LIFETIME_SEC
			));
		}
		else
			CTIP_MysqlWpSessionHandler::register(static::getTableName('sessions'));
		session_name($this->settings->get('session_name'));
		//$url = site_url();
		//$urlParts = parse_url($url);
		//if (!static::DEBUG)
		//if ($urlParts !== false && $urlParts['host'] !== "localhost")
			//session_set_cookie_params(static::SESSION_LIFETIME_SEC, '/', '.' . $urlParts['host']); // include subdomains
		session_set_cookie_params(static::SESSION_LIFETIME_SEC, $this->siteUrlParts['path'], null, false, true);	
		// here we could use session_id() to set a previous session ID (provided by req params)
		session_start();
	}
	
	/**
	 * Gets get number of posts a user as viewed.
	 * @return int
	 */
	public function getPostViewCount(): int {
		return isset($_SESSION['postViews']) ? $_SESSION['postViews'] : 0;
	}
	
	/**
	 * Gets the total amount of tips a user has given (in button currency from settings).
	 * @param int $postID If profided only return the amount the user has tipped for this specific post
	 * @return float
	 */
	public function getTipAmount(int $postID = 0): float {
		if ($postID === 0)
			return isset($_SESSION['tips']) ? $_SESSION['tips'] : 0.0;
		return isset($_SESSION['tipped']) && isset($_SESSION['tipped'][$postID]) ? $_SESSION['tipped'][$postID] : 0.0;
	}
	
	/**
	 * Adds a tip amount for the current user.
	 * @param float $amount
	 */
	public function addTipAmount(float $amount) {
		if (!isset($_SESSION['tips']))
			$_SESSION['tips'] = 0.0;
		$_SESSION['tips'] += $amount;
	}
	
	/**
	 * Checks if a user has sent a tip for a given post.
	 * @param int $postID
	 * @param float $minAmount consider calling getMinTipAmount() to deduct TX fees before checking in this function
	 * @return bool
	 */
	public function isTippedPost(int $postID, float $minAmount = 0.00000001): bool {
		return isset($_SESSION['tipped']) && isset($_SESSION['tipped'][$postID]) && $_SESSION['tipped'][$postID] > $minAmount;
	}
	
	/**
	 * Adds a tip for the provided post of the current user.
	 * @param int $postID
	 * @param float $amount
	 */
	public function addTippedPost(int $postID, float $amount) {
		if (!isset($_SESSION['tipped']))
			$_SESSION['tipped'] = array();
		if (!isset($_SESSION['tipped'][$postID]))
			$_SESSION['tipped'][$postID] = 0.0;
		$_SESSION['tipped'][$postID] += $amount;
	}
	
	public function hasFullAccessPass(): bool {
		return isset($_SESSION['accessPass']) && $_SESSION['accessPass'] - time() > 0;
	}
	
	/**
	 * Give a user a full access pass to all content. If he already has one, the time will be added.
	 * @param int $days
	 */
	public function addFullAccessPass(int $days) {
		if (!isset($_SESSION['accessPass']))
			$_SESSION['accessPass'] = time() + DAY_IN_SECONDS*$days;
		else {
			$remainingTimeSec = $_SESSION['accessPass'] - time();
			$_SESSION['accessPass'] = time() + DAY_IN_SECONDS*$days;
			if ($remainingTimeSec > 0)
				$_SESSION['accessPass'] += $remainingTimeSec;
		}
	}
	
	public function reachedDonationGoal(int $postID): bool {
		if ($this->settings->get('enable_donation_goal') === false)
			return false;
		$tipForPost = (float)get_post_meta($postID, 'tipAmount', true);
		return $tipForPost >= $this->settings->get('donation_goal');
	}
	
	/**
	 * Gets the min amount that must be received, taking are of TX fees.
	 * @param float $amount
	 * @return float
	 */
	public function getMinTipAmount(float $amountRequested): float {
		if ($this->settings->get('button_currency') !== 'USD') // TODO conversions if not USD
			return $amountRequested*0.98;
		// TODO if the user changes the currency setting we should store the requested currency at the time of the tip
		return max(0.0, $amountRequested - static::BCH_TX_FEE['USD']);
	}
	
	public function incShowingTipButtons() {
		$this->showingTipButtons++;
	}
	
	public function getIncludedMoneybuttonScript() {
		return $this->includedMoneybuttonScript;
	}
	
	public function setIncludedMoneybuttonScript(bool $included) {
		$this->includedMoneybuttonScript = $included;
	}
	
	/**
	 * Generate a QR code for the payment
	 * @param string $txid The internal MySQL transaction ID (not the on-chain TXID).
	 * @param string $address The (1-time) BCH address created for this payment.
	 * @param float $amount The amount to be received in display currency (USD).
	 * @param float $amountBCH (optional) The amount in BCH to use. Use this for remaining amounts.
	 * @return string the public image URL of the QR code
	 */
	public function generateQrCodeForAddress(string $txid, string $address, float $amount, float $amountBCH = 0.0): string {
		$qrHash = hash('sha512', $txid);
		$fileName = sprintf('data/temp/qr/%s.png', $qrHash);
		$fileLocal = CASHTIPPR__PLUGIN_DIR . $fileName;
		$fileUrl =  plugins_url( $fileName, CASHTIPPR__PLUGIN_DIR . 'slp-payments.php' );
		if (file_exists($fileLocal) === true)
			return $fileUrl; // use it from cache
		
		if ($amountBCH === 0.0)
			$amountBCH = $this->toAmountBCH($amount, $this->settings->get('button_currency'));
		$codeContents = $this->createPaymentURI($address, $amountBCH);
		\QR_Code\QR_Code::png($codeContents, $fileLocal);
		return $fileUrl;
	}
	
	/**
	 * Generate a QR code for the payment
	 * @param string $txid The internal MySQL transaction ID (not the on-chain TXID).
	 * @return string the public image URL of the QR code
	 */
	public function generateQrCode(string $txid): string {
		global $wpdb;
		$qrHash = hash('sha512', $txid);
		$fileName = sprintf('data/temp/qr/%s.png', $qrHash);
		$fileLocal = CASHTIPPR__PLUGIN_DIR . $fileName;
		$fileUrl =  plugins_url( $fileName, CASHTIPPR__PLUGIN_DIR . 'slp-payments.php' );
		if (file_exists($fileLocal) === true)
			return $fileUrl; // use it from cache
		
		$table = Cashtippr::getTableName('transactions');
		$query = $wpdb->prepare("SELECT address, amount FROM $table WHERE txid = '%s'", array($txid));
		$row = $wpdb->get_row($query);
		if (empty($row))
			return '';
		return $this->generateQrCodeForAddress($txid, $row->address, $row->amount);
	}
	
	public function createPaymentURI(string $address, float $amountBCH): string {
		$address = preg_replace("/.+:/i", "", $address);
		// we use the bitcoincash URI supported by new wallets
		$uri = sprintf("bitcoincash:%s?amount=%s", $address, number_format($amountBCH, $this->settings->get('paymentCommaDigits')));
		return $uri;
	}
	
	public function toAmountBCH(float $amount, string $currency = ''): float {
		if ($currency === '')
			$currency = $this->settings->get('button_currency');
		if ($currency !== 'USD')
			return 0.0; // TODO crawl other rates and add support for more currencies
		$rate = $this->settings->get('rate_usd_bch');
		if ($rate === 0.0)
			return 0.0;
		return floor($amount / $rate * 100000000) / 100000000; // many wallets support only 8 decimals
	}
	
	public function showTipprButton($attrs, $content = null, $tag = "") {
		$btnConf = array();
		$btnConf['postID'] = isset($attrs['postID']) && is_numeric($attrs['postID']) ? (int)$attrs['postID'] : ($this->post !== null ? $this->post->ID : 0);
		$btnConf['recAddress'] = $this->getReceiverAddress($attrs);
		if (empty($btnConf['recAddress']))
			return esc_html("CashTippr button error: No receiver address is set.", "ekliptor") . '<br><br>';
		$btnConf['unit'] = $this->settings->get('button_currency');
		$btnConf['amount'] = isset($attrs['amount']) && $attrs['amount'] > 0.0 ? (float)$attrs['amount'] : $this->settings->get('default_amount');
		if ($btnConf['amount'] < 0.00000001)
			$btnConf['amount'] = 0.00000001;
		$btnConf['sats'] = static::toSatoshis($btnConf['amount'] / $this->settings->get('rate_usd_bch'));
		$btnConf['amountBCH'] = $this->toAmountBCH($btnConf['amount'], $btnConf['unit']);
		$btnConf['txid'] = $this->createTransactionId($btnConf['recAddress'], $btnConf['amount'], 0, $btnConf['postID']);
		if ($btnConf['txid'] === false)
			return esc_html("Unable to create a transaction ID. Please try again or report a bug if the problem persists.", "ekliptor") . '<br><br>';
		
		//$btnConf['bchImage'] = plugins_url( 'img/bch_64.png', CASHTIPPR__PLUGIN_DIR . 'cashtippr.php' );
		$btnConf['edit'] = $this->canEditButtonAmount($attrs);
		//$webhookUrl = "http%3A%2F%2Flocalhost%2Fwp-json%2Fcashtippr%2Fv1%2Fmoneybutton&data=foo%3Dba%26x%3D2";
		//$btnConf['webhookUrl'] = urlencode(site_url("wp-json/v1/moneybutton")) . "&data=" . urlencode("tid=" . $btnConf['txid']); // tid=ba&x=2
		$btnConf['webhookUrl'] = $this->getWebhookUrl(array('tid' => $btnConf['txid']));
		$btnConf['text'] = isset($attrs['text']) && $attrs['text'] !== '' ? $attrs['text'] : ''; // use default from settings otherwise
		$btnConf['isRestricted'] = $this->isContentRestricted();
		$btnConf['restrictedTxt'] = '';
		$btnConf['content'] = $content ? $content : '';
		$btnConf['showFullAccessPass'] = $btnConf['isRestricted'] === true && $btnConf['edit'] === false;
		$btnConf['callbackData'] = array(
				'days' => 0,
				'postHide' => isset($attrs['postHide']) // internal attr, meaning auto hide of the post
		);
		$btnConf['tag'] = $tag;
		$btnConf['qrcodeStatic'] = $this->qrcodeStatic;
		$btnConf['closeImage'] = $this->closeImage;
		$btnConf['loadingImage'] = $this->loadingImage;
		$btnConf['addQrCode'] = $btnConf['isRestricted'] === false && $btnConf['tag'] === 'tippr_button' && $btnConf['unit'] === 'USD'; // TODO QR code support for more currencies
		$btnConf['btnText'] = __('Send', 'ekliptor');
		$includedMoneybuttonScript = $this->getIncludedMoneybuttonScript();
		ob_start();
		switch ($tag) {
			case "tippr_button":
				// hidden words might be 0 if this is a tip button on a short article
				if ($btnConf['isRestricted'] === true && $this->hiddenWords !== 0) {
					if ($btnConf['text'] !== '')
						$btnConf['text'] = $this->buttonTpl->fillTipprButtonHiddenTextTemplate($btnConf['text'], $this->hiddenWords);
					else
						$btnConf['restrictedTxt'] = $this->buttonTpl->fillTipprButtonHiddenTextTemplate($this->settings->get('hide_tip_txt'), $this->hiddenWords);
				}
				include CASHTIPPR__PLUGIN_DIR . 'tpl/moneybutton.php';
				$this->showingTipButtons++;
				if ($btnConf['showFullAccessPass'] === true && $this->settings->get('enable_daily_pass') === true)
					$this->printAccessPass($btnConf['recAddress'], 1, $btnConf);
				if ($btnConf['showFullAccessPass'] === true && $this->settings->get('enable_custom_pass') === true)
					$this->printAccessPass($btnConf['recAddress'], $this->settings->get('custom_access_days'), $btnConf); // value must be > 1
				break;
			case "tippr_hide":
				if (empty($content)) {
					esc_html_e("Missing closing tag for shortcode: '$tag' - usage: [$tag]my hidden text[/$tag]", "ekliptor");
					echo "<br><br>";
				}
				else if ($this->isTippedPost($btnConf['postID'], $this->getMinTipAmount($btnConf['amount'])) || $this->reachedDonationGoal($btnConf['postID']) === true) {
					// TODO support for multiple tip buttons per post and hide them individually (supported on front end now)
					echo $content;
					include CASHTIPPR__PLUGIN_DIR . 'tpl/moneybutton.php'; // add a normal tip button below so users can continue tipping
					$this->showingTipButtons++;
				}
				else {
					$btnConf['isRestricted'] = true; // must be true, just to be sure
					$btnConf['showFullAccessPass'] = $btnConf['isRestricted'] === true && $btnConf['edit'] === false;
					if ($btnConf['text'] !== '')
						$btnConf['text'] = $this->buttonTpl->fillTipprButtonHiddenTextTemplate($btnConf['text'], $this->countWords($content));
					else
						$btnConf['restrictedTxt'] = $this->buttonTpl->fillTipprButtonHiddenTextTemplate($this->settings->get('hide_tip_txt'), $this->countWords($content));
					include CASHTIPPR__PLUGIN_DIR . 'tpl/hiddenContent.php';
					$this->showingTipButtons++;
					if ($btnConf['showFullAccessPass'] === true && $this->settings->get('enable_daily_pass') === true)
						$this->printAccessPass($btnConf['recAddress'], 1, $btnConf);
					if ($btnConf['showFullAccessPass'] === true && $this->settings->get('enable_custom_pass') === true)
						$this->printAccessPass($btnConf['recAddress'], $this->settings->get('custom_access_days'), $btnConf); // value must be > 1
				}
				break;
				// TODO add tippr_unhide shortcode as opposite which removes content after a tip
			default:
				if (apply_filters('show_tippr_button', false, $btnConf, $attrs, $content, $tag) === false) {
					esc_html_e("Unknown shortcode: '$tag'", "ekliptor"); // TODO inpust error template with color custimizations by user
					echo "<br><br>";
				}
		}
		$docHtml = ob_get_contents();
		ob_end_clean();
		//$this->setIncludedMoneybuttonScript($includedMoneybuttonScript);
		$this->setIncludedMoneybuttonScript(true);
		wp_enqueue_script( 'badger-wallet', Cashtippr::BADGER_WALLET_JS, array(), CASHTIPPR_VERSION, true );
		return $docHtml;
	}
	
	public function getPost(WP_Post $post_object) {
		$this->post = $post_object;
		//$post_object->post_title = "XX"; // works
		//$post_object->post_content = "FOOOOO"; // doesn't change the output, use the_content hook
		//$post_object->post_content_filtered  = "FOOOOO FILTERED";
		//$post_object->post_excerpt = "EXCERPT...";
	}
	
	public function restrictPostText(string $content) {
		$allPostTypeFilter = array('post', 'page');
		// Check if we're inside the main loop in a single post page.
		// shouldn't we filter it at some other places too?
		if ( /*is_single() && */ is_singular($allPostTypeFilter) && in_the_loop() && is_main_query() ) {
			if (!isset($_SESSION['postViews']))
				$_SESSION['postViews'] = 0;
			$_SESSION['postViews']++;
			$postID = $this->post !== null ? $this->post->ID : 0;
			
			// restrict the number of words
			$containsButton = $this->containsTipprButton($content); // if there is any tip button inserted we don't hide automatically
			$hasFullAccessPass = $this->hasFullAccessPass();
			$reachedDonationGoal = $this->reachedDonationGoal($postID);
			$isTippedPost = $this->isTippedPost($postID);
			$content = apply_filters('cashtippr_restrict_post_before', $content, $containsButton, $hasFullAccessPass, $reachedDonationGoal, $isTippedPost);
			// TODO find out for which tippr button this is and check the min amount (deduct a few cents TX fees. 0.49999795796624 = 0.5)
			if ($containsButton === false && $hasFullAccessPass === false && $reachedDonationGoal === false && $isTippedPost === false && $this->settings->get('enable_limit_words') === true) {
				// TODO this removes html formatting for the user. find a better way
				$contentStripped = wp_strip_all_tags($content, false);
				$allWords = str_word_count($contentStripped, 1);
				$this->postWordLen = count($allWords);
				if ($this->postWordLen > $this->settings->get('limit_words')) {
					$moreText = __('<span class="ct-more">...</span>', 'ekliptor');
					//$words = array_slice($allWords, 0, $this->settings->get('limit_words'));
					//$content = implode(' ', $words) . $moreText;
					$content = wp_trim_words($contentStripped, $this->settings->get('limit_words'), $moreText);
					$this->hiddenWords = $this->postWordLen - str_word_count($content, 0); // '...' counts as 0 words
					
					if ($this->settings->get('show_search_engines') === true && $this->hiddenWords > 0) { // hiddenWords should always be > 0 here
						//$hiddenText = array_slice($allWords, $this->settings->get('limit_words'));
						$encoding = get_bloginfo('charset');
						$start = mb_strlen($content, $encoding) - mb_strlen($moreText, $encoding); // '...' is not present in stripped
						$hiddenText = mb_substr($contentStripped, $start, null, $encoding);
						ob_start();
		        		include CASHTIPPR__PLUGIN_DIR . 'tpl/hiddenTextPart.php';
		        		$hiddenTextHtml = ob_get_contents();
		        		ob_end_clean();
		        		$content .= $hiddenTextHtml;
					}
				}
			}
			
			// check if we want to automatically add a button for this post type
			$postTypeFilter = array();
			if ($this->settings->get('button_posts') === true)
				$postTypeFilter[] = 'post';
			if ($this->settings->get('button_pages') === true)
				$postTypeFilter[] = 'page';
			$addButton = count($postTypeFilter) !== 0 && is_singular($postTypeFilter); // is_singular() returns true if array is empty
			$isButtonPage = $containsButton || $addButton;
			
			// insert ad for user without donations
			if ($this->settings->get('enable_ads_top') === true && $this->getTipAmount() === 0.0 && $isButtonPage === true)
				$content = $this->settings->get('ad_code') . $content;
			
			// if donation goal is enabled add a progress bar with current donations
			if ($this->settings->get('enable_donation_goal') === true)
				$content .= $this->buttonTpl->getProgressBar($postID);
			// if no goal is set, check the option to always show donations per post
			else if ($this->settings->get('show_donations') === true)
				$content .= $this->buttonTpl->getDonationStatus($postID);
			
			// add the bitcon faucet link
			if ($this->settings->get('enable_faucet') === true && $isButtonPage === true) {
				ob_start();
	        	include CASHTIPPR__PLUGIN_DIR . 'tpl/faucet.php';
	        	$faucetHtml = ob_get_contents();
	        	ob_end_clean();
	        	$content .= $faucetHtml;
			}
			
			if ($containsButton === true)
				return $content;
			if ($addButton === false)
				return $content;
			// the_content hook is executed before shortcodes, so we can just add another shortcode
			return $content . '<br>[tippr_button postHide="1"]';
		}
		return $content;
	}
	
	public function addFooterCode() {
		$cfg = array(
			'cookieLifeDays' => ceil(static::SESSION_LIFETIME_SEC / DAY_IN_SECONDS),
			'cookiePath' => $this->siteUrlParts['path'],
			'siteUrl' => $this->getSiteUrl(),
			'show_search_engines' => $this->settings->get('show_search_engines'),
			'display_currency' => strtolower($this->settings->get('button_currency')),
			'rate' => array(
					'usd' => $this->settings->get('rate_usd_bch')
			),
			'paymentCommaDigits' => $this->settings->get('paymentCommaDigits'),
			// TODO move localized strings into a separate .js file generated from PHP if we have more strings
			'badgerLocked' => __('Your BadgerWallet is locked. Please open it in your browser toolbar and enter your password before sending money.', 'ekliptor'),
		);
		if ($this->settings->get('show_cookie_consent') === true && !isset($_COOKIE[static::CONSENT_COOKIE_NAME])) {
			// TODO add option to only show this to specific countries
			// from get_the_privacy_policy_link()
			$policy_page_id = (int)get_option( 'wp_page_for_privacy_policy' );
			$privacyPageTitle = $policy_page_id ? get_the_title( $policy_page_id ) : __('Privacy Policy', 'ekliptor');
			include CASHTIPPR__PLUGIN_DIR . 'tpl/cookieConfirm.php';
		}
		$cfg = apply_filters('cashtippr_js_config', $cfg);
		echo '<script type="text/javascript">var cashtipprCfg = ' . json_encode($cfg) . ';</script>';
	}
	
	public function addPluginScripts() {
		wp_enqueue_style( 'cashtippr', plugins_url( 'tpl/css/cashtippr.css', CASHTIPPR__PLUGIN_DIR . 'cashtippr.php' ), array(), CASHTIPPR_VERSION );
		wp_enqueue_script( 'cashtippr-bundle', plugins_url( 'tpl/js/bundle.js', CASHTIPPR__PLUGIN_DIR . 'cashtippr.php' ), array(), CASHTIPPR_VERSION, true );
	}
	
	public function getSettings(): CTIP_Settings {
		return $this->settings;
	}
	
	public function getButtonTpl(): ButtonTemplates {
		return $this->buttonTpl;
	}
	
	public function getLoadingImage(): string {
		return $this->loadingImage;
	}
	
	public function cleanupTransactions() {
		global $wpdb;
		$table = static::getTableName('transactions');
		$maxAge = date('Y-m-d H:i:s', time() - static::CLEANUP_TRANSACTIONS_H * HOUR_IN_SECONDS);
		$wpdb->query("DELETE FROM $table WHERE created < '$maxAge'");
		
		// cleanup the dir with QR codes
		$cacheDir = CASHTIPPR__PLUGIN_DIR . 'data/temp/qr/';
		$files = scandir($cacheDir);
		if ($files === false) {
			static::notifyErrorExt('Error scanning qr code dir to cleanup', "cache dir: $cacheDir");
			return;
		}
		// cleanup by age, oldest creation/changed time first
		$deletionTime = time() - static::CLEANUP_TRANSACTIONS_H*HOUR_IN_SECONDS;
		foreach ($files as $file) {
			if (empty($file) || $file[0] === '.')
				continue;
			$filePath = $cacheDir . '/' . $file;
			$lastChanged = filectime($filePath);
			if ($lastChanged < $deletionTime)
				@unlink($filePath);
		}
	}
	
	public function updateCurrencyRates() {
		$res = wp_remote_get('https://index-api.bitcoin.com/api/v0/cash/price/usd');
		if (is_array($res) === false) {
			static::notifyErrorExt('Error updating currency rates', $res);
			return;
		}
		$json = json_decode($res['body']);
		if ($json === null || empty($json->price)) {
			static::notifyErrorExt('Invalid response when updating currency rates', $res);
			return;
		}
		if ($json->price > 0.0)
			$this->settings->set('rate_usd_bch', $json->price / 100.0); // receiced in USD cents
	}
	
	public static function scheduleUnsuedAddressSearch($offsetSec = 0): int {
		$scheduleTime = time() + $offsetSec;
		wp_schedule_single_event($scheduleTime, 'ct_unused_address_search');
		return $scheduleTime;
	}
	
	public function searchUnusedAddress() {
		$scheduleTime = static::scheduleUnsuedAddressSearch(5*MINUTE_IN_SECONDS); // schedule it again in case it gets aborted
		$xPub = $this->settings->get('xPub');
		$hdPathFormat = $this->settings->get('hdPathFormat');
		$nextCount = $this->settings->get('addressCount') + 1; // in live mode we also increase +1 when creating the 1st address
		$skipCount = $this->settings->get('skipUsedAddressCount');
		if ($skipCount < 1)
			$skipCount = 1;
		$blockchainApi = $this->createBlockchainApiInstance();
		$hasExistingTransactions = false;
		while ($hasExistingTransactions === false)
		{
			// make sure we don't timeout
			// WP cron already calls ignore_user_abort
			set_time_limit(60);
			$address = $blockchainApi->createNewAddress($xPub, $nextCount, $hdPathFormat);
			if (!$address || !$address->cashAddress) { // try again later
				static::notifyError("Failed to create address when finding unused address", "");
				return;
			}
			$balance = $blockchainApi->getAddressBalance($address->cashAddress);
			if ($balance > 0.0)
				$nextCount += $skipCount;
			else {
				$this->settings->set('addressCount', $nextCount);
				break; // we have succeeded
			}
		}
		//static::notifyError("found empty address $nextCount", $address);
		wp_unschedule_event($scheduleTime, 'ct_unused_address_search'); // done successfully. remove it from schedule
	}
	
	/*
	public function onUpgrade($upgrader_object, $options) {
		// upgrader_process_complete is useless since it's run on the OLD not updated code of WP. just like the upgrader_post_install filter
		$currentPlugin = plugin_basename( CASHTIPPR__PLUGIN_DIR . 'cashtippr.php' );
		if ($options['action'] === 'update' && $options['type'] === 'plugin' ) {
			foreach($options['plugins'] as $plugin) {
				if ($plugin !== $currentPlugin)
					continue;
				$migrate = new DatabaseMigration(CASHTIPPR_VERSION, CASHTIPPR_VERSION);
				if ($migrate->ensureLatestVersion() === false)
					static::notifyErrorExt("Error ensuring latest DB version on migration", $migrate->getLastError());
			}
		}
	}
	*/
	
	public function notifyError($subject, $error, $data = null) {
		static::notifyErrorExt($subject, $error, $data); // member function included for backwards compatibility
	}
	
	public static function notifyErrorExt($subject, $error, $data = null) {
		global $wpdb;
		if (static::DEBUG === false)
			return;
		$table = static::getTableName("messages_system");
		if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
			pre_print_r($subject);
			pre_print_r($error);
			if ($data !== null)
				pre_print_r($data);
			return; // table doesn't exist
		}
		if (!is_string($error))
			$error = print_r($error, true);
		$rowCount = $wpdb->insert($table, array(
				'sender' => 'SystemError',
				'subject' => $subject,
				'text' => $error,
				'data' => $data !== null ? serialize($data) : null,
				'site' => strtolower(get_bloginfo('name'))
		));
	}
	
	public static function getRandomString($len) {
		$chars = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		$max = strlen($chars)-1;
		mt_srand();
		$random = '';
		for ($i = 0; $i < $len; $i++)
			$random .= $chars[mt_rand(0, $max)];
		return $random;
	}
	
	public static function toSatoshis($bch) {
		return floor($bch * 100000000);
	}
	
	public static function isValidUrl(string $url): bool {
		if (strlen($url) < 6 || preg_match("/^https?:\/\//", $url) !== 1 || strpos($url, '.') === false)
			return false;
		return true;
	}
	
	public function getSiteUrl(array $query = array()) {
		$url = $this->siteUrlParts['scheme'] . '://' . $this->siteUrlParts['host'];
		if (isset($this->siteUrlParts['port']))
			$url .= $this->siteUrlParts['port'];
		$url .= $this->siteUrlParts['path'];
		$first = true;
		foreach ($query as $key => $value) {
			$url .= $first === true ? '?' : '&';
			$url .= $key . '=' . urlencode($value);
			$first = false;
		}
		return $url;
	}
	
	public function getCurrentUrl() {
		global $wp;
		return home_url( add_query_arg( array(), $wp->request ) );
	}
	
	public function createBlockchainApiInstance(): AbstractBlockchainApi {
		$blockchainApi = AbstractBlockchainApi::getInstance($this->settings->get('blockchain_api'), $this->settings->get('blockchain_rest_url'));
		AbstractBlockchainApi::setLogger(function (string $subject, $error, $data = null) {
			\Cashtippr::notifyErrorExt("BlockChain API: " . $subject, $error, $data);
		});
		$httpAgent = new WordpressHttpAgent(function (string $subject, $error, $data = null) {
			\Cashtippr::notifyErrorExt("HTTP: " . $subject, $error, $data);
		});
		$blockchainApi->setHttpAgent($httpAgent);
		return $blockchainApi;
	}
	
	public function getReceiverAddress($attrs = array()) {
		if ($this->settings->get('author_bch_addresses') === false) 
			return $this->settings->get('bch_address');
		$address = isset($attrs['address']) && $this->sanitizer->isValidBitcoinCashAddress($attrs['address']) ? trim($attrs['address']) : $this->settings->get('bch_address');
		$authorID = (int)get_the_author_meta('ID');
		if ($authorID === 0)
			return $address;
		$authorAddress = get_user_meta($authorID, 'author_bch_address', true);
		return $authorAddress ? $authorAddress : $address;
	}
	
	public function createTransactionId(string $recAddress, float $amount, int $days = 0, int $postID = 0) {
		global $wpdb;
		// TODO for better performance create all needed TX per page at once and insert them in bulk
		// TODO store this in memcached too if memcached is used
		$table = static::getTableName('transactions');
		$txid = static::getRandomString(40);
		$data = array(
				'txid' => $txid,
				'address' => $recAddress,
				'amount' => $amount,
				'session_id' => session_id(),
				'post_id' => $postID !== 0 ? $postID : ($this->post !== null ? $this->post->ID : 0),
				'days' => $days
		);
		if ($wpdb->insert($table, $data) !== 1)
			return false; // otherwise we can't check if we received the payment
		return $txid;
	}
	
	protected function containsTipprButton(string $postContent, bool $hideButtonOnly = false): bool {
		// the shortcodes are added after the_contet hook
		// so we check here quick & dirty // TODO improve this, also add a hook for the blurry image shortcode
		if (mb_strpos($postContent, ']') === false)
			return false;
		if ($hideButtonOnly === true)
			return mb_strpos($postContent, '[tippr_hide') !== false || mb_strpos($postContent, '[tippr_blur') !== false;
		return mb_strpos($postContent, '[tippr_button') !== false || mb_strpos($postContent, '[tippr_hide') !== false || mb_strpos($postContent, '[tippr_blur') !== false;
	}
	
	/**
	 * Checks if the current page content on our blog is restricted for this user.
	 * @return bool true if restricted, false otherwise
	 */
	protected function isContentRestricted(): bool {
		// the order of the checks here is important!
		if ($this->post === null)
			return false; // shouldn't happen
		
		if ($this->settings->get('enable_posts_free') === true) {
			// check the user's free view limit
			// this value is incremented before shortcodes, so before this function is called
			if ($this->getPostViewCount() <= $this->settings->get('posts_free'))
				return false;
		}
		
		// check for post expiry (making it visible to everybody)
		if ($this->settings->get('enable_free_access') === true) {
			$created = strtotime($this->post->post_date_gmt);
			if ($created > 0 && $created + $this->settings->get('free_access_days')*DAY_IN_SECONDS <= time())
				return false;
		}
		
		// check for donation goal with post meta
		if ($this->settings->get('enable_donation_goal') === true) {
			$tipAmount = (float)get_post_meta($this->post->ID, 'tipAmount', true); // float or false // TODO cache value?
			if ($tipAmount >= $this->settings->get('donation_goal'))
				return false;
		}
		
		// check for hide button
		if ($this->containsTipprButton($this->post->post_content, true) === true)
			return true;
		
		// automatic word limit enabled?
		if ($this->settings->get('enable_limit_words') === false)
			return false;
		return true;
	}
	
	protected function canEditButtonAmount($attrs): bool {
		if (is_array($attrs) === false)
			return false;
		foreach ($attrs as $key => $value) {
			$value = strtolower($value);
			if ($value === 'edit') // attr without value = numeric array
				return true;
			if (strtolower($key) === 'edit') // attr with value = associative array
				return $value === '1' || $value === 'true';
		}
		return false;
	}
	
	/**
	 * Generate a callback URL with the given arguments.
	 * @param array $args key-value pairs of args as associative array
	 * @return string
	 */
	protected function getWebhookUrl(array $args): string {
		$url = urlencode(site_url("wp-json/v1/moneybutton"));
		$data = '';
		foreach ($args as $key => $value) {
			if (!empty($data))
				$data .= '&';
			$data .= $key . '=' . $value;
		}
		if ($data !== '')
			$url .= '&data=' . urlencode($data); // key and value encoded and added as query param
		return $url;
	}
	
	protected function printAccessPass(string $recAddress, int $days, array $btnConf) {
		$btnConf['amount'] = $this->settings->get($days === 1 ? 'daily_access_price' : 'custom_access_price');
		if ($days === 1)
			$btnConf['tipTxt'] = $this->buttonTpl->fillTipprButtonTextTemplate($this->settings->get('daily_tip_txt'));
		else
			$btnConf['tipTxt'] = $this->buttonTpl->fillTipprButtonTextTemplate($this->settings->get('custom_tip_txt'));
		$btnConf['txid'] = $this->createTransactionId($recAddress, $btnConf['amount'], $days);
		if ($btnConf['txid'] === false) {
			echo esc_html("Unable to create a transaction ID. Please try again or report a bug if the problem persists.", "ekliptor") . '<br><br>';
			return;
		}
		$btnConf['callbackData']['days'] = $days;
		$btnConf['webhookUrl'] = $this->getWebhookUrl(array('tid' => $btnConf['txid']));
		include CASHTIPPR__PLUGIN_DIR . 'tpl/accessPass.php';
		$this->showingTipButtons++;
	}
	
	protected function countWords(string $text): int {
		return str_word_count(wp_strip_all_tags($text, true), 0);
	}
	
	protected static function createTransactionsTable(): bool {
		global $wpdb;
		$table = static::getTableName('transactions');
		if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table)
			return true; // table already exists
		$success = $wpdb->query("CREATE TABLE `$table` (
  				`txid` char(40) NOT NULL,
				`address` varchar(64) NOT NULL,
				`amount` float NOT NULL,
  				`session_id` varchar(40) NOT NULL,
				`post_id` bigint(20) NOT NULL DEFAULT '0',
				`days` int(11) NOT NULL DEFAULT '0' COMMENT '0 means tip per post',
  				`created` timestamp NOT NULL DEFAULT current_timestamp()
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;") !== false;
		if ($success === false)
			return false;
		$success = $wpdb->query("ALTER TABLE `$table`
  				ADD PRIMARY KEY (`txid`);") !== false;
		return $success === true;
	}
	
	protected static function bailOnActivation($message, $escapeHtml = true, $deactivate = true) {
		include CASHTIPPR__PLUGIN_DIR . 'tpl/message.php';
		if ($deactivate) {
			$plugins = get_option ( 'active_plugins' );
			$cashtippr = plugin_basename ( CASHTIPPR__PLUGIN_DIR . 'cashtippr.php' );
			$update = false;
			foreach ( $plugins as $i => $plugin ) {
				if ($plugin === $cashtippr) {
					$plugins [$i] = false;
					$update = true;
				}
			}
			
			if ($update) {
				update_option ( 'active_plugins', array_filter ( $plugins ) );
			}
		}
		exit ();
	}
}
?>