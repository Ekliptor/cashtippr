<?php
class CTIP_Settings {
	/** @var Cashtippr */
	protected $cashtippr;
	/** @var CashtipprAdmin */
	protected $cashtipprAdmin;
	
	/** @var string Settings field */
	public  $settingsField;
	
	/** @var array map with options cached after DB load */
	protected $options = null;
	
	/** @var bool Indicates if the admin is currently saving/updating settings. Used speed up plugin and skip sanitization otherwise. */
	protected $isSaving = false;
	
	public function __construct(Cashtippr $cashtippr, CashtipprAdmin $cashtipprAdmin = null) {
		$this->settingsField = "cashtippr_settings";
		$this->cashtippr = $cashtippr;
		$this->cashtipprAdmin = $cashtipprAdmin;
		
		//add_action('init', array($this, 'settingsInit'), 10);
		add_action( 'admin_init', array( $this, 'settingsAdminInit' ), 10 );
		add_action( 'admin_init', array( $this, 'registerSettings' ), 5 );
	}
	
	/*
	public function settingsInit() {
		// nothing to do yet
	}
	*/
	
	public function settingsAdminInit() {
		if ( 'options.php' === $GLOBALS['pagenow'] )
			$this->handleUpdatePost();
	}
	
	public function registerSettings() {
		//* If the settings field doesn't exist, we can't register it.
		if (!$this->settingsField )
			return;

		$defaults = array(
			'type'              => 'string', // not string, an associative array containing all settings
			'group'             => $this->settingsField,
			'description'       => '',
			'sanitize_callback' => array($this, 'sanitize'),
			'show_in_rest'      => false,
			'default'			=> ''
		);
		register_setting( $this->settingsField, $this->settingsField, $defaults );
		add_option( $this->settingsField, $this->getDefaultSiteOptions() );

		//$this->checkOptionsReset(); // TODO add resetToDefault
	}
	
	public function sanitize($settings) {
		if ($this->isSaving === false) // this is also always called once when loding this plugin settings page
			return $settings;
		static $sanitized = false; // called twice on save (so 3x in total)
		if ($sanitized === true)
			return $settings;
		$sanitized = true;
		
		$allSettings = $this->getAll();
		$defaults = $this->getDefaultSiteOptions(true);
		$sanitizer = $this->getOptionSanitizers();
		// meta parameter one level above the registered cashtippr_settings array
		$allCheckboxes = isset($_POST['all_checkboxes']) ? explode(',', sanitize_text_field($_POST['all_checkboxes'])) : array();
		$allCheckboxes = array_flip($allCheckboxes);
		// sanitize & add missing keys
		foreach ($defaults as $key => $value) {
			if (!isset($settings[$key])) {
				if (isset($allCheckboxes[$key]))
					$settings[$key] = false; // unchecked checkboxes are not present in html forms , so do this BEFORE falling back to prev setting
				else if (isset($allSettings[$key]))
					$settings[$key] = $allSettings[$key]; // keep the previous value
				else
					$settings[$key] = $defaults[$key]; // add the default value
				
				if ($key === 'xPub' && $settings[$key] !== $allSettings[$key]) {
					// reset the hdPath counter (otherwise electron cash needs to scan the full history for the TX to show up
					$settings['addressCount'] = 0;
				}
			}
			else
				$settings[$key] = call_user_func($sanitizer[$key], $settings[$key], $key);
		}
		update_option($this->settingsField, $settings);
		$this->options = $settings;
		return $settings;
	}
	
	public function handleUpdatePost() {
		// Verify update nonce.
		if ( false === $this->verifyNonce() )
			return;

		//$this->init_sanitizer_filters();
		$this->isSaving = true; // enable sanitization

		//* Flush transients after options have changed.
		add_action( "update_option_{$this->settingsField}", array( $this, 'updateOption' ) );
	}
	
	public function updateOption($options) {
		static $updated = 0;
		if (!is_array($options) || (isset($updated) && $updated >= 2)) {
			//$this->cashtippr->notifyError("options wrong", $options); // happens after update? with empty string
			return;
		}
		/**
		 * Array
			(
			    [myplugin_new_field] => foo
			)
			Array
			(
			    [closedpostboxesnonce] => 9cae849239
			    [meta-box-order-nonce] => ddb0e5d489
			    [option_page] => autodescription-site-settings
			    [action] => update
			    [_wpnonce] => 223346def4
			    [_wp_http_referer] => /wolfbotpress/wp-admin/admin.php?page=cashtippr
			    [myplugin_inner_custom_box_nonce] => c1d2bf285e
			    [myplugin_new_field] => abc
			    [submit] => Save Changes
			)
		 */
		/* // moved handling POST data to sanitize() function to ensure proper sanitization
		foreach ($options as $key => $oldValue) {
			if (isset($_POST[$key]) && !empty($_POST[$key]))
				$options[$key] = $_POST[$key];
		}
		*/
		$updated++; // prevent endless recursion when listening for update event. being called twice by wp
		//update_option($this->settingsField, $options);
		//$this->options = $options;
	}
	
