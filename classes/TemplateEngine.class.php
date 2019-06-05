<?php
class CTIP_TemplateEngine {
	/** @var CTIP_Settings */
	protected  $settings;
	
	/** @var string */
	protected static  $dateFormat = '';
	/** @var string */
	protected static $timeFormat = '';
	
	protected $adminNotices = array();
	
	/** @var array Keep track of all checkboxes. In HTML forms they are not present on submit when unchecked. */
	protected $allCheckboxes = array();
	
	public function __construct(CTIP_Settings $settings) {
		$this->settings = $settings;
	}
	
	public function showMetaboxPayment($post = null, $args = array()) {
		$bchAddressPlaceholder = __("your BCH address...", 'ekliptor');
		$btnCurrencyPlaceholder = __("the currency to display to your users...", 'ekliptor');
		$pricePlaceholder = __("the price in your selected button currency...", 'ekliptor');
		$defAmountPlaceholder = __("the preset amount users can tip...", 'ekliptor');
		$tipTextPlaceholder = __("the text shown to users on your website...", 'ekliptor');
		$customPassPlaceholder = __("the number of days users can buy access...", 'ekliptor');
    	include CASHTIPPR__PLUGIN_DIR . 'tpl/admin/metaboxPayment.php';
    }
    
    public function showMetaboxContent($post = null, $args = array()) {
		$freeAccessPlaceholder = __("number of days...", 'ekliptor');
		$donationGoalPlaceholder = __("min amount of money...", 'ekliptor');
		$limitWordsPlaceholder = __("number of characters that will be shown...", 'ekliptor');
		$postsFreePlaceholder = __("number of posts new users can view...", 'ekliptor');
		$tipTextPlaceholder = __("the text shown to users on your website...", 'ekliptor');
		$progressTextPlaceholder = __("the text before the progress bar...", 'ekliptor');
		$donationsTextPlaceholder = __("text for the amount of current donations on every post...", 'ekliptor');
    	include CASHTIPPR__PLUGIN_DIR . 'tpl/admin/metaboxContent.php';
    }
    
    public function showMetaboxAds($post = null, $args = array()) {
		$adPlaceholder = __("The banner HTML or JavaScript code of your ad...", 'ekliptor');
		$faucetPlaceholder = __("http://your-bitcoin-faucet ...", 'ekliptor');
		$faucetTextPlaceholder = __("describe your faucet or referral link  ...", 'ekliptor');
    	include CASHTIPPR__PLUGIN_DIR . 'tpl/admin/metaboxAds.php';
    }
    
    public function showMetaboxAdvanced($post = null, $args = array()) {
    	$cookiePlaceholder = __("your cookie consent text to your visitors...", 'ekliptor');
		$sessionPlaceholder = __("session name key...", 'ekliptor');
		$memHostPlaceholder = __("the hostname of your memcached server...", 'ekliptor');
		$memPortPlaceholder = __("the port of your memcached server...", 'ekliptor');
		$apiPlaceholder = __("https://...", 'ekliptor');
    	include CASHTIPPR__PLUGIN_DIR . 'tpl/admin/metaboxAdvanced.php';
    }
    
    public function showMetaboxAddons($post = null, $args = array()) {
    	$pluginBoxes = isset($args['args']) && !empty($args['args']) ? $args['args'][0] : array();
    	include CASHTIPPR__PLUGIN_DIR . 'tpl/admin/metaboxAddons.php';
    }
    
    public function showEditorMetaFields(WP_User $user) {
    	$bchAddressPlaceholder = __('BCH address to receive tips for posts directly...', 'ekliptor');
    	include CASHTIPPR__PLUGIN_DIR . 'tpl/admin/userProfile.php';
    }
    
    /**
     * Adds an admin notice to be shown.
     * @param CTIP_AdminNotice $notice
     */
    public function addAdminNotices(CTIP_AdminNotice $notice) {
    	$this->adminNotices[] = $notice;
    }
    
    /**
     * Display all admin notices added with addAdminNotices(). Must be called from
     * the 'admin_notices' action.
     */
    public function showAdminNotices() {
    	static $shown = false;
    	if ($shown === true)
    		return; // prevent againt queuing this function as multiple actions
    	$shown = true;
    	foreach ($this->adminNotices as $notice) {
    		$this->showAdminNotice($notice);
    	}
    }
    
