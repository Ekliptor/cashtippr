<?php
use Ekliptor\Cashtippr\UserSettings;

require_once CASHTIPPR__PLUGIN_DIR . 'classes/UserSettings.php';

class CashtipprAdmin {
	const PAGE_HOOK = 'toplevel_page_cashtippr';
	
	/** @var CashtipprAdmin */
	private static $instance = null;
	
	/**
	 * Name of the page hook when the menu is registered.
	 * For example: toplevel_page_cashtippr
	 * @var string Page hook
	 */
	public $pageHook = '';
	
	/** @var CTIP_TemplateEngine */
	public $tpl = null;
	
	/** @var Cashtippr */
	protected $cashtippr;
	
	/** @var CTIP_Settings */
	protected $settings = null;
	
	/** @var CTIP_Settings */
	protected $userSettings = null;
	
	private function __construct(Cashtippr $cashtippr) {
		if ($cashtippr === null)
			throw new Error("Cashtippr class must be provided in constructor of " . get_class($this));
		$this->cashtippr = $cashtippr;
	}
	
	public static function getInstance(Cashtippr $cashtippr = null) {
		if (self::$instance === null)
			self::$instance = new self($cashtippr);
		return self::$instance;
	}
	
	public function init() {
		$this->settings = $this->cashtippr->getSettings(); // settings class created after init
		$this->tpl = new CTIP_TemplateEngine($this->settings);
		$this->userSettings = new UserSettings($this->tpl);
		
		// init hooks
		//add_action( 'admin_init', array( self::$instance, 'baa' ) ); // fired on every admin page (also ajax)
		add_action( 'admin_menu', array( self::$instance, 'createMenu' ), 5 ); // Priority 5, so it's called before Jetpack's admin_menu.
		add_action( 'current_screen', array( $this, 'initCurrentScreen' ), 10, 1 );
		
		//add_action( 'admin_init', array( $this, 'loadAssets' ) ); // done after screen setup
		add_action( 'admin_init', array( $this, 'displayAdminNotices' ) );
		add_action( 'admin_init', array( $this, 'addPrivacyPolicyContent' ) );
		add_action('admin_footer', array($this, 'addAdminJavascript'));
		
		add_filter('removable_query_args', array($this, 'addRemovableAdminArgs'));
		add_filter('cashtippr_settings_change_xPub', array($this, 'onUpdateXpub'), 10, 4);
		add_filter('cashtippr_settings_change_detect_adblock', array($this, 'onAdBlockChange'), 10, 4);
		
		do_action('cashtippr_admin_init', $this);
	}
	
	public function createMenu() {
		/*$this->registeredPageHooks[] = */add_menu_page( __( 'CashTippr', 'ekliptor' ), __( 'CashTippr', 'ekliptor' ), 'manage_options', 'cashtippr', array(self::$instance, 'displaySettings'), plugins_url('/img/bch_16.png', CASHTIPPR__PLUGIN_DIR . 'cashtippr.php'), '55.5' );
		do_action('cashtippr_admin_menu', $this);
	}
	
	public function getPageHook(): string {
		return $this->pageHook;
	}
	
	public function getTpl(): CTIP_TemplateEngine {
		return $this->tpl;
	}
	
	public function displaySettings() {
		//global $wpdb;
		//$msgRows = $wpdb->get_results("SELECT COUNT(*) AS cnt FROM " . Cashtippr::getTableName("messages_system") . " WHERE seen = 0");
		//$messageCount = $msgRows ? (int)$msgRows[0]->cnt : 0;
		//$messageCount = 0;
		include CASHTIPPR__PLUGIN_DIR . 'tpl/admin/mainSettingsWrap.php';
	}
	
	public function showAllSettings() {
		include CASHTIPPR__PLUGIN_DIR . 'tpl/admin/mainSettings.php';
	}
	
	public function initCurrentScreen(WP_Screen $screen) {
		// id: [id] => toplevel_page_cashtippr or cashtippr_page_cashtippr_shout <- this is always the hook
		if (strpos($screen->base, 'cashtippr') === false)
			return;
		$this->pageHook = $screen->base;
		
		add_action( $this->pageHook . '_settings_page_boxes', array( $this, 'showAllSettings' ) );
		// as an alternative to listen on the screen hook we could register hooks for all sub menus here
		add_action( 'load-' . $this->pageHook, array( $this, 'addMetaBoxes' ) );
		$this->loadAssets();
	}
	
