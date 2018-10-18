<?php
/**
 * CashTippr Uninstall
 *
 * Uninstall and delete all stored session & payment data from all users.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once (plugin_dir_path ( __FILE__ ) . 'data.php');

class CashtipprUninstall {
	public function __construct() {
	}
	
	public function uninstall() {
		global $wpdb, $wp_version;
		
		// Only remove all user session + payment data if this is set to true.
		// This is to prevent data loss when deleting the plugin from the backend
		// and to ensure only the site owner can perform this action.
		if (CashtipprData::REMOVE_ALL_DATA !== true)
			return;
		
		//wp_clear_scheduled_hook( 'woocommerce_scheduled_sales' );
		
		//$table = Cashtippr::getTableName('sessions'); // we don't have that class loaded
		$tables = get_option('cashtippr_tables', array());
		foreach ($tables as $table) {
			$wpdb->query( "DROP TABLE IF EXISTS $table" );
		}
		
		delete_option('cashtippr_tables');
		delete_option('cashtippr_settings'); // TODO load settings class in case that option name has been changed (currently not possible)
		delete_option('cashtippr_memcached_secret');
		delete_option('cashtippr_version');
		
		$attributes = array('tipAmount');
		$attributesStr = "'" . implode("', '", $attributes) . "'";
		$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ($attributesStr)");
		
		// No post data to delete. The shortcode will be kept in posts, it just won't display anything anymore.
		
		// Clear any cached data that has been removed.
		wp_cache_flush();
	}
}

$uninstall = new CashtipprUninstall();
$uninstall->uninstall();
