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

		$options = array(
			'blogname',
			'blogdescription',
			'date_format',
			'time_format',
			'page_for_posts',
			'page_on_front',
		);

		foreach ( $options as $option ) {
			add_filter( 'option_' . $option, array( $this, 'filter_options' ), 10, 2 );
		}
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

		if ( (int) multilocale_get_default_locale_id() !== (int) $locale->term_id ) {
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
			case 'page_for_posts' :
				$option = get_option( 'plugin_multilocale' );
				if ( ! empty( $option['page_for_posts'][ $this->_locale_obj->term_id ] ) ) {
					return (int) $option['page_for_posts'][ $this->_locale_obj->term_id ];
				}
				break;
			case 'page_on_front' :
				$option = get_option( 'plugin_multilocale' );
				if ( ! empty( $option['page_on_front'][ $this->_locale_obj->term_id ] ) ) {
					return (int) $option['page_on_front'][ $this->_locale_obj->term_id ];
				}
				break;
		}

		return $value;
	}
}

global $multilocale_public;
$multilocale_public = new Multilocale_Public();