	public function displayAdminNotices() {
		$bchAddress = $this->settings->get('bch_address');
		if (empty($bchAddress)) {
			$tplVars = array(
					'msg' => __('You must enter a Bitcoin Cash address to receive money.', 'ekliptor'),
					'link' => admin_url() . 'admin.php?page=cashtippr'
			);
			$notice = new CTIP_AdminNotice($this->tpl->getTemplate('adminNotice.php', $tplVars), 'error');
			$this->tpl->addAdminNotices($notice);
		}
		if (empty($this->settings->get('xPub')) && class_exists('\\Ekliptor\\Cashtippr\\Woocommerce', false)) { // class gets initialized after admin_notices action
			$tplVars = array(
					'msg' => __('You must enter your xPub to use the Cashtippr WooCommerce Gateway.', 'ekliptor'),
					'link' => admin_url() . 'admin.php?page=cashtippr'
			);
			$notice = new CTIP_AdminNotice($this->tpl->getTemplate('adminNotice.php', $tplVars), 'error');
			$this->tpl->addAdminNotices($notice);
		}
		if (isset($_GET['notices'])) {
			$notices = explode(',', $_GET['notices']);
			foreach ($notices as $noticeData) {
				$notice = CTIP_AdminNotice::urlDecode($noticeData);
				$this->tpl->addAdminNotices($notice);
			}
		}
		do_action('cashtippr_admin_notices');
		add_action('admin_notices', array($this->tpl, 'showAdminNotices'));
	}
	
	public function loadAssets() {
		wp_enqueue_style( 'cashtippr-admin', plugins_url( 'tpl/css/cashtippr-admin.css', CASHTIPPR__PLUGIN_DIR . 'cashtippr.php' ), array(), CASHTIPPR_VERSION );
		wp_enqueue_script( 'cashtippr-bundle', plugins_url( 'tpl/js/bundle.js', CASHTIPPR__PLUGIN_DIR . 'cashtippr.php' ), array(), CASHTIPPR_VERSION, false );
		add_action( "load-{$this->pageHook}", array( $this, 'addMetaboxScripts' ) );
	}
	
	public function addAdminJavascript() {
		$cfg = $this->cashtippr->getPluginJsConfigData();
		$cfg = apply_filters('cashtippr_admin_js_config', $cfg);
		echo '<script type="text/javascript">var cashtipprAdminCfg = ' . json_encode($cfg) . ';</script>';
	}
	