    /**
     * Show a notice at the top in the admin panel.
     * @param CTIP_AdminNotice $notice The notice to show.
     * @return string|void The html scting with the notice or nothing if $echo is true
     */
    public function showAdminNotice(CTIP_AdminNotice $notice) {
    	$notice->print();
    }
    
    /**
     * Returns a html template file as string.
     * @param string $templateFile The file name relative to the plugin's "tpl" directory.
     * @param array $tplVars associative array with optional additional variables
     * @return string the html
     */
    public function getTemplate(string $templateFile, array $tplVars = array()): string {
    	ob_start();
    	include CASHTIPPR__PLUGIN_DIR . 'tpl/' . $templateFile;
    	$html = ob_get_contents();
    	ob_end_clean();
    	return $html;
    }
	
	/**
	 * Helper function that constructs name attributes for use in form fields.
	 *
	 * Other page implementation classes may wish to construct and use a
	 * get_field_id() method, if the naming format needs to be different.
	 *
	 * @param string $name Field name base
	 * @return string Full field name
	 */
	public function getFieldName( $name ) {
		return sprintf( '%s[%s]', $this->settings->settingsField, $name );
	}

	/**
	 * Echo constructed name attributes in form fields.
	 *
	 * @param string $name Field name base
	 */
	public function fieldName( $name ) {
		echo \esc_attr( $this->getFieldName( $name ) );
	}

	/**
	 * Helper function that constructs id attributes for use in form fields.
	 *
	 * @param string $id Field id base
	 * @return string Full field id
	 */
	public function getFieldId( $id ) {
		return sprintf( '%s[%s]', $this->settings->settingsField, $id );
	}

	/**
	 * Echo constructed id attributes in form fields.
	 *
	 * @param string $id Field id base.
	 * @param boolean $echo Whether to escape echo or just return.
	 * @return string Full field id
	 */
	public function fieldId( $id, $echo = true ) {

		if ( $echo ) {
			echo \esc_attr( $this->getFieldId( $id ) );
		} else {
			return $this->getFieldId( $id );
		}
	}

	/**
	 * Helper function that returns a setting value from this form's settings
	 * field for use in form fields.
	 * Fetches blog option.
	 *
	 * @param string $key Field key
	 * @return string Field value
	 */
	public function getFieldValue( $key ) {
		return $this->getOption( $key/*, $this->settings->settingsField*/ );
	}

	/**
	 * Echo a setting value from this form's settings field for use in form fields.
	 *
	 * @param string $key Field key
	 */
	public function fieldValue( $key ) {
		echo \esc_attr( $this->getFieldValue( $key ) );
	}
	
	/**
	 * Helper function that returns a user's setting value.
	 * @param string $key the setting name
	 * @param int $userID
	 * @param bool $single
	 * @return mixed|boolean|string|array
	 */
	public function getUserFieldValue( string $key, int $userID, bool $single = true ) {
		return get_user_meta($userID, $key, $single);
	}
	
	/**
	 * Echo a user's setting value
	 * @param string $key the setting name
	 * @param int $userID
	 * @param bool $single
	 */
	public function userFieldValue( string $key, int $userID, bool $single = true ) {
		echo \esc_attr( $this->getUserFieldValue( $key, $userID, $single ) );
	}
	
	/**
	 * Return plugin options from the plugin options database.
	 *
	 * @param string  $key       Option name.
	 * @param boolean $use_cache Optional. Whether to use the cache value or not. Defaults to true.
	 * @return mixed The value of this $key in the database.
	 */
	public function getOption( $key, $use_cache = true ) {
		return $this->settings->get($key, $use_cache);
	}
	
	/**
	 * Echo or return a chechbox fields wrapper.
	 *
	 * @param string $input The input to wrap. Should already be escaped.
	 * @param boolean $echo Whether to escape echo or just return.
	 * @return string|void Wrapped $input.
	 */
	public function wrapFields( $input = '', $echo = false ) {

		if ( is_array( $input ) )
			$input = implode( PHP_EOL, $input );

		if ( $echo ) {
			//* Already escaped.
			echo '<div class="ct-fields">' . $input . '</div>';
		} else {
			return '<div class="ct-fields">' . $input . '</div>';
		}
	}
	