	/**
	 * Get a plugin setting by key.
	 * @param string $key
	 * @param boolean $useCache
	 * @return mixed
	 */
	public function get(string $key, $useCache = true) {
		if ($this->options === null || $useCache === false) {
			$this->options = get_option($this->settingsField); // defaults should already be set, skip 2nd function call
			if ($this->options === false)
				$this->options = $this->getDefaultSiteOptions();
			else if (!isset($this->options['version']) || $this->options['version'] !== CASHTIPPR_VERSION) {
				// first access after an update
				// we can't call this from our plugin_activation() functios because no instances exist at that time
				// TODO improve via non-blocking HTTP "cron" request from plugin_activation() hook?
				$this->afterPluginUpdate();
			}
		}
		return isset($this->options[$key]) ? $this->options[$key] : false;
	}
	
	public function getAll($useCache = true) {
		$this->get('version', $useCache); // just load them from DB or cache
		return $this->options;
	}
	
	/**
	 * Update a plugin setting by key.
	 * @param string $key
	 * @param mixed $value
	 * @return bool true if the setting was updated.
	 */
	public function set(string $key, $value): bool {
		$this->options = get_option($this->settingsField); // always reload from DB first to ensure we have the latest version
		if ($this->options === false) // shouldn't happen
			$this->options = $this->getDefaultSiteOptions();
		$this->options[$key] = $value;
		return update_option($this->settingsField, $this->options);
	}
	
	/**
	 * Update multiple plugin settings at once. Use this for improved performance (fewer DB queries).
	 * @param array $settings associative array with settings
	 * @return bool true if the settings were updated.
	 */
	public function setMultiple(array $settings): bool {
		$this->options = get_option($this->settingsField); // always reload from DB first to ensure we have the latest version
		if ($this->options === false) // shouldn't happen
			$this->options = $this->getDefaultSiteOptions();
		foreach ($settings as $key => $value) {
			$this->options[$key] = $value;
		}
		return update_option($this->settingsField, $this->options);
	}
	
	/**
	 * Get the default of any of the The plugin Framework settings.
	 *
	 * @param string $key required The option name
	 * @param string $setting optional The settings field
	 * @param bool $use_cache optional Use the options cache or not. For debugging purposes.
	 * @return int|bool|string default option
	 *         int '-1' if option doesn't exist.
	 */
	public function getDefaultSettings( $key, $setting = '', $use_cache = true ) {
		if ( ! isset( $key ) || empty( $key ) )
			return false;

		//* Fetch default settings if it's not set.
		if ( empty( $setting ) )
			$setting = $this->settingsField;

		//* If we need to bypass the cache
		if ( ! $use_cache ) {
			$defaults = $this->getDefaultSiteOptions();

			if ( ! is_array( $defaults ) || ! array_key_exists( $key, $defaults ) )
				return -1;

			return is_array( $defaults[ $key ] ) ? \stripslashes_deep( $defaults[ $key ] ) : stripslashes( $defaults[ $key ] );
		}

		static $defaults_cache = array();

		//* Check options cache
		if ( isset( $defaults_cache[ $key ] ) )
			//* Option has been cached
			return $defaults_cache[ $key ];

		$defaults_cache = $this->getDefaultSiteOptions();

		if ( ! is_array( $defaults_cache ) || ! array_key_exists( $key, (array) $defaults_cache ) )
			$defaults_cache[ $key ] = -1;

		return $defaults_cache[ $key ];
	}

	/**
	 * Get the warned setting of any of the The plugin Framework settings.
	 *
	 * @param string $key required The option name
	 * @param string $setting optional The settings field
	 * @param bool $use_cache optional Use the options cache or not. For debugging purposes.
	 * @return int 0|1 Whether the option is flagged as dangerous for SEO.
	 *         int '-1' if option doesn't exist.
	 */
	public function getWarnedSettings( $key, $setting = '', $use_cache = true ) {
		if ( empty( $key ) )
			return false;

		return false; // we don't have any warned settings yet
	}
	
	/**
	 * Checks the plugin Settings page nonce. Returns false if nonce can't be found.
	 * Performs wp_die() when nonce verification fails.
	 *
	 * Never run a sensitive function when it's returning false. This means no nonce can be verified.
	 *
	 * @return bool True if verified and matches. False if can't verify.
	 */
	protected function verifyNonce() {
		static $validated = null;
		if ( isset( $validated ) )
			return $validated;

		//* If this page doesn't store settings, no need to sanitize them
		if ( ! $this->settingsField )
			return $validated = false;

		/**
		 * If this page doesn't parse the site options,
		 * There's no need to filter them on each request.
		 * Nonce is handled elsewhere. This function merely injects filters to the $_POST data.
		 */
		if ( empty( $_POST ) || ! isset( $_POST[ $this->settingsField ] ) || ! is_array( $_POST[ $this->settingsField ] ) )
			return $validated = false;

		//* This is also handled in /wp-admin/options.php. Nevertheless, one might register outside of scope.
		\check_admin_referer( $this->settingsField . '-options' );

		return $validated = true;
	}
	
	protected function afterPluginUpdate() {
		// add missing keys of new settings after our plugin has been updated to a new version
		$settings = $this->options;
		$defaults = $this->getDefaultSiteOptions(false);
		foreach ($defaults as $key => $value) {
			if (!isset($settings[$key]))
				$settings[$key] = $defaults[$key]; // add the default value
			// TODO outdated settings array to remove old settings
		}
		$settings['version'] = CASHTIPPR_VERSION;
		update_option($this->settingsField, $settings);
		$this->options = $settings;
	}
	
