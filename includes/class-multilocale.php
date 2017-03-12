<?php
/**
 * Contains the core plugin class.
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 */

/**
 * The core plugin class.
 *
 * @since 0.0.1
 */
class Multilocale {

	/**
	 * Instance of this class.
	 *
	 * @since 0.0.1
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * The name of the taxonomy we're storing locales in.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	public $locale_taxonomy;

	/**
	 * Options page identifier.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	public $options_page;

	/**
	 * The name of the taxonomy we're storing post translations in.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	public $post_translation_taxonomy;

	/**
	 * Initialize this class.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {

		$this->locale_taxonomy = 'locale';
		$this->options_page = 'multilocale-options';
		$this->post_translation_taxonomy = 'post_translation';

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

		add_action( 'widgets_init', array( $this, 'unregister_widgets' ) );
	}

	/**
	 * Unregister unsupported core sidebar widgets.
	 *
	 * Most core widgets won't work as expected when using this plugin.
	 * Let's deactivate them for the time being.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 */
	public function unregister_widgets() {
		unregister_widget( 'WP_Widget_Archives' );
		unregister_widget( 'WP_Widget_Calendar' );
		unregister_widget( 'WP_Widget_Categories' );
		unregister_widget( 'WP_Widget_Pages' );
		unregister_widget( 'WP_Widget_Recent_Comments' );
		unregister_widget( 'WP_Widget_Recent_Posts' );
		unregister_widget( 'WP_Widget_Tag_Cloud' );
	}

	/**
	 * Get current locale object.
	 *
	 * @since 0.0.1
	 * @return false|WP_Term Locale taxonomy term object or false if no locales defined.
	 */
	public function get_current_locale_object() {

		if ( is_admin() ) {

			return multilocale_locale()->get_default_locale();

		} else {

			global $wp_locale_switcher;

			if ( $wp_locale_switcher ) {

				// Equivalent of $wp_locale_switcher->is_switched() but with returned value if switched.
				$switched_locale = $wp_locale_switcher->filter_locale( false );

				if ( $switched_locale ) {

					$locales = multilocale_get_locales();

					foreach ( $locales as $locale ) {
						if ( $switched_locale === $locale->description ) {
							return $locale;
							break;
						}
					}
				}
			} else {

				return multilocale_locale()->locale_obj;
			}
		}
	}
}
