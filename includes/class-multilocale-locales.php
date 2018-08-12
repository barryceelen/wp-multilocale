<?php
/**
 * Contains the general locales class.
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 */

/**
 * General locales class.
 *
 * @since 0.0.1
 */
class Multilocale_Locales {

	/**
	 * Instance of this class.
	 *
	 * @since 0.0.1
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin.
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		$this->_locale_taxonomy = multilocale()->locale_taxonomy;

		add_action( 'init', array( $this, 'register_locale_taxonomy' ), 11 ); // Late to allow post types to register support.
		add_action( 'init', array( $this, 'register_locale_term_meta' ), 11 );
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
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register 'locale' taxonomy.
	 *
	 * Posts are assigned a term in the locale taxonomy which signifies the locale they belong to.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function register_locale_taxonomy() {

		$labels = array(
			'name'                  => _x( 'Locale', 'taxonomy general name', 'multilocale' ),
			'singular_name'         => _x( 'Locale', 'taxonomy singular name', 'multilocale' ),
			'menu_name'             => __( 'Locales', 'multilocale' ),
			'all_items'             => __( 'All Locales', 'multilocale' ),
			'edit_item'             => __( 'Edit Locale', 'multilocale' ),
			'update_item'           => __( 'Update Locale', 'multilocale' ),
			'add_new_item'          => __( 'Add New Locale', 'multilocale' ),
			'new_item_name'         => __( 'New Locale Name', 'multilocale' ),
			'search_items'          => __( 'Search Locales', 'multilocale' ),
			'popular_items'         => __( 'Popular Locales', 'multilocale' ),
			'add_or_remove_items'   => __( 'Add or remove locales', 'multilocale' ),
			'choose_from_most_used' => __( 'Choose from most used locales', 'multilocale' ),
			'not_found'             => __( 'No locales found', 'multilocale' ),
		);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'public'            => false,
			'show_ui'           => false,
			'show_admin_column' => true,
			'query_var'         => 'locale',
			'rewrite'           => false,

			/*
			 * Add support for the YARPP plugin.
			 *
			 * Make YARPP only consider posts in the same locale:
			 *
			 * - Open the YARPP plugin options page.
			 * - Find the "Relatedness" options.
			 * - Select the 'Require at least one locale in common' option for the 'Locale' taxonomy.
			 *
			 * Note that post types without 'multilocale' support will not be included in the default
			 * relatedness consideration, as they do not use the 'locale' taxonomy.
			 *
			 * To display related posts without locale support, use your own call to yarp_related()
			 * and set 'require_tax' => array().
			 */
			'yarpp_support'     => true, // Add support for the YARPP plugin.
		);

		register_taxonomy(
			$this->_locale_taxonomy,
			get_post_types_by_support( 'multilocale' ),
			$args
		);
	}

	/**
	 * Register term metadata for terms in the locale taxonomy.
	 *
	 * @see register_meta()
	 *
	 * @since 0.0.1
	 */
	public function register_locale_term_meta() {

		$term_meta_keys = array(
			'_locale_blogname'        => array(
				'description' => esc_html__( 'Site name for the locale', 'multilocale' ),
			),
			'_locale_blogdescription' => array(
				'description' => esc_html__( 'Site description', 'default' ),
			),
			'_locale_date_format'     => array(
				'description' => esc_html__( 'Date format', 'default' ),
			),
			'_locale_time_format'     => array(
				'description' => esc_html__( 'Time format', 'default' ),
			),
		);

		foreach ( $term_meta_keys as $meta_key => $args ) {
			register_meta(
				'term',
				$meta_key,
				array(
					'description'       => $args['description'],
					'sanitize_callback' => 'trim',
					'single'            => true,
					'type'              => 'string',
				)
			);
		}
	}

	/**
	 * Create new locale.
	 *
	 * @todo Add date and time settings etc.
	 *
	 * @since 0.0.1
	 *
	 * @see wp_insert_term()
	 *
	 * @param string $name       The locale name, eg. "English".
	 * @param string $wp_locale  The WP_Locale code.
	 * @param string $slug       The locale slug to use.
	 * @param array  $meta {
	 *     Term meta.
	 *
	 *     @type string $blogname        Optional. The blogname for the locale.
	 *     @type string $blogdescription Optional. The blogdescription for the locale.
	 *     @type string $time_format     Optional. The time format for the locale.
	 *     @type string $date_format     Optional. The date format for the locale.
	 * }
	 * @return array|WP_Error An array containing the `term_id` and `term_taxonomy_id`,
	 *                        {@see WP_Error} otherwise.
	 */
	public function insert_locale( $name, $wp_locale, $slug, $meta = false ) {

		$term = wp_insert_term(
			$name,
			$this->_locale_taxonomy,
			array(
				'description' => $wp_locale,
				'slug'        => $slug,
			)
		);

		if ( ! is_wp_error( $term ) && $meta ) {
			foreach ( $meta as $k => $v ) {
				update_term_meta( $term['term_id'], $k, $v );
			}
		}

		return $term;
	}

	/**
	 * Delete locale.
	 *
	 * @since 0.0.1
	 *
	 * @param string $id Term ID.
	 * @return bool|int|WP_Error Returns false if not term; true if completes delete action.
	 */
	public function delete_locale( $id ) {
		return wp_delete_term( absint( $id ), $this->_locale_taxonomy );
	}

	/**
	 * Get the default locale term object.
	 *
	 * @since 0.0.1
	 *
	 * @return false|WP_Term Term object or false.
	 */
	public function get_default_locale() {

		$default_locale_id = $this->get_default_locale_id();
		$_term             = false;

		if ( $default_locale_id ) {
			$_term = WP_Term::get_instance( (int) $default_locale_id, $this->_locale_taxonomy );
		}

		/**
		 * Filters the default locale WP_Term object.
		 *
		 * @since 0.0.1
		 *
		 * @param false|WP_Term Term object or false.
		 */
		return apply_filters( 'multilocale_default_locale', $_term );
	}

	/**
	 * Get the default locale term ID.
	 *
	 * @since 0.0.1
	 *
	 * @return bool|int Locale term ID or false.
	 */
	public function get_default_locale_id() {

		$id      = false;
		$options = get_option( 'plugin_multilocale' );

		if ( ! empty( $options['default_locale_id'] ) ) {
			$id = (int) $options['default_locale_id'];
		}

		return apply_filters( 'multilocale_default_locale_id', $id );
	}

	/**
	 * Set default locale id.
	 *
	 * @since 0.0.1
	 *
	 * @param string $id Locale id.
	 * @return boolean|WP_Error True if option is updated, else WP_Error.
	 */
	public function set_default_locale( $id ) {

		if ( ! $this->locale_id_exists( $id ) ) {
			return new WP_Error( 'locale_not_found', sprintf( 'Locale with ID %d not found', $id ) );
		}

		$options                      = get_option( 'plugin_multilocale' );
		$options['default_locale_id'] = absint( $id );

		return update_option( 'plugin_multilocale', $options );
	}

	/**
	 * Get locales.
	 *
	 * @since 0.0.1
	 *
	 * @return array List of WP_Term objects.
	 */
	public function get_locales() {

		$terms = wp_cache_get( 'multilocale_locales' );

		if ( ! $terms ) {

			$args = array(
				'taxonomy' => $this->_locale_taxonomy,
			);

			$term_query = new WP_Term_Query();
			$terms      = $term_query->query( $args );

			if ( count( $terms ) ) {
				wp_cache_add( 'multilocale_locales', $terms );
			} else {
				$terms = array();
			}
		}

		return $terms;
	}

	/**
	 * Check if a locale with the specified term_id exists.
	 *
	 * @since 0.0.1
	 *
	 * @param string $id Locale id.
	 * @return boolean
	 */
	public function locale_id_exists( $id ) {
		return get_term_by( 'id', absint( $id ), $this->_locale_taxonomy );
	}
}

global $multilocale_locales;
$multilocale_locales = Multilocale_Locales::get_instance();