	public function addMetaboxScripts() {
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'wp-lists' );
		wp_enqueue_script( 'postbox' );
	}
	
	public function addMetaBoxes(string $post_type/*, WP_Post $post*/) {
		if ($this->pageHook === static::PAGE_HOOK) {
			add_meta_box(
					'cashtippr-payment-settings',
					esc_html__( 'Payment Settings', 'ekliptor' ),
					array( $this->tpl, 'showMetaboxPayment' ),
					$this->pageHook,
					'main'
				);
	        add_meta_box(
					'cashtippr-content-settings',
					esc_html__( 'Content Settings', 'ekliptor' ),
					array( $this->tpl, 'showMetaboxContent' ),
					$this->pageHook,
					'main'
				);
	        add_meta_box(
					'cashtippr-ad-settings',
					esc_html__( 'Ad Settings', 'ekliptor' ),
					array( $this->tpl, 'showMetaboxAds' ),
					$this->pageHook,
					'main'
				);
	        add_meta_box(
					'cashtippr-advanced-settings',
					esc_html__( 'Advanced Settings', 'ekliptor' ),
					array( $this->tpl, 'showMetaboxAdvanced' ),
					$this->pageHook,
					'main'
				);
	        add_meta_box(
					'cashtippr-styling',
					esc_html__( 'Custom Style', 'ekliptor' ),
					array( $this->tpl, 'showStylingSettings' ),
					$this->pageHook,
					'main'
				);
		}
        
        $pluginBoxes = array(
        		'BlurryImage' => false,
        		'Shout' => false,
        		'SlpPress' => false,
        		'Woocommerce' => false,
        		'SlpPayments' => defined('BCHSLP_PAYMENTS_VERSION') === true // faster than get_option('slp_payments_installed')
        );
        $pluginBoxes = apply_filters('cashtippr_admin_metaboxes', $pluginBoxes, $post_type); // allows plugins to add metaboxes
        if ($this->pageHook === static::PAGE_HOOK && $this->allPluginsEnabled($pluginBoxes) === false) { // advertise our plugin addons
        	add_meta_box(
					'cashtippr-plugin-settings',
					esc_html__( 'Addons', 'ekliptor' ),
					array( $this->tpl, 'showMetaboxAddons' ),
					$this->pageHook,
					'main',
        			'default',
        			array($pluginBoxes)
				);
        }
    }
    
    public function addRemovableAdminArgs(array $removable_query_args) {
    	array_push($removable_query_args, 'notices');
    	return $removable_query_args;
    }
    
    public function onUpdateXpub($newVal, $oldVal, $key, $allSettings) {
		// reset the hdPath counter (otherwise electron cash needs to scan the full history for the TX to show up
		$allSettings['addressCount'] = 0;
		Cashtippr::scheduleUnsuedAddressSearch();
		return $newVal;
	}
	
	public function onAdBlockChange($newVal, $oldVal, $key, $allSettings) {
		if ($newVal === true)
			add_action('shutdown', array ($this, 'updateAdBlockBaitFilename' )); // modifies settings, so must be called after setting is updated
		return $newVal;
	}
    
    public function addPrivacyPolicyContent() {
    	if ( ! function_exists( 'wp_add_privacy_policy_content' ) )
    		return;
    	$content = sprintf(
        	__( 'This website uses cookies to track recurring visitors and their previous donations/payments.
				Additionally it loads code API code from bitcoin.com to interact with BadgerWallet.',
        			'ekliptor' )
    	);
    	wp_add_privacy_policy_content('CashTippr', wp_kses_post( wpautop( $content, false ) ) );
    }
    
    public function updateAdBlockBaitFilename() {
    	/* // sufficient to just create 1 file with static filename, no random name needed since we want the file to get blocked
    	// TODO we could also call this periodically via cron, but not needed unless adblock adopts to our scheme (if we were really big)
    	// first delete the existing file
    	$existingBaitFile = $this->settings->get('adFrameBaitFile');
    	if (!empty($existingBaitFile))
    		@unlink($existingBaitFile);
    	
    	// no need to use wp_handle_upload(), the data is from ourselves
    	$uploadDir = wp_upload_dir();
    	if (!isset($uploadDir['path'])) {
    		$this->settings->set('adFrameBaitFile', '');
    		return; // couldn't be created, see $uploadDir['error'] for details
    	}
    	mt_srand();
    	$len = mt_rand(10, 20);
    	$nextBaitFile = $uploadDir['path'] . DIRECTORY_SEPARATOR . Cashtippr::getRandomString($len) . '.js';
    	if (file_put_contents($nextBaitFile, 'var ctipAdblockOk = true;') === false) {
    		Cashtippr::notifyErrorExt("Error creating AdBlock bait file", "Path: $nextBaitFile");
    		$this->settings->set('adFrameBaitFile', '');
    		return;
    	}
    	$this->settings->set('adFrameBaitFile', $nextBaitFile);
    	*/
    	$existingBaitFile = $this->settings->get('adFrameBaitFile');
    	if (!empty($existingBaitFile) && file_exists($existingBaitFile) === true)
    		return;
    	$uploadDir = wp_upload_dir();
    	if (!isset($uploadDir['path'])) {
    		$this->settings->setMultiple(array('adFrameBaitFile' => '', 'adFrameBaitUrl' => ''));
    		return; // couldn't be created, see $uploadDir['error'] for details
    	}
    	$filename = 'adframe.js';
    	$nextBaitFile = $uploadDir['path'] . DIRECTORY_SEPARATOR . $filename;
    	if (file_put_contents($nextBaitFile, 'var ctipAdblockOk = true;') === false) {
    		Cashtippr::notifyErrorExt("Error creating AdBlock bait file", "Path: $nextBaitFile");
    		$this->settings->setMultiple(array('adFrameBaitFile' => '', 'adFrameBaitUrl' => ''));
    		return;
    	}
    	$this->settings->setMultiple(array('adFrameBaitFile' => $nextBaitFile, 'adFrameBaitUrl' => $uploadDir['url'] . '/' . $filename));
    }
    
    protected function allPluginsEnabled(array $pluginBoxes): bool {
    	foreach ($pluginBoxes as $plugin => $enabled) {
    		if ($enabled === false)
    			return false;
    	}
    	return true;
    }
}
?>