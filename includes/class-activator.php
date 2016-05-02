<?php
/**
 * Contains plugin activate and deactivate functionality
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 */

/**
 * Activate and deactivate functionality.
 *
 * @since 0.0.1
 */
class Multilocale_Activator {

	/**
	 * Fires on plugin activation.
	 *
	 * @since 0.0.1
	 *
	 * @param string $plugin plugin_basename( __FILE__ ) of base plugin file.
	 * @return void
	 */
	public static function activate( $plugin ) {
		self::compatibility_check( $plugin );
		self::maybe_set_default_options();
		do_action( 'multilocale_activate' );
	}

	/**
	 * Prevent activation if 'add_post_meta' function not present.
	 *
	 * @since 0.0.1
	 *
	 * @param string $plugin plugin_basename( __FILE__ ) of base plugin file.
	 * @return void
	 */
	public static function compatibility_check( $plugin ) {
		if ( ! function_exists( 'add_term_meta' ) ) {
			wp_die( esc_html( __( 'Multilocale requires WordPress 4.4 or higher.', 'multilocale' ) ) );
			deactivate_plugins( $plugin );
		}
	}

	/**
	 * Fires on plugin deactivation.
	 *
	 * @since 0.0.1
	 */
	public static function deactivate() {
		do_action( 'multilocale_deactivate' );
	}

	/**
	 * Set default plugin options.
	 *
	 * Adds an option with the options version, useful if
	 * the options structure changes or we introduce new ones.
	 *
	 * @since 0.0.1
	 */
	public static function maybe_set_default_options() {

		$options_version = '0.0.1';
		$options  = get_option( 'plugin_multilocale' );
		$defaults = array(
			'default_locale' => '',
		);

		if ( empty( $options ) ) {
			update_option( 'plugin_multilocale', $defaults );
			update_option( 'plugin_multilocale_options_version', $options_version, false );
		}
	}
}
