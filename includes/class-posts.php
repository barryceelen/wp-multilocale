<?php
/**
 * Contains the general posts class
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 */

/**
 * General posts class.
 *
 * @todo Review WPCS suggestions.
 *
 * @since 0.0.1
 */
class Multilocale_Posts {

	/**
	 * Instance of this class.
	 *
	 * @since 0.0.1
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the class.
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		$multilocale = multilocale();

		$this->locale_taxonomy = $multilocale->locale_taxonomy;
		$this->post_translation_taxonomy = $multilocale->post_translation_taxonomy;

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

		// Register the post translation taxonomy.
		add_action( 'init', array( $this, 'register_post_translation_taxonomy' ) );
	}

	/**
	 * Register 'post_translation' taxonomy.
	 *
	 * The translation taxonomy is used to connect posts that are translations of one another.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 */
	public function register_post_translation_taxonomy() {

		$labels = array(
			'name'              => _x( 'Translations', 'taxonomy general name', 'multilocale' ),
			'singular_name'     => _x( 'Translation', 'taxonomy singular name', 'multilocale' ),
			'menu_name'         => __( 'Translations', 'multilocale' ),
			'all_items'         => __( 'All Translations', 'multilocale' ),
			'edit_item'         => __( 'Edit Translation', 'multilocale' ),
			'update_item'       => __( 'Update Translation', 'multilocale' ),
			'add_new_item'      => __( 'Add Translation', 'multilocale' ),
			'search_items'      => __( 'Search Translations', 'multilocale' ),
			'add_or_remove_items' => __( 'Add or remove translations', 'multilocale' ),
			'not_found'         => __( 'No translations found', 'multilocale' ),
		);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'public'            => false,
			'show_ui'           => false,
			'show_admin_column' => false,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'translation' ),
		);

		register_taxonomy(
			$this->post_translation_taxonomy,
			get_post_types_by_support( 'multilocale' ),
			$args
		);
	}

	/**
	 * Get the post locale.
	 *
	 * @see get_post()
	 * @see get_the_terms()
	 *
	 * @param int|WP_Post|null $post Optional. Post ID or post object. Defaults to global $post.
	 * @return obj|false Locale object on success or false on failure.
	 */
	public function get_post_locale( $post = null ) {

		$_post = get_post( $post );

		if ( ! $_post ) {
			return false;
		}

		if ( ! post_type_supports( $_post->post_type, 'multilocale' ) ) {
			return false;
		}

		if ( ! $results = wp_cache_get( $_post->ID, 'post_locale' ) ) {

			$terms = get_the_terms( $_post->ID, $this->locale_taxonomy );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				$results = false;
			} else {
				$results = $terms[0];
			}
		}

		return $results;
	}

	/**
	 * Set the locale ID for a post.
	 *
	 * Will remove the current translation id if present. If the post is part of a translation group,
	 * and there is a post in this group with the passed locale id, we'll assign a new translation
	 * group to the post.
	 *
	 * @param int|WP_Post|null $post      Optional. Post ID or post object. Defaults to global $post.
	 * @param int              $locale_id Locale term ID.
	 * @return int|WP_Error Locale ID on success or WP_Error on failure.
	 */
	public function update_post_locale( $post = null, $locale_id ) {

		$_post = get_post( $post );

		if ( ! $_post ) {
			return new WP_Error( 'post_not_found', __( 'Post not found.', 'multilocale' ) );
		}

		if ( ! multilocale_locale_id_exists( $locale_id ) ) {
			return new WP_Error( 'locale_not_found', sprintf( __( 'Locale with id %d not found.', 'multilocale' ), $locale_id ) );
		}

		$post_locale = $this->get_post_locale( $post );

		if ( $post_locale && (int) $post_locale->term_id === (int) $locale_id ) {
			return $locale_id;
		}

		$translation_group_id = $this->get_post_translation_group_id( $post );

		if ( $translation_group_id ) {
			$translations = $this->get_posts_by_translation_group_id( $translation_group_id );
			if ( array_key_exists( $locale_id, $translations ) && $translations[ $locale_id ]->ID !== $_post->ID ) {
				$this->insert_post_translation_group( $translations[ $locale_id ] );
			}
		}

		$terms = wp_set_object_terms( $_post->ID, absint( $locale_id ), $this->locale_taxonomy );

		return $terms[0];
	}

	/**
	 * Remove locale from a post.
	 *
	 * @since 0.0.1
	 *
	 * @see wp_set_object_terms()
	 *
	 * @param int|WP_Post $post Post in question.
	 * @return array|WP_Error Affected Term IDs.
	 */
	public function remove_post_locale( $post ) {

		$_post = get_post( $post );

		if ( ! $_post ) {
			return new WP_Error( 'post_not_found', __( 'Post not found.', 'multilocale' ) );
		}

		return wp_set_object_terms( $_post->ID, null, $_this->locale_taxonomy );
	}

	/**
	 * Get the translations of a post.
	 *
	 * @see get_posts()
	 *
	 * @since 0.0.1
	 *
	 * @param int|WP_Post $post    Post ID or post object.
	 * @param string      $status  Post translation status.
	 * @param bool        $exclude Exclude the post we're getting the translations of.
	 * @return array Array of posts where key is post locale or an empty array.
	 */
	public function get_post_translations( $post = null, $status = 'any', $exclude = true ) {

		$_post = get_post( $post );

		if ( ! $_post ) {
			return new WP_Error( 'post_not_found', __( 'Post not found.', 'multilocale' ) );
		}

		$posts = array();
		$translation_group = $this->get_post_translation_group( $post );

		if ( $translation_group ) {
			if ( $exclude ) {
				$exclude = $post->ID;
			}
			$posts = $this->get_posts_by_translation_group_id( $translation_group->term_id, $status = 'all', $exclude );
		}

		return $posts;
	}

	/**
	 * Get the post translation group.
	 *
	 * @see get_post()
	 * @see get_the_terms()
	 *
	 * @since 0.0.1
	 *
	 * @param int|WP_Post|null $post Optional. Post ID or post object. Defaults to global $post.
	 * @return object|false Translation group term object on success or false on failure.
	 */
	private function get_post_translation_group( $post = null ) {

		$_post = get_post( $post );

		if ( ! $_post ) {
			return false;
		}

		$terms = get_the_terms(
			$_post->ID,
			$this->post_translation_taxonomy
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return false;
		}

		return $terms[0];
	}

	/**
	 * Create new post translation group for a post.
	 *
	 * If the post already belongs to a translation group, it will be removed from that group and added to a new one.
	 *
	 * @see get_post()
	 * @see wp_get_post_terms()
	 *
	 * @since 0.0.1
	 *
	 * @param int|WP_Post|null $post Optional. Post ID or post object. Defaults to global $post.
	 * @return int|WP_Error Translation group ID on success, {@see WP_Error} otherwise.
	 */
	public function insert_post_translation_group( $post = null ) {

		$_post = get_post( $post );

		if ( ! $_post ) {
			return new WP_Error( 'post_not_found', __( 'Post not found.', 'multilocale' ) );
		}

		$taxonomy = $this->post_translation_taxonomy;
		$terms = wp_get_post_terms(
			$_post->ID,
			$taxonomy,
			array( 'fields' => 'all' )
		);

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		/*
		 * Remove post from existing translation group if any, and delete
		 * the translation group if no more posts are present in that group.
		 */
		if ( count( $terms ) > 0 ) {
			foreach ( $terms as $term ) {
				if ( 1 === $term->count ) {
					wp_delete_term( $term->term_id, $taxonomy );
				}
				wp_remove_object_terms( $_post->ID, $term->term_id, $taxonomy );
			}
		}

		$term_name = uniqid( $_post->ID );
		$term  = wp_insert_term( $term_name, $taxonomy, $args = array() );

		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$new_term = wp_set_object_terms( $_post->ID, absint( $term['term_id'] ), $taxonomy );

		if ( is_wp_error( $new_term ) ) {
			return $new_term;
		}

		return $new_term[0];
	}

	/**
	 * Get all posts in a translation group by term_id.
	 *
	 * @see get_posts()
	 *
	 * @since 0.0.1
	 *
	 * @param string       $id          Term ID.
	 * @param string       $post_status Post status.
	 * @param string|array $exclude     ID or list of IDs to exclude.
	 * @return array Array of posts where key is post locale or empty array.
	 */
	public function get_posts_by_translation_group_id( $id, $post_status = 'any', $exclude = false ) {

		$args = array(
			'post_type' => get_post_types_by_support( 'multilocale' ),
			'post_status' => $post_status,
			'tax_query' => array(
				array(
					'taxonomy' => $this->post_translation_taxonomy,
					'field'    => 'term_id',
					'terms'    => absint( $id ),
				),
			),
		);

		if ( $exclude ) {
			$args['post__not_in'] = (array) $exclude;
		}

		$posts = get_posts( $args );
		$result = array();

		if ( $posts ) {

			foreach ( $posts as $post ) {
				$post_locale = $this->get_post_locale( $post );
				if ( $post_locale ) {
					$result[ $post_locale->term_id ] = $post;
				}
			}
		}

		return $result;
	}

	/**
	 * Get the translation group ID for a post.
	 *
	 * @see get_post()
	 * @see wp_get_post_terms()
	 *
	 * @since 0.0.1
	 *
	 * @param int|WP_Post|null $post Optional. Post ID or post object. Defaults to global $post.
	 * @return string|false Translation group ID on success or false on failure.
	 */
	public function get_post_translation_group_id( $post ) {

		$_post = get_post( $post );

		if ( ! $_post ) {
			return false;
		}

		$terms = wp_get_post_terms(
			$_post->ID,
			$this->post_translation_taxonomy,
			array( 'fields' => 'ids' )
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return false;
		}

		return $terms[0];
	}
}

global $multilocale_posts;
$multilocale_posts = Multilocale_Posts::get_instance();
