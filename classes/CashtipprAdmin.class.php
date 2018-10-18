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
	public $pageHook;
	
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
		
		add_filter('removable_query_args', array($this, 'addRemovableAdminArgs'));
		
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
		wp_enqueue_style( 'cashtippr-admin', plugins_url( 'tpl/css/cashtippr-admin.css', CASHTIPPR__PLUGIN_DIR . 'cashtippr.php' ) );
		wp_enqueue_script( 'cashtippr-bundle', plugins_url( 'tpl/js/bundle.js', CASHTIPPR__PLUGIN_DIR . 'cashtippr.php' ), array(), CASHTIPPR_VERSION, false );
		add_action( "load-{$this->pageHook}", array( $this, 'addMetaboxScripts' ) );
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
		}
        
        $pluginBoxes = array(
        		'BlurryImage' => false,
        		'Shout' => false
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
    
    public function addPrivacyPolicyContent() {
    	if ( ! function_exists( 'wp_add_privacy_policy_content' ) )
    		return;
    	$content = sprintf(
        	__( 'This website uses cookies to track recurring visitors and their previous donations/payments.
				Additionally it loads code from MoneyButton.com for payments. Users have to register an account there 
				to send money. Their privacy policy can be found <a href="%s" target="_blank" rel="nofollow">here</a>.',
        			'ekliptor' ),
        	'https://www.moneybutton.com/about'
    	);
    	wp_add_privacy_policy_content('CashTippr', wp_kses_post( wpautop( $content, false ) ) );
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