	protected function getOptionSanitizers() {
		$defaultSanitizer = new CTIP_Sanitizer();
		$tpl = $this->cashtipprAdmin->tpl;
		$defaults = $this->getDefaultSiteOptions();
		$settings = $this;
		$sanitizer = array(
				// custom sanitizers:
				// values are optional and must contain a PHP callable function($newValue, string $settingName)
				// the return value must be the sanitzed value
				'bch_address' => function($newValue, string $settingName) use($tpl, $defaultSanitizer) {
					$newValue = sanitize_text_field($newValue);
					if ($defaultSanitizer->isValidBitcoinCashAddress($newValue) === false) {
						$tplVars = array(
								'msg' => __('Invalid Bitcoin Cash address', 'ekliptor'),
								//'link' => admin_url() . 'admin.php?page=cashtippr'
						);
						$notice = new CTIP_AdminNotice($tpl->getTemplate('adminNotice.php', $tplVars), 'error');
						$defaultSanitizer->addAdminNotices($notice);
						return ''; // leave it empty
					}
					return $newValue;
				},
				'button_currency' => function($newValue, string $settingName) use($tpl, $defaultSanitizer, $settings) {
					$newValue = strtoupper(sanitize_text_field($newValue));
					if (strlen($newValue) < 3) {
						$tplVars = array(
								'msg' => __('The button currency can not be empty.', 'ekliptor'),
						);
						$notice = new CTIP_AdminNotice($tpl->getTemplate('adminNotice.php', $tplVars), 'error');
						$defaultSanitizer->addAdminNotices($notice);
						return $settings->get($settingName); // keep the previous value
					}
					if ($newValue !== 'USD') { // TODO crawl more conversion rates to display them
						$tplVars = array(
								'msg' => __('Currently only USD is supported as display currency. All payments happen in BCH on chain.', 'ekliptor'),
						);
						$notice = new CTIP_AdminNotice($tpl->getTemplate('adminNotice.php', $tplVars), 'error');
						$defaultSanitizer->addAdminNotices($notice);
						return 'USD';
					}
					return $newValue;
				},
				'custom_access_days' => function($newValue, string $settingName) use($tpl, $defaultSanitizer) {
					$newValue = sanitize_text_field($newValue);
					if ($newValue < 2) {
						$tplVars = array(
								'msg' => __('Custom access duration must be at least 2 days.', 'ekliptor')
						);
						$notice = new CTIP_AdminNotice($tpl->getTemplate('adminNotice.php', $tplVars), 'error');
						$defaultSanitizer->addAdminNotices($notice);
						return 2;
					}
					return $newValue;
				},
				'donation_goal' => function($newValue, string $settingName) use($tpl, $defaultSanitizer, $settings) {
					$newValue = $defaultSanitizer->formatFloat($newValue, $settingName);
					return $newValue > 0.0 ? $newValue : $settings->get($settingName);
				},
				'ad_code' => function($newValue, string $settingName) use($tpl, $defaultSanitizer) {
					$newValue = $defaultSanitizer->sanitizeHtml($newValue);
					return $newValue;
				},
				'faucet_bch' => function($newValue, string $settingName) use($tpl, $defaultSanitizer, $settings) {
					$newValue = sanitize_text_field($newValue);
					if (Cashtippr::isValidUrl($newValue) === false) {
						$tplVars = array(
								'msg' => __('Invalid Bitcoin faucet URL.', 'ekliptor')
						);
						$notice = new CTIP_AdminNotice($tpl->getTemplate('adminNotice.php', $tplVars), 'error');
						$defaultSanitizer->addAdminNotices($notice);
						return $settings->get($settingName); // keep the previous value
					}
					return $newValue;
				},
				'faucet_bch_text' => function($newValue, string $settingName) use($tpl, $defaultSanitizer, $settings) {
					$newValue = sanitize_text_field($newValue);
					if (empty($newValue))
						return $settings->get($settingName); // keep the previous value
					return $newValue;
				},
				'session_name' => function($newValue, string $settingName) use($tpl, $defaultSanitizer, $defaults, $settings) {
					$newValue = sanitize_text_field($newValue);
					if (preg_match("/^[a-z]+$/i", $newValue) !== 1) {
						$tplVars = array(
								'msg' => __('The session name can only contain letters a-z.', 'ekliptor')
						);
						$notice = new CTIP_AdminNotice($tpl->getTemplate('adminNotice.php', $tplVars), 'error');
						$defaultSanitizer->addAdminNotices($notice);
						//return $defaults['session_name'];
						return $settings->get('session_name'); // keep the previous value
					}
					return $newValue;
				},
				'use_memcached' => function($newValue, string $settingName) use($tpl, $defaultSanitizer, $defaults, $settings) {
					$newValue = $defaultSanitizer->formatBoolean($newValue, $settingName);
					if ($newValue === false)
						return false;
					if (CTIP_MemcachedSessionHandler::isInstalled() === false) {
						$tplVars = array(
								'msg' => __('Memcached is not installed on your server where WordPress is running. Please install this or contact your hosting provier about it.', 'ekliptor')
						);
						$notice = new CTIP_AdminNotice($tpl->getTemplate('adminNotice.php', $tplVars), 'error');
						$defaultSanitizer->addAdminNotices($notice);
						return $defaults[$settingName]; // default is disabled
					}
					if (CTIP_MemcachedSessionHandler::checkConnection($settings->get('memcached_host'), $settings->get('memcached_port')) === false) {
						$tplVars = array(
								'msg' => __('Unable to connect to the memcached server you entered. Please ensure the memcached daemon is listening on the address you provided and you can access it from your network (firewall settings etc... ).', 'ekliptor')
						);
						$notice = new CTIP_AdminNotice($tpl->getTemplate('adminNotice.php', $tplVars), 'error');
						$defaultSanitizer->addAdminNotices($notice);
						return $defaults[$settingName]; // default is disabled
					}
					return $newValue;
				},
				'blockchain_rest_url' => function($newValue, string $settingName) use($tpl, $defaultSanitizer, $settings) {
					$newValue = sanitize_text_field($newValue);
					if (Cashtippr::isValidUrl($newValue) === false) {
						$tplVars = array(
								'msg' => __('Invalid API backend URL.', 'ekliptor')
						);
						$notice = new CTIP_AdminNotice($tpl->getTemplate('adminNotice.php', $tplVars), 'error');
						$defaultSanitizer->addAdminNotices($notice);
						return $settings->get($settingName); // keep the previous value
					}
					return $newValue;
				}
		);
		$sanitizer = apply_filters('cashtippr_settings_sanitizer', $sanitizer, $defaultSanitizer, $tpl, $defaults, $this);
		foreach ($defaults as $key => $value) {
			if (isset($sanitizer[$key]))
				continue;
			// add a sanitizer based on the type of the default value
			$sanitizer[$key] = $defaultSanitizer->sanitizeByType($defaults[$key]);
		}
		return $sanitizer;
	}
	
