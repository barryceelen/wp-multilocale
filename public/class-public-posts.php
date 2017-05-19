<?php
/**
 * Contains public facing post class
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 */

/**
 * Public post related functionality.
 *
 * @since 0.0.1
 */
class Multilocale_Public_Posts {

	/**
	 * Locale taxonomy name.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	private $_locale_taxonomy;

	/**
	 * Locale object.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	private $_locale_obj;

	/**
	 * Initialize the class.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {

		$this->_locale_obj = multilocale_locale()->locale_obj;

		if ( empty( $this->_locale_obj ) ) {
			return;
		}

		$multilocale = multilocale();

		$this->_locale_taxonomy = $multilocale->locale_taxonomy;
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

		// Redirect to localized url if non-localized is requested.
		add_action( 'template_redirect', array( $this, 'redirect_to_localized_post_url' ) );

		// Filter public facing post permalinks.
		// Note: Filter also removed and set in get_localized_unsupported_post_permalink().
		add_filter( 'post_link', array( $this, 'filter_post_link' ), 10, 2 );
		add_filter( 'page_link', array( $this, 'filter_post_link' ), 10, 2 );
		add_filter( 'post_type_link', array( $this, 'filter_post_link' ), 10, 2 );
		add_filter( 'post_type_archive_link', array( $this, 'filter_post_type_archive_link' ), 10, 2 );

		// Filter next and previous post link join. Uses the get_{$adjacent}_post_join hook.
		// Todo: Fix prev next links.
		add_filter( 'get_next_post_join', array( $this, 'filter_get_previous_next_post_join' ), 20, 5 );
		add_filter( 'get_previous_post_join', array( $this, 'filter_get_previous_next_post_join' ), 20, 5 );

		// Filter next and previous post link where. Uses the get_{$adjacent}_post_where hook.
		add_filter( 'get_next_post_where', array( $this, 'filter_get_previous_next_post_where' ), 20, 5 );
		add_filter( 'get_previous_post_where', array( $this, 'filter_get_previous_next_post_where' ), 20, 5 );

		// Modify main query for fun and profit.
		add_action( 'pre_get_posts', array( $this, 'localize_main_query' ), 9999 );

		// Filter page_for_posts option.
		add_filter( 'option_page_for_posts', array( $this, 'filter_option_page_for_posts' ) );

		// Filter page_on_front option.
		add_filter( 'option_page_on_front', array( $this, 'filter_option_page_for_posts' ) );
	}

	/**
	 * Get localized slug for unsupported post types.
	 *
	 * @since 0.0.1
	 *
	 * @param  int|WP_Post $post   The post in question.
	 * @param  int|WP_Term $locale ID or WP_Term object for the locale in question.
	 * @return false|string False if the post does not exist, localized permalink if the post does not support multilocale, else the permalink.
	 */
	public function get_localized_unsupported_post_permalink( $post, $locale ) {

		$_post = get_post( $post );

		if ( ! $_post ) {
			return false;
		}

		if ( post_type_supports( $_post->post_type, 'multilocale' ) ) {
			return get_permalink( $_post );
		}

		global $multilocale_public;

		$locale = get_term( $locale, $this->_locale_taxonomy );

		if ( ! $locale || is_wp_error( $locale ) ) {
			return false;
		}

		remove_filter( 'home_url', array( $multilocale_public, 'filter_home_url' ), 10 );

		if ( multilocale_get_default_locale_id() === (int) $locale->term_id ) {
			$url = get_permalink( $_post );
		} else {
			$permalink = get_permalink( $_post );
			$home_url = trailingslashit( get_home_url() );
			$url = $home_url . $locale->slug . '/' . str_replace( $home_url, '', $permalink );
		}

		add_filter( 'home_url', array( $multilocale_public, 'filter_home_url' ), 10, 2 );

		return $url;
	}

	/**
	 * Make the main query retrieve posts in the current locale only.
	 *
	 * Todo: Basic first try, improve.
	 *
	 * @see filter_posts_where_and_join() for single post queries.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @param WP_Query $wp_query The WP_Query instance (passed by reference).
	 */
	public function localize_main_query( $wp_query ) {

		if ( ! $wp_query->is_main_query() || $wp_query->is_singular() ) {
			return;
		}

		$post_type = empty( $wp_query->query_vars['post_type'] ) ? 'post' : sanitize_key( $wp_query->query_vars['post_type'] );

		if ( ! post_type_supports( $post_type, 'multilocale' ) ) {
			return;
		}

		$tax_query = array(
			'taxonomy' => 'locale',
			'field'    => 'id',
			'terms'    => array( $this->_locale_obj->term_id ),
			'operator' => 'IN',
			'include_children' => false,
		);

		if ( empty( $wp_query->tax_query->queries ) ) {
			$wp_query->set( 'tax_query', array( $tax_query ) );
		} else {
			$wp_query->tax_query->queries[] = $tax_query;
		}
	}


