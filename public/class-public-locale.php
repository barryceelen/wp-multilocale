<?php
/**
 * Contains public facing locale class
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 */

/**
 * Sets the requested locale depending on the url prefix.
 *
 * @since 0.0.1
 */
class Multilocale_Public_Locale {

	/**
	 * Instance of this class.
	 *
	 * @since 0.0.1
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Site home url.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @var string
	 */
	private $_home_url;

	/**
	 * WP Locale.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @var string
	 */
	private $_locale;

	/**
	 * Locale object.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @var string
	 */
	public $locale_obj;

	/**
	 * Modified request URI.
	 *
	 * If $_SERVER['request_uri'] contains a locale slug, we'll shamelessly change it.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @var string
	 */
	private $_modified_request_uri = false;

	/**
	 * Plugin options.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @var array
	 */
	private $_options;

	/**
	 * Initialize the class.
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		// Prevent error if loaded as mu-plugin.
		if ( wp_installing() ) {
			return;
		}

		// No locales? Stop that train, I'm leavin'.
		if ( empty( $this->get_locales() ) ) {
			return;
		}

		$this->_home_url = is_multisite() ? network_home_url( '/' ) : home_url( '/' );
		$this->_options  = get_option( 'plugin_multilocale' );

		$this->init();
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
	 * Let's get this party started.
	 *
	 * Hackety-hack. A quick and dirty way to determine the locale by looking at the requested URL that will
	 * most likely fail in many ways. Sets $this->_locale and *yikes* maybe modifies $_SERVER['REQUEST_URI'].
	 *
	 * @todo Use filter on permalinks or add our own in stead, look at Babble/Polylang/qTranslate for inspiration?
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 *
	 * @global bool $is_IIS
	 */
	private function init() {

		// Temporary. Should we just go ahead and save default locale in WP_LOCALE?
		$locales = wp_list_pluck( $this->get_locales(), 'description', 'term_id' );

		if ( ! empty( $this->_options['default_locale_id'] ) && array_key_exists( $this->_options['default_locale_id'], $locales ) ) {
			$this->_locale = $locales[ $this->_options['default_locale_id'] ];
		} else {
			$this->_locale = get_locale();
		}

		// Get locales as an array where slug is key and term_id is value.
		$locales = wp_list_pluck( $this->get_locales(), 'term_id', 'slug' );

		// Get request URI minus the home URL (which is not necessarily just the host).
		if ( ! isset( $_SERVER['HTTP_HOST'] ) || ! isset( $_SERVER['REQUEST_URI'] ) ) { // WPCS: input var ok.
			return;
		}

		$str = is_ssl() ? 'https://' : 'http://';
		$str .= wp_unslash( $_SERVER['HTTP_HOST'] );   // WPCS: sanitization ok, input var ok.
		$str .= wp_unslash( $_SERVER['REQUEST_URI'] ); // WPCS: sanitization ok, input var ok.

		$str = str_replace( $this->_home_url, '', $str );

		// Turn that into an array so we can peek at the first element.
		$req_uri_array = explode( '/', trim( $str, '/' ) );

		// If there is a first element and it is present in our locales, let's do something with it.
		if ( $req_uri_array[0] && array_key_exists( $req_uri_array[0], $locales ) ) {

			// Save our slug in a variable for later use.
			$slug = $req_uri_array[0];

			// Remove the first element from our array.
			array_shift( $req_uri_array );

			if ( (int) $this->_options['default_locale_id'] === (int) $locales[ $slug ] ) {

				// The default locale is not supposed to have a slug, let's just redirect here.
				global $is_IIS;

				if ( ! $is_IIS && PHP_SAPI !== 'cgi-fcgi' ) {
					status_header( 301 ); // This causes problems on IIS and some FastCGI setups.
				}

				$location = $this->_home_url . implode( '/', $req_uri_array );

				header( "Location: $location", true, 301 );
				exit;

			} else {

				// Get locales as an array where slug is key and description
				// is value so we can get the locale by its slug and set it accordingly.
				$array = wp_list_pluck( $this->get_locales(), 'description', 'slug' );

				// Set the locale.
				$this->_locale = $array[ $slug ];

				// Set the modified request uri.
				$this->_modified_request_uri = '/' . implode( '/', $req_uri_array );
			}
		}

		// Set public locale_obj var.
		$this->locale_obj = $this->get_current_locale_object();
	}

	/**
	 * Add actions and filters.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 */
	private function add_actions_and_filters() {
		/*
		 * Remove canonical redirects for partial urls for the time being.
		 * Trac: https://core.trac.wordpress.org/ticket/16557
		 */
		remove_filter( 'template_redirect', 'redirect_canonical' );
		add_filter( 'redirect_canonical', array( $this, 'remove_redirect_guess_404_permalink' ) );

		add_action( 'wp_loaded', array( $this, 'maybe_modify_request_uri' ) );
		add_filter( 'locale', array( $this, 'filter_locale' ) );
	}

	/**
	 * Remove locale slug from REQUEST_URI so WordPress is can parse the request while
	 * oblivious to the hocus pocus we're conducting here.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 */
	public function maybe_modify_request_uri() {
		if ( ! is_admin() && $this->_modified_request_uri ) {
			$_SERVER['REQUEST_URI'] = $this->_modified_request_uri;
		}
	}

	/**
	 * Disable WordPress' URL autocorrection guessing feature.
	 *
	 * @see https://core.trac.wordpress.org/ticket/16557
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @param string $redirect_url The redirect URL.
	 */
	public function remove_redirect_guess_404_permalink( $redirect_url ) {

		if ( is_404() ) {
			return false;
		}

		return $redirect_url;
	}

	/**
	 * Filter locale.
	 *
	 * @since  0.0.1
	 *
	 * @access private
	 * @param string $locale The locale ID.
	 * @return string Locale string, eg. 'de_DE'.
	 */
	public function filter_locale( $locale ) {
		return $this->_locale;
	}

	/**
	 * Get active locales directly from the database and maybe cache the result.
	 *
	 * Note: Duplicate method exists in Multilocale_Locales class which is wrapped in multilocale_get_locales() function.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @return array List of WP_Term objects.
	 */
	private function get_locales() {

		if ( ! $results = wp_cache_get( 'multilocale_locales' ) ) {

			global $wpdb;

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
						SELECT *
						FROM $wpdb->terms AS t1
						LEFT JOIN $wpdb->term_taxonomy AS t2
						ON t1.term_id = t2.term_id
						WHERE t2.taxonomy = '%s'
						ORDER BY t2.description
					",
					'locale' // Todo: How to get this from a variable without saving to options?
				)
			); // WPCS: db call ok.

			if ( count( $results ) ) {
				wp_cache_add( 'multilocale_locales', $results );
			} else {
				$results = array();
			}
		}

		return $results;
	}

	/**
	 * Get the locale term object depending on the current locale.
	 *
	 * @return object|false
	 */
	private function get_current_locale_object() {

		$locales    = $this->get_locales();
		$locale_obj = false;

		foreach ( $locales as $locale ) {
			if ( $this->_locale === $locale->description ) {
				$locale_obj = $locale;
				break;
			}
		}

		return $locale_obj;
	}
}
