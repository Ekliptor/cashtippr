<?php
/*
 * Plugin Name: CashTippr: Bitcoin Cash instant tips & payments for your content
 * Plugin URI: https://cashtippr.com/
 * Description: Earn money for your content using instant Bitcoin Cash tips (0 conf).
 * Version: 1.1.11
 * Author: Ekliptor
 * Author URI: https://twitter.com/ekliptor
 * License: GPLv3
 * Text Domain: ekliptor
 */

use Ekliptor\Cashtippr\DatabaseMigration;

// Make sure we don't expose any info if called directly
if (! defined( 'ABSPATH' )) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit ();
}

define ( 'CASHTIPPR_VERSION', '1.1.11' );
define ( 'CASHTIPPR__MINIMUM_WP_VERSION', '4.7' );
define ( 'CASHTIPPR__PLUGIN_DIR', plugin_dir_path ( __FILE__ ) );

if (PHP_VERSION_ID < 70000) {
	load_plugin_textdomain ( 'ekliptor' );
	$escapeHtml = false;
	$message = '<strong>' . esc_html__ ( 'You need PHP v7.0 or higher to use this plugin.', 'ekliptor' ) . '</strong> ' . esc_html__ ( 'Please update in your hosting provider\'s control panel or contact your hosting provider.', 'ekliptor' );
	include CASHTIPPR__PLUGIN_DIR . 'tpl/message.php';
	exit();
}

register_activation_hook ( __FILE__, array (
		'Cashtippr',
		'plugin_activation' 
) );
register_deactivation_hook ( __FILE__, array (
		'Cashtippr',
		'plugin_deactivation' 
) );

require_once (CASHTIPPR__PLUGIN_DIR . 'data.php');
require_once (CASHTIPPR__PLUGIN_DIR . 'functions.php');
require_once (CASHTIPPR__PLUGIN_DIR . 'classes/Cashtippr.class.php');
require_once (CASHTIPPR__PLUGIN_DIR . 'classes/CashtipprApi.class.php');
require_once (CASHTIPPR__PLUGIN_DIR . 'api.php');
CashtipprApi::getInstance(Cashtippr::getInstance());

DatabaseMigration::checkAndMigrate();

add_action ( 'init', array (
		Cashtippr::getInstance(),
		'init' 
) );


add_action ( 'rest_api_init', array (
		CashtipprApi::getInstance(),
		'init' 
) );

if (is_admin ()/* || (defined ( 'WP_CLI' ) && WP_CLI)*/) {
	require_once (CASHTIPPR__PLUGIN_DIR . 'classes/CashtipprAdmin.class.php');
	CashtipprAdmin::getInstance(Cashtippr::getInstance());
	add_action ( 'init', array (
			CashtipprAdmin::getInstance(),
			'init' 
	) );
}
?>