	/**
	 * Redirect to the localized url of a post if a non-localized version is opened.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 */
	public function redirect_to_localized_post_url() {

		if ( false === apply_filters( 'multilocale_redirect_to_localized_post_url', true ) ) {
			return;
		}

		if ( ! is_singular( get_post_types_by_support( 'multilocale' ) ) ) {
			return;
		}

		global $post;

		$locale = get_locale();
		$post_locale = multilocale_get_post_locale( $post );

		if ( $post_locale && $locale !== $post_locale->description ) {
			wp_safe_redirect( get_permalink( $post ), 301 );
			exit();
		}
	}

	/**
	 * Maybe add locale slug to post permalink.
	 *
	 * @todo Rethink.
	 * @todo Use user_trailingslashit()?
	 *
	 * @param string  $permalink The post's permalink.
	 * @param WP_Post $post      The post in question.
	 * @return string The post's permalink including the locale slug if the post's locale is not the default locale.
	 */
	public function filter_post_link( $permalink, $post ) {

		// The page_link filter sends the post id as the second parameter.
		$_post   = get_post( $post );
		$options = get_option( 'multilocale' );

		if ( is_admin() ) {
			return $permalink;
		}

		if ( post_type_supports( $_post->post_type, 'multilocale' ) ) {

			$options     = get_option( 'plugin_multilocale' );
			$post_locale = multilocale_get_post_locale( $post );

			if ( $post_locale ) {

				if (
					'page' === $_post->post_type
					&&
					! empty( $options['page_on_front'][ $post_locale->term_id ] )
					&&
					(int) $_post->ID === (int) $options['page_on_front'][ $post_locale->term_id ]
				) {

					$permalink = multilocale_get_localized_home_url( $post_locale );

				} else {

					$http     = is_ssl() ? 'https://' : 'http://';
					$home_url = trailingslashit( get_home_url() );
					$str      = str_replace( $http, '', $home_url );
					$array    = explode( '/', trim( $str, '/' ) );

					if (
						! empty( $array[1] )
						&&
						$array[1] !== $post_locale->slug
						||
						(int) $post_locale->term_id !== (int) $options['default_locale_id']
					) {
						$permalink = $http . $array[0] . '/' . $post_locale->slug . '/' .str_replace( trailingslashit( $home_url ), '', $permalink );
					}
				}
			}
		}

		return $permalink;
	}

	/**
	 * Filters the post type archive link.
	 *
	 * @since 1.0.0
	 * @param string $link      The post type archive permalink.
	 * @param string $post_type Post type name.
	 */
	public function filter_post_type_archive_link( $link, $post_type ) {

		$locale_obj = multilocale_get_locale_object();

		if ( ! $locale_obj || multilocale_get_default_locale_id() === (int) $locale_obj->term_id ) {
			return $link;
		}

		return $this->get_localized_post_type_archive_link( $post_type, $locale_obj );
	}

	/**
	 * Retrieves the localized permalink for a post type archive.
	 *
	 * @since 1.0.0
	 *
	 * @global WP_Rewrite $wp_rewrite
	 *
	 * @param string        $post_type Post type.
	 * @param string|object $locale Locale.
	 * @return string|false|WP_Error The post type archive permalink.
	 */
	function get_localized_post_type_archive_link( $post_type, $locale = null ) {

		if ( is_admin() ) {
			return get_post_type_archive_link( $post_type );
		}

		if ( ! $locale ) {
			$locale = multilocale_get_locale_object();
		}

		global $wp_rewrite;

		$post_type_obj = get_post_type_object( $post_type );

		if ( ! $post_type_obj ) {
			return false;
		}

		if ( ! is_object( $locale ) ) {
			if ( is_int( $locale ) ) {
				$locale = get_term( $locale, 'locale' );
			} else {
				$locale = wpcom_vip_get_term_by( 'slug', $locale, 'locale' );
			}
			if ( ! $locale || is_wp_error( $locale ) ) {
				/* translators: %s: locale name */
				return new WP_Error( 'invalid_locale', sprintf( __( 'Invalid locale: %s' ), (string) $locale ) );
			}
		}

		if ( multilocale_get_default_locale_id() === $locale->term_id ) {
			return get_post_type_archive_link( $post_type );
		}

		if ( 'post' === $post_type ) {
			$show_on_front = get_option( 'show_on_front' );
			$page_for_posts = get_option( 'page_for_posts' );

			if ( 'page' === $show_on_front && $page_for_posts ) {
				$translations = multilocale_get_post_translations( $page_for_posts, 'publish', true );
				if ( ! empty( $translations[ $locale->term_id ] ) ) {
					$link = get_permalink( $translations[ $locale->term_id ] );
				} else {
					$link = multilocale_get_localized_home_url( $locale );
				}
			} else {
				$link = multilocale_get_localized_home_url( $locale );
			}

			/**
			 * Filters the localized post type archive permalink.
			 *
			 * @since 1.0.0
			 *
			 * @param string $link      The localized post type archive permalink.
			 * @param string $post_type Post type name.
			 */
			return apply_filters( 'multilocale_localized_post_type_archive_link', $link, $post_type );
		}

		if ( ! $post_type_obj->has_archive ) {
			return false;
		}

		if ( get_option( 'permalink_structure' ) && is_array( $post_type_obj->rewrite ) ) {

			$struct = ( true === $post_type_obj->has_archive ) ? $post_type_obj->rewrite['slug'] : $post_type_obj->has_archive;

			if ( $post_type_obj->rewrite['with_front'] ) {
				$struct = $wp_rewrite->front . $struct;
			} else {
				$struct = $wp_rewrite->root . $struct;
			}

			$link = multilocale_get_localized_home_url( $locale ) . '/' . ltrim( user_trailingslashit( $struct, 'post_type_archive' ), '/' );

		} else {
			$link = multilocale_get_localized_home_url( $locale ) . '/?post_type=' . $post_type;
		}

		/**
		 * Filters the localized post type archive permalink.
		 *
		 * @since 1.0.0
		 *
		 * @param string $link      The localized post type archive permalink.
		 * @param string $post_type Post type name.
		 */
		return apply_filters( 'multilocale_localized_post_type_archive_link', $link, $post_type );
	}

