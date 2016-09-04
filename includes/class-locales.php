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

		$multilocale = multilocale();

		$this->_locale_taxonomy = $multilocale->locale_taxonomy;

		add_action( 'init', array( $this, 'register_locale_taxonomy' ), 99 );
		add_action( 'init', array( $this, 'register_locale_term_meta' ) );
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
			'name'              => _x( 'Locale', 'taxonomy general name', 'multilocale' ),
			'singular_name'     => _x( 'Locale', 'taxonomy singular name', 'multilocale' ),
			'menu_name'         => __( 'Locales', 'multilocale' ),
			'all_items'         => __( 'All Locales', 'multilocale' ),
			'edit_item'         => __( 'Edit Locale', 'multilocale' ),
			'update_item'       => __( 'Update Locale', 'multilocale' ),
			'add_new_item'      => __( 'Add New Locale', 'multilocale' ),
			'new_item_name'     => __( 'New Locale Name', 'multilocale' ),
			'search_items'      => __( 'Search Locales', 'multilocale' ),
			'popular_items' => __( 'Popular Locales', 'multilocale' ),
			'add_or_remove_items' => __( 'Add or remove locales', 'multilocale' ),
			'choose_from_most_used' => __( 'Choose from most used locales', 'multilocale' ),
			'not_found'         => __( 'No locales found', 'multilocale' ),
		);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'public'            => false,
			'show_ui'           => false,
			'show_admin_column' => true,
			'query_var'         => 'locale',
			'rewrite'           => array( 'slug' => 'locale' ),
		);

		register_taxonomy(
			$this->_locale_taxonomy,
			get_post_types_by_support( 'multilocale' ),
			$args
		);
	}

	/**
	 * Register meta for terms in the locale taxonomy.
	 *
	 * @see register_meta()
	 *
	 * @since 0.0.1
	 */
	public function register_locale_term_meta() {

		$term_meta_keys = array(
			'_locale_blogname' => array(
				'description' => esc_html__( 'Site name for the locale' ),
			),
			'_locale_blogdescription' => array(
				'description' => esc_html__( 'Site description' ),
			),
			'_locale_date_format' => array(
				'description' => esc_html__( 'Date format' ),
			),
			'_locale_time_format' => array(
				'description' => esc_html__( 'Time format' ),
			),
		);

		foreach ( $term_meta_keys as $meta_key => $args ) {
			register_meta(
				'term',
				$meta_key,
				array(
					'description' => $args['description'],
					'sanitize_callback' => 'trim',
					'single' => true,
					'type' => 'string',
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
				'slug' => $slug,
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
	 * @return bool|WP_Term Term object or false.
	 */
	public function get_default_locale() {

		$default_locale = false;
		$options        = get_option( 'plugin_multilocale' );
		$locales        = $this->get_locales();

		if ( $locales ) {
			foreach ( $locales as $locale ) {
				if ( (int) $options['default_locale_id'] === (int) $locale->term_id ) {
					$default_locale = $locale;
					break;
				}
			}
		}

		return apply_filters( 'multilocale_default_locale', $default_locale );
	}

	/**
	 * Get the default locale term ID.
	 *
	 * @since 0.0.1
	 *
	 * @return bool|int Locale term ID or false.
	 */
	public function get_default_locale_id() {

		$default_locale_id = false;
		$default_locale = $this->get_default_locale();

		if ( $default_locale ) {
			$default_locale_id = $default_locale->term_id;
		}

		return apply_filters( 'multilocale_default_locale_id', $default_locale_id );
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

		$options = get_option( 'plugin_multilocale' );
		$options['default_locale_id'] = absint( $id );

		return update_option( 'plugin_multilocale', $options );
	}

	/**
	 * Get locales.
	 *
	 * @since 0.0.1
	 *
	 * @return object|false Object with active locales or false.
	 */
	public function get_locales() {

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
					$this->_locale_taxonomy
				)
			); // WPCS: db call ok.

			if ( count( $results ) ) {
				wp_cache_add( 'multilocale_locales', $results );
			} else {
				$results = false;
			}
		}

		return $results;
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