	/**
	 * Returns a chechbox wrapper.
	 *
	 * @param string $field_id    The option ID. Must be within the plugin settings.
	 * @param string $label       The checkbox description label.
	 * @param string $description Addition description to place beneath the checkbox.
	 * @param bool   $escape      Whether to escape the label and description.
	 * @param bool   $disabled    Whether to disable the input.
	 * @return string HTML checkbox output.
	 */
	public function makeCheckbox( $field_id = '', $label = '', $description = '', $escape = true, $disabled = false ) {
		$this->allCheckboxes[$field_id] = true;
		if ( $escape ) {
			$description = \esc_html( $description );
			$label = \esc_html( $label );
		}

		$description = $description ? '<p class="description ct-option-spacer">' . $description . '</p>' : '';

		$output = '<span class="ct-toblock">'
					. '<label for="'
						. $this->getFieldId( $field_id ) . '" '
						. ( $disabled ? 'class=ct-disabled ' : '' )
					. '>'
						. '<input '
							. 'type="checkbox" '
							. ( $disabled ? 'class=ct-disabled disabled ' : '' )
							. 'name="' . $this->getFieldName( $field_id ) . '" '
							. 'id="' . $this->getFieldId( $field_id ) . '" '
							. ( $disabled ? '' : $this->getIsConditionalChecked( $field_id ) . ' ' )
							. 'value="1" '
							. \checked( $this->getFieldValue( $field_id ), true, false ) .
						' />'
						. $label
					. '</label>'
				. '</span>'
				. $description
				;

		return $output;
	}

	/**
	 * Return a text field wrapper.
	 * 
	 * @param string $field_id The option ID. Must be within the plugin settings.
	 * @param string $label The checkbox description label.
	 * @param string $description Addition description to place beneath the checkbox.
	 * @param string $placeholder The text field placeholder.
	 * @param bool $escape Whether to escape the label and description.
	 * @return string HTML text field output.
	 */
	public function makeTextfield( $field_id = '', $label = '', $description = '', $placeholder = '', $escape = true ) {

		if ( $escape ) {
			$description = \esc_html( $description );
			$label = \esc_html( $label );
			$placeholder = \esc_attr( $placeholder );
		}

		$description = $description ? '<p class="description ct-option-spacer">' . $description . '</p>' : '';

		$output = '<p>'
					. '<label for="' . $this->fieldId( $field_id ) . '" class="ct-toblock">'
						. '<strong>' . $label . '</strong>'
					. '</label>'
				. '</p>'
				. '<p>'
					. '<input type="text" name="' . $this->fieldName( $field_id ) . '"'
						. ' class="large-text" id="' . $this->fieldId( $field_id ) . '"'
						. ' placeholder="' . $placeholder . '"'
						. ' value="' . $this->getFieldValue( $field_id ) . '"'
					. ' />'
				. '</p>'
				. $description
				;

		return $output;
	}

	/**
	 * Return a wrapped question mark.
	 *
	 * @param string $description The descriptive on-hover title.
	 * @param string $link The non-escaped link.
	 * @param bool $echo Whether to echo or return.
	 * @return string HTML checkbox output if $echo is false.
	 */
	public function makeInfo( $description = '', $link = '', $echo = true ) {

		if ( $link ) {
			$output = sprintf(
				'<a href="%1$s" class="ct-tooltip-item" target="_blank" rel="nofollow noreferrer noopener" title="%2$s" data-desc="%2$s">[?]</a>',
				\esc_url( $link, array( 'http', 'https' ) ),
				\esc_attr( $description )
			);
		} else {
			$output = sprintf(
				'<span class="ct-tooltip-item" title="%1$s" data-desc="%1$s">[?]</span>',
				\esc_attr( $description )
			);
		}

		$output = sprintf( '<span class="ct-tooltip-wrap">%s</span>', $output );

		if ( $echo ) {
			//* Already escaped.
			echo $output;
		} else {
			return $output;
		}
	}
	
	/**
	 * Mark up content in description wrap.
	 * Escapes all HTML, so `<` gets changed to `&lt;` and displays correctly.
	 *
	 * @param string $content Content to be wrapped in the description wrap.
	 * @param bool $block Whether to wrap the content in <p> tags.
	 * @return string Content wrapped int he description wrap.
	 */
	public function description( $content, $block = true ) {
		$this->descriptionNoesc( \esc_html( $content ), $block );
	}

	/**
	 * Mark up content in description wrap.
	 *
	 * @param string $content Content to be wrapped in the description wrap. Expected to be escaped.
	 * @param bool $block Whether to wrap the content in <p> tags.
	 * @return string Content wrapped int he description wrap.
	 */
	public function descriptionNoesc( $content, $block = true ) {
		$output = '<span class="description">' . $content . '</span>';
		echo $block ? '<p>' . $output . '</p>' : $output;
	}
	
