<?php
/**
 * Loads when the plugin is uninstalled
 *
 * Only removes options for the time being.
 * Plugins can use the 'multilocale_before_uninstall_plugin' hook.
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 */

if (
	! defined( 'WP_UNINSTALL_PLUGIN' )
||
	! WP_UNINSTALL_PLUGIN
||
	dirname( WP_UNINSTALL_PLUGIN ) !== dirname( plugin_basename( __FILE__ ) )
) {
	status_header( 404 );
	exit;
}

/**
 * Fires before the plugin uninstall routine runs.
 *
 * @since 0.0.1
 */
do_action( 'multilocale_before_uninstall_plugin' );

/*
 * Remove user preferences.
 */
$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'admin_locale' ) );

/*
 * Remove options.
 *
 * Note: Do this last, the options might be needed for handling other stuff.
 */
$option_names = array(
	'plugin_multilocale',
	'plugin_multilocale_version',
);

foreach ( $option_names as $option ) {
	delete_option( $option_name );
	delete_site_option( $option_name );
}
