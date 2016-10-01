<?php
/**
 * Contains the general admin functionality plugin class.
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 */

/**
 * Class for general admin functionality.
 *
 * Adds option page, query vars, modifies permalink option page.
 *
 * @since 0.0.1
 */
class Multilocale_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since 0.0.1
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Locale taxonomy name.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	private $_locale_taxonomy;

	/**
	 * Options page identifier.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	private $_options_page;

	/**
	 * Initialize class.
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		$multilocale = multilocale();

		$this->_locale_taxonomy = $multilocale->locale_taxonomy;
		$this->_options_page = $multilocale->options_page;

		$this->add_actions_and_filters();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 0.0.1
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Add actions and filters.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 */
	private function add_actions_and_filters() {

		// Register query vars.
		add_filter( 'query_vars', array( $this, 'filter_query_vars' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . 'multilocale.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_plugin_action_link' ) );

		// Filter the locale so we show the admin in the locale set in the user preferences.
		add_filter( 'locale', array( $this, 'filter_admin_locale' ) );

		// Prevent setting the permalink option to 'Plain'.
		add_action( 'admin_footer', array( $this, 'disable_plain_permalink_option' ) );
		add_filter( 'pre_update_option_permalink_structure', array( $this, 'pre_update_option_permalink_structure' ), 10, 2 );

		add_action( 'admin_notices', array( $this, 'options_page_admin_notices' ) );
	}

	/**
	 * Register query vars.
	 *
	 * Registers the locale_id (used for filtering posts in the admin) and translation_id
	 * (used for assigning post translations for the locale tabs) query vars.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @param array $query_vars The array of whitelisted query variables.
	 * @return array Modified array of query vars.
	 */
	public function filter_query_vars( $query_vars ) {
		$query_vars[] = $this->_locale_taxonomy . '_id';
		$query_vars[] = 'translation_id';
		return $query_vars;
	}

	/**
	 * Register the administration menu item for this plugin.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 */
	public function add_admin_menu() {

		$this->_options_page_hook = add_options_page(
			__( 'Locales', 'multilocale' ),
			__( 'Locales', 'multilocale' ),
			'manage_options',
			$this->_options_page,
			array( $this, 'display_options_page' )
		);
	}

	/**
	 * Display the options page for this plugin.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 */
	public function display_options_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $multilocale_locales;

		$multilocale = Multilocale::get_instance();
		$active_locales = $multilocale_locales->get_locales();

		if ( empty( $active_locales ) ) {
			include_once( MULTILOCALE_PLUGIN_DIR . 'admin/templates/page-setup.php' );
		} else {
			include_once( MULTILOCALE_PLUGIN_DIR . 'admin/templates/page-options.php' );
		}
	}

	/**
	 * Add a plugin settings link to the plugins page.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @param array $links An array of action links.
	 * @return array Modified array of action links.
	 */
	public function add_plugin_action_link( $links ) {

		if ( current_user_can( 'manage_options' ) ) {
			$link = sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'options-general.php?page=' . $this->_options_page ),
				esc_html__( 'Settings', 'multilocale' )
			);
			$links = array_merge( array( 'settings' => $link ), $links );
		}

		return $links;
	}

	/**
	 * Filter admin locale.
	 *
	 * Switch the admin locale depending on user preference.
	 *
	 * @since  0.0.1
	 *
	 * @access private
	 * @param string $locale The locale ID.
	 * @return string Locale, eg. 'de_DE'.
	 */
	public function filter_admin_locale( $locale ) {

		$user_admin_locale = get_user_meta( get_current_user_id(), 'admin_locale', true );

		if ( ! empty( $user_admin_locale ) ) {
			return $user_admin_locale;
		}

		return $locale;
	}

	/**
	 * Disable the "Plain" permalinks option on the 'Permalink Settings' admin page.
	 *
	 * @todo Move to external JavaScript and css file, add localized string via wp_localize_script().
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 */
	public function disable_plain_permalink_option() {

		$screen = get_current_screen();

		if ( 'options-permalink' === $screen->id  && ! empty( get_option( 'permalink_structure' ) ) ) {

			printf(
				'
				<!-- Plugin Multilocale -->
				<script type="text/javascript">
				//<![CDATA[
				jQuery( document ).ready( function( $ ) {
					var $input = $( "%s" );
					$input.attr( "disabled", "disabled" );
					$input.closest( "tr" ).find( "td" ).append( " %s" );
				});
				//]]>
				</script>
				<!-- Fall back to css if JavaScript inactive -->
				<style>
				.no-js .permalink-structure tr:first-of-type { display: none; }
				</style>
				<!-- End Plugin Multilocale -->',
				".permalink-structure input[name='selection'][value='']",
				sprintf(
					" <span class='description'>%s</span>",
					esc_html__( 'Plain permalinks are not supported by the Multilocale plugin.', 'multilocale' )
				)
			);
		}
	}

	/**
	 * Disallow changing the permalink_structure option to "Plain".
	 *
	 * Uses the 'pre_update_option_' hook documented in wp-includes/option.php.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @param mixed $new_value The new, unserialized option value.
	 * @param mixed $old_value The old option value.
	 * @return mixed The old value if the new value is an empty string.
	 */
	public function pre_update_option_permalink_structure( $new_value, $old_value ) {

		if ( empty( $new_value )  ) {
			return $old_value;
		}

		return $new_value;
	}

	/**
	 * Maybe render admin notices on the plugin options page.
	 *
	 * Displays an admin notice on the plugin options page if:
	 * - Current WordPress version is lower than 4.4.
	 * - Pretty permalinks are disabled.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 */
	public function options_page_admin_notices() {

		$current_screen = get_current_screen();

		if ( $current_screen && 'settings_page_' . $this->_options_page === $current_screen->base ) {

			if ( ! function_exists( 'add_term_meta' ) ) {
				echo $this->get_admin_notice_wp_version_requirement(); // WPCS: XSS ok.
			}

			if ( ! get_option( 'permalink_structure' ) ) {
				echo $this->get_admin_notice_permalink_requirement(); // WPCS: XSS ok.
			}
		}
	}

	/**
	 * Return admin notice html, informing the user about the minimum WordPress version required by the plugin.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @return string Admin notice html.
	 */
	private function get_admin_notice_wp_version_requirement() {

		$allowed_tags = array(
			'div' => array(),
			'p'   => array(),
			'a'   => array( 'href' ),
		);

		if ( current_user_can( 'update_core' ) ) {
			$msg = sprintf( __( 'Multilocale requires WordPress 4.4 or higher. <a href="%s">Update</a>', 'multilocale' ), admin_url( 'update-core.php' ) );
		} else {
			$msg = sprintf( __( 'Multilocale requires WordPress 4.4 or higher. Please notify the site administrator.', 'multilocale' ), $cur->current );
		}

		return wp_kses( '<div class="error"><p>' . $msg . '</p></div>', $allowed_tags );
	}

	/**
	 * Return admin notice html, informing the user about the pretty permalinks requirement.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @return string Admin notice html.
	 */
	private function get_admin_notice_permalink_requirement() {

		$allowed_tags = array(
			'div' => array(),
			'p'   => array(),
			'a'   => array( 'href' ),
		);

		if ( current_user_can( 'manage_options' ) ) {
			// Wording shamelessly borrowed from the 'Babble' plugin (http://babbleplugin.com/).
			$msg = sprintf(
				__( 'Pretty permalinks are disabled. <a href="%s">Please enable them</a> in order to have language prefixed URLs work correctly.', 'multilocale' ),
				admin_url( '/options-permalink.php' )
			);
		} else {
			$msg = __( 'Pretty permalinks must be enabled in order to have language prefixed URLs work correctly. Please notify the site administrator.', 'multilocale' );
		}

		return wp_kses( '<div class="error"><p>' . $msg . '</p></div>', $allowed_tags );
	}
}

global $multilocale_admin;
$multilocale_admin = Multilocale_Admin::get_instance();