	/**
	 * Get the default settings for this plugin.
	 * @param boolean $onUpdate true if we are updating settings via form submit
	 * @return string[]|number[]|boolean[]
	 */
	protected function getDefaultSiteOptions($onUpdate = false) {
		$defaults = array(
				'version' => CASHTIPPR_VERSION,
				
				// Payment
				'bch_address' => '',
				'button_currency' => 'USD',
				'default_amount' => 0.1,
				'tip_txt' => __('Tip the author of this article:', 'ekliptor'),
				'enable_daily_pass' => false,
				'daily_access_price' => 1.0,
				'daily_tip_txt' => __('Please tip the author of this article to get access to all articles on {site_name} for 1 day:', 'ekliptor'),
				'enable_custom_pass' => false,
				'custom_access_days' => 30,
				'custom_access_price' => 15.0,
				'custom_tip_txt' => __('Please tip the author of this article to get access to all articles on {site_name} for {days} days:', 'ekliptor'),
				'author_bch_addresses' => true,
				
				// Content
				'button_posts' => true,
				'button_pages' => false,
				'enable_limit_words' => false,
				'limit_words' => 400,
				'enable_posts_free' => false,
				'posts_free' => 3,
				'hide_tip_txt' => __('Please send a tip to the author to view the hidden text ({words} words):', 'ekliptor'),
				'enable_free_access' => true,
				'free_access_days' => 180,
				'enable_donation_goal' => false,
				'donation_goal' => 50.0,
				'donation_goal_txt' => 'Donate for this post to make it publicly available for everybody:',
				// TODO add text for when goal is reached. this would be better as per post setting to show surpises...
				'show_donations' => false,
				'donation_status_txt' => 'Tips received: {tips}',
				'enable_show_button_categories' => false,
				'enable_free_categories' => false,
				
				// Ads
				'enable_ads_top' => false,
				'ad_code' => '',
				'enable_faucet' => false,
				'faucet_bch' => 'https://free.bitcoin.com/',
				'faucet_bch_text' => __('Click here to get your first free Bitcoin Cash (BCH Facuet)', 'ekliptor'),
				
				// Advanced
				'show_search_engines' => true,
				'show_cookie_consent' => false,
				'cookie_consent_txt' => 'This site uses cookies to improve your user experience.',
				'session_name' => 'ctip',
				'use_memcached' => false,
				'memcached_host' => '127.0.0.1',
				'memcached_port' => 11211,
				'paymentCommaDigits' => 8,
				'tokenDigits' => 8, // for easier compatibility when merging from SLP plugin
				
				// stats
				'tips' => 0,
				'tip_amount' => 0.0,
				'rate_usd_bch' => 0.0,
				
				// Blury Images // TOOD create hook and move to blurry image addon. convenient here because in translation of main plugin
				// an option to blur all images seems overkill? let the user choose
				'hide_image_txt' => __('Please send a tip to the artist to see the clear version:', 'ekliptor'),
				'blurry_cache_mb' => 400,
				'blurry_update_nonce' => Cashtippr::getRandomString(20),
				'blurry_last_update_check' => time(),
				
				// Shout
				'tweet_posts' => false,
				'tweet_posts_prefix' => __('New blog post:', 'ekliptor'),
				'twitter_api_key' => '',
				'twitter_api_secret' => '',
				'twitter_access_token' => '',
				'twitter_token_secret' => '',
				'shout_max_chars' => 280,
				'shout_price' => 0.5,
				'tweet_price' => 1.0,
				'tweet_shout_prefix' => __('Visitor tweet:', 'ekliptor'),
				'shouts_on_page' => 5,
				'shout_word_filter' => '',
				'shout_image_max_size' => '300x300',
				'shout_links' => true,
				'shout_update_nonce' => Cashtippr::getRandomString(20),
				'shout_last_update_check' => time(),
				
				// Woocommerce
				'blockchain_api' => 'BitcoinComRestApi',
				'wait_confirmations' => 3,
				'blockchain_rest_url' => 'https://rest.bitcoin.com/v2/',
				'lastCheckedTransactions' => 0,
				'xPub' => '',
				//'hdPathFormat' => 'm/0/%d',
				'hdPathFormat' => '0/%d',
				'addressCount' => 0,
				'lastAddress' => '', // used for debugging
		);
		$defaults = apply_filters('cashtippr_default_settings', $defaults);
		if ($onUpdate === true) { // html form checkboxes are not present when false, so assume false for all on update
			foreach ($defaults as $name => $value) {
				if ($value === true && is_bool($defaults[$name]) === true) // is_bool() check shouldn't be needed
					$defaults[$name]= false;
			}
		}
		return $defaults;
	}
}
?>