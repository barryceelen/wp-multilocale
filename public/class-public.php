<?php
/**
 * Contains public facing general functionality class
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 */

/**
 * Public plugin class.
 *
 * @since 0.0.1
 */
class Multilocale_Public {

	/**
	 * Locale object.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	private $_locale_obj;

	/**
	 * Home URL.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	private $_home_url;

	/**
	 * Initialize the class.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {

		$this->_locale_obj = multilocale_locale()->locale_obj;

		if ( empty( $this->_locale_obj ) ) {
			return false;
		}

		$this->_home_url = get_home_url();

		$this->add_actions_and_filters();
	}

	/**
	 * Add actions and filters.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 */
	private function add_actions_and_filters() {

		add_filter( 'home_url', array( $this, 'filter_home_url' ), 10, 2 );
		add_filter( 'option_blogname', array( $this, 'filter_options' ), 10, 2 );
		add_filter( 'option_blogdescription', array( $this, 'filter_options' ), 10, 2 );
		add_filter( 'option_date_format', array( $this, 'filter_options' ), 10, 2 );
		add_filter( 'option_time_format', array( $this, 'filter_options' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'show_translated_page_if_page_on_front' ) );
	}

	/**
	 * Maybe add locale slug to home URL
	 *
	 * @since 0.0.1
	 *
	 * @todo Use user_trailingslashit()?
	 *
	 * @param string $url Home URL.
	 * @return string Home URL including locale slug if the current locale is not the default locale.
	 */
	public function filter_home_url( $url ) {

		if ( ! did_action( 'template_redirect' ) ) { // Thanks, Polylang.
			return $url;
		}

		$options = get_option( 'plugin_multilocale' );

		if ( (int) $options['default_locale_id'] !== (int) $this->_locale_obj->term_id ) {
			$home = trailingslashit( $this->_home_url );
			$url  = trailingslashit( $url );
			$url  = $home . $this->_locale_obj->slug . '/' . str_replace( $home, '', $url );
		}

		return $url;
	}

	/**
	 * Get home url for a locale.
	 *
	 * @since 0.0.1
	 *
	 * @param WP_Term $locale The locale in question.
	 * @return string Home URL for the specified locale.
	 */
	public function get_localized_home_url( $locale ) {

		$url = $this->get_default_home_url();

		if ( multilocale_get_default_locale_id() !== $locale->term_id ) {
			$url = trailingslashit( $url ) . $locale->slug;
		}

		return $url;
	}

	/**
	 * Get unfiltered home URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string Home URL.
	 */
	public function get_default_home_url() {

		remove_filter( 'home_url', array( $this, 'filter_home_url' ), 10 );

		$url = get_home_url();

		add_filter( 'home_url', array( $this, 'filter_home_url' ), 10, 2 );

		return $url;
	}

	/**
	 * Filter locale related options.
	 *
	 * @since 0.0.1
	 *
	 * @param string|array $value  Option value.
	 * @param string       $option Option name.
	 * @return string|array        Localized value.
	 */
	public function filter_options( $value, $option ) {

		if ( ! $this->_locale_obj ) {
			return $value;
		}

		switch ( $option ) {
			case 'blogname' :
				$value = get_term_meta( $this->_locale_obj->term_id, 'blogname', true );
				break;
			case 'blogdescription' :
				$value = get_term_meta( $this->_locale_obj->term_id, 'blogdescription', true );
				break;
			case 'date_format' :
				$value = get_term_meta( $this->_locale_obj->term_id, 'date_format', true );
				break;
			case 'time_format' :
				$value = get_term_meta( $this->_locale_obj->term_id, 'time_format', true );
				break;
		}

		return $value;
	}

	/**
	 * A nicely convoluted action to load the correct translation if page_on_front is set.
	 *
	 * @since 1.0.0
	 * @param WP_Query $query The WP_Query instance (passed by reference).
	 */
	function show_translated_page_if_page_on_front( $query ) {

		if ( is_admin() ) {
			return;
		}

		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( ! $query->is_page ) {
			return;
		}

		$show_on_front = get_option( 'show_on_front' );
		$page_on_front = get_option( 'page_on_front' );

		if ( 'page' === $show_on_front && post_type_supports( 'page', 'multilocale' ) && $page_on_front === $query->query_vars['page_id'] ) {

			$default_locale_obj = multilocale_get_default_locale();

			if ( get_locale() !== $default_locale_obj->description ) {

				// Todo: Add multilocale_get_post_translation( $locale ) function.
				$translations = multilocale_get_post_translations( $page_on_front, true );
				$locale_obj   = multilocale_get_locale_object();

				if ( ! empty( $translations[ $locale_obj->term_id ] ) ) {
					$query->query_vars['page_id'] = $translations[ $locale_obj->term_id ]->ID;
				}
			}
		}
	}
}

global $multilocale_public;
$multilocale_public = new Multilocale_Public();