	/**
	 * Modify join clause of the next and previous post links query to only point to a post in the same locale as the current post.
	 *
	 * @since 0.0.1
	 *
	 * @see http://presscustomizr.com/snippet/restrict-post-navigation-category/
	 *
	 * @access private
	 * @param string  $join           The JOIN clause in the SQL.
	 * @param bool    $in_same_term   Whether post should be in a same taxonomy term.
	 * @param array   $excluded_terms Array of excluded term IDs.
	 * @param string  $taxonomy       Taxonomy. Used to identify the term used when `$in_same_term` is true.
	 * @param WP_Post $post           WP_Post object.
	 * @return string  The JOIN clause in the SQL.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	public function filter_get_previous_next_post_join( $join, $in_same_term, $excluded_terms, $taxonomy, $post ) {

		global $wpdb;

		if ( ! is_object_in_taxonomy( $post, $this->_locale_taxonomy ) ) {
			return $join;
		}

		$join = " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";

		return $join;
	}

	/**
	 * Modify where clause of the next and previous post links query to only point to a post in the same locale as the current post.
	 *
	 * @since 0.0.1
	 *
	 * @see http://presscustomizr.com/snippet/restrict-post-navigation-category/
	 *
	 * @param string  $where          The `WHERE` clause in the SQL.
	 * @param bool    $in_same_term   Whether post should be in a same taxonomy term.
	 * @param array   $excluded_terms Array of excluded term IDs.
	 * @param string  $taxonomy       Taxonomy. Used to identify the term used when `$in_same_term` is true.
	 * @param WP_Post $post           WP_Post object.
	 * @return string The `WHERE` clause in the SQL.
	 */
	public function filter_get_previous_next_post_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {

		global $wpdb;

		if ( ! is_object_in_taxonomy( $post, $this->_locale_taxonomy ) ) {
			return $where;
		}

		$post_locale = multilocale_get_post_locale( $post );

		if ( ! $post_locale ) {
			return $where;
		}

		$smaller_bigger = ( 'get_previous_post_where' === current_filter() ) ? '<' : '>';

		$where = $wpdb->prepare( // WPCS: unprepared SQL ok.
			"WHERE p.post_date {$smaller_bigger} %s AND p.post_type = %s AND p.post_status = 'publish' AND tt.term_id IN (%d)",
			$post->post_date,
			$post->post_type,
			absint( $post_locale->term_id )
		);

		return $where;
	}

	/**
	 * Filter page_for_posts and page_on_front option.
	 *
	 * If a translation is present for the page_for_posts or page_on_front page, return the ID of the translation.
	 *
	 * Note: Assumes the page_for_posts is in the default language.
	 *
	 * @since 1.0.0
	 * @param mixed $value Value of the option.
	 * @return mixed Page ID or empty string if no page is set as 'page_for_posts'.
	 */
	function filter_option_page_for_posts( $value ) {

		if ( empty( $value ) ) {
			return $value;
		}

		$option = substr( current_filter(), 7 );

		if ( multilocale_get_default_locale_id() !== $this->_locale_obj->term_id ) {

			$options = get_option( 'plugin_multilocale' );

			if ( ! empty( $options[ $option ][ $this->_locale_obj->term_id ] ) ) {
				return $options[ $option ][ $this->_locale_obj->term_id ];
			}
		}

		return $value;
	}
}

global $multilocale_public_posts;
$multilocale_public_posts = new Multilocale_Public_Posts();
