<?php
/**
 * Main plugin file
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 *
 * Plugin Name: Multilocale
 * Plugin URI:  https://github.com/barryceelen/wp-multilocale
 * Description: Publish content in multiple locales.
 * Version:     0.0.3
 * Author:      Barry Ceelen
 * Author URI:  https://barryceelen.github.com/
 * Text Domain: multilocale
 * License:     GPLv3+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 */

// Don't load directly.
defined( 'ABSPATH' ) or die();

define( 'MULTILOCALE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MULTILOCALE_PLUGIN_URL', plugins_url( '/', __FILE__ ) );

/**
 * Runs when the plugin is activated.
 *
 * @since 0.0.1
 */
function multilocale_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-activator.php';
	Multilocale_Activator::activate( plugin_basename( __FILE__ ) );
}

/**
 * Runs when the plugin is deactivated.
 *
 * Note: When uninstalling the plugin, the uninstall.php file
 * in the plugin directory is called.
 *
 * @since 0.0.1
 */
function multilocale_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-activator.php';
	Multilocale_Activator::deactivate();
}

register_activation_hook( __FILE__, 'multilocale_activate' );
register_deactivation_hook( __FILE__, 'multilocale_deactivate' );

if ( ! is_admin() ) {

	require_once MULTILOCALE_PLUGIN_DIR . 'public/class-public-locale.php';

	/**
	 * Determine and set the requested locale depending on the url prefix.
	 *
	 * @since 0.0.1
	 *
	 * @return Multilocale_Public_Locale Instance of the public locale class.
	 */
	function multilocale_locale() {
		return Multilocale_Public_Locale::get_instance();
	}
	multilocale_locale();
}

require_once MULTILOCALE_PLUGIN_DIR . 'includes/wpcomvip.php';
require_once MULTILOCALE_PLUGIN_DIR . 'includes/class-multilocale.php';

/**
 * Return an instance of the core plugin class.
 *
 * @since 0.0.1
 *
 * @return Multilocale Instance of the core plugin class.
 */
function multilocale() {
	return Multilocale::get_instance();
}
multilocale();

require_once MULTILOCALE_PLUGIN_DIR . 'includes/class-locales.php';
require_once MULTILOCALE_PLUGIN_DIR . 'includes/class-posts.php';

if ( is_admin() ) {
	require_once MULTILOCALE_PLUGIN_DIR . 'admin/class-admin.php';
	require_once MULTILOCALE_PLUGIN_DIR . 'admin/class-admin-locales.php';
	require_once MULTILOCALE_PLUGIN_DIR . 'admin/class-admin-posts.php';
	require_once MULTILOCALE_PLUGIN_DIR . 'admin/class-admin-meta.php';
} else {
	require_once MULTILOCALE_PLUGIN_DIR . 'public/class-public.php';
	require_once MULTILOCALE_PLUGIN_DIR . 'public/class-public-posts.php';
}

require_once( MULTILOCALE_PLUGIN_DIR . 'includes/api.php' );