	/**
	 * Helper function that constructs id attributes for use in form fields.
	 *
	 * @param string $key The option name which returns boolean.
	 */
	public function getIsConditionalChecked( $key ) {
		return $this->isConditionalChecked( $key, $this->settings->settingsField, true, false );
	}

	/**
	 * Returns the HTML class wrap for warning/default Checkbox options.
	 *
	 * This function does nothing special. But is merely a simple wrapper.
	 * Just like code_wrap.
	 *
	 * @param string $key required The option name which returns boolean.
	 * @param string $setting optional The settings field
	 * @param bool $wrap optional output class="" or just the class name.
	 * @param bool $echo optional echo or return the output.
	 *
	 * @return string Empty on echo or The class with an optional wrapper.
	 */
	public function isConditionalChecked( $key, $setting = '', $wrap = true, $echo = true ) {
		$class = '';
		$default = $this->isDefaultChecked( $key, $setting, false, false );
		$warned = $this->isWarningChecked( $key, $setting, false, false );

		if ( '' !== $default && '' !== $warned ) {
			$class = $default . ' ' . $warned;
		} elseif ( '' !== $default ) {
			$class = $default;
		} elseif ( '' !== $warned ) {
			$class = $warned;
		}

		if ( $echo ) {
			if ( $wrap ) {
				printf( 'class="%s"', \esc_attr( $class ) );
			} else {
				echo \esc_attr( $class );
			}
		} else {
			if ( $wrap )
				return sprintf( 'class="%s"', $class );

			return $class;
		}
	}
	
	/**
	 * Returns the HTML class wrap for default Checkbox options.
	 *
	 * This function does nothing special. But is merely a simple wrapper.
	 * Just like code_wrap.
	 *
	 * @param string $key required The option name which returns boolean.
	 * @param string $setting optional The settings field
	 * @param bool $wrap optional output class="" or just the class name.
	 * @param bool $echo optional echo or return the output.
	 */
	public function isDefaultChecked( $key, $setting = '', $wrap = true, $echo = true ) {
		$class = '';
		$default = $this->settings->getDefaultSettings( $key, $setting );

		if ( 1 === $default )
			$class = 'ct-default-selected';

		if ( $echo ) {
			if ( $wrap ) {
				printf( 'class="%s"', \esc_attr( $class ) );
			} else {
				echo \esc_attr( $class );
			}
		} else {
			if ( $wrap )
				return sprintf( 'class="%s"', $class );

			return $class;
		}
	}

	/**
	 * Returns the HTML class wrap for warning Checkbox options.
	 *
	 * @param string $key required The option name which returns boolean.
	 * @param string $setting optional The settings field
	 * @param bool $wrap optional output class="" or just the class name.
	 * @param bool $echo optional echo or return the output.
	 * @return string Empty on echo or The class with an optional wrapper.
	 */
	public function isWarningChecked( $key, $setting = '', $wrap = true, $echo = true ) {
		$class = '';
		$warned = $this->settings->getWarnedSettings( $key, $setting );

		if ( 1 === $warned )
			$class = 'ct-warning-selected';

		if ( $echo ) {
			if ( $wrap ) {
				printf( 'class="%s"', \esc_attr( $class ) );
			} else {
				echo \esc_attr( $class );
			}
		} else {
			if ( $wrap )
				return sprintf( 'class="%s"', $class );

			return $class;
		}
	}
	
	/**
	 * Return the names of all checkboxes in the current HTML form.
	 * @param bool $asString
	 * @return array|string
	 */
	public function getAllCheckboxes(bool $asString) { // TODO make this compatible with multi-level form fields, see getFieldName()
		if ($asString === false)
			return $this->allCheckboxes;
		return implode(',', array_keys($this->allCheckboxes));
	}
	
	public static function getDate(int $unixTimestamp, bool $echo = true) {
		if (static::$dateFormat === '')
			static::$dateFormat = get_option( 'date_format' );
		$date = date_i18n(static::$dateFormat, $unixTimestamp);
		if ($echo === false)
			return $date;
		echo $date;
	}
	
	public static function getTime(int $unixTimestamp, bool $echo = true) {
		if (static::$timeFormat === '')
			static::$timeFormat = get_option( 'time_format' );
		$date = date_i18n(static::$timeFormat, $unixTimestamp);
		if ($echo === false)
			return $date;
		echo $date;
	}
}
?>