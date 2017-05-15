<?php
/**
 * Helpers and template tags
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 */

/**
 * Get locales.
 *
 * @since 0.0.1
 * @return array List of WP_Term objects.
 */
function multilocale_get_locales() {
	global $multilocale_locales;
	return $multilocale_locales->get_locales();
}

/**
 * Get current locale object.
 *
 * @since 0.0.1
 * @return object Current locale.
 */
function multilocale_get_locale_object() {

	if ( ! is_admin() ) {
		return multilocale_locale()->locale_obj;
	} else {
		return multilocale_get_default_locale();
	}
}

/**
 * Get the locale for a post.
 *
 * @see Multilocale_Posts->get_post_locale()
 *
 * @param int|WP_Post|null $post  Optional. Post ID or post object. Defaults to global $post.
 * @param bool             $cache Do not use cache if set to true.
 * @return obj|null Locale object on success or null on failure.
 */
function multilocale_get_post_locale( $post, $cache = true ) {
	global $multilocale_posts;
	return $multilocale_posts->get_post_locale( $post, $cache );
}

/**
 * Update the post locale.
 *
 * @see Multilocale_Posts->update_post_locale()
 *
 * @param int|WP_Post|null $post      Optional. Post ID or post object. Defaults to global $post.
 * @param string           $locale_id Locale term ID.
 * @return string|WP_Error Locale ID on success or WP_Error on failure.
 */
function multilocale_update_post_locale( $post = null, $locale_id ) {
	global $multilocale_posts;
	return $multilocale_posts->update_post_locale( $post, $locale_id );
}

/**
 * Get the translations of a post.
 *
 * @see Multilocale_Posts->get_post_translations()
 *
 * @since 0.0.1
 *
 * @param int|WP_Post  $post    Post ID or post object.
 * @param string|array $status  Post translation status.
 * @param bool         $exclude Exclude the post we're getting the translations of.
 * @return array Array of posts where key is post locale or an empty array.
 */
function multilocale_get_post_translations( $post = null, $status = 'any', $exclude = true ) {
	global $multilocale_posts;
	return $multilocale_posts->get_post_translations( $post, $status, $exclude );
}

/**
 * Get the translation group ID for a post.
 *
 * @see Multilocale_Posts->get_post_translation_group_id()
 *
 * @since 0.0.1
 *
 * @param int|WP_Post|null $post Optional. Post ID or post object. Defaults to global $post.
 * @return string|false Translation group ID on success or false on failure.
 */
function multilocale_get_post_translation_group_id( $post ) {
	global $multilocale_posts;
	return $multilocale_posts->get_post_translation_group_id( $post );
}

/**
 * Get all posts in a translation group by term_id.
 *
 * @see Multilocale_Posts->get_posts_by_translation_group_id()
 *
 * @since 0.0.1
 *
 * @param string       $id          Term ID.
 * @param string       $post_status Post status.
 * @param string|array $exclude     ID or list of IDs to exclude.
 * @return array Array of posts where key is post locale or empty array.
 */
function multilocale_get_posts_by_translation_group_id( $id, $post_status = 'any', $exclude = false ) {
	global $multilocale_posts;
	return $multilocale_posts->get_posts_by_translation_group_id( $id, $post_status, $exclude );
}

/**
 * Get the post translation group.
 *
 * @see Multilocale_Posts->get_post_translation_group()
 *
 * @since 0.0.1
 *
 * @param int|WP_Post|null $post Optional. Post ID or post object. Defaults to global $post.
 * @return object|false Translation group term object on success or false on failure.
 */
function get_post_translation_group( $post = null ) {
	global $multilocale_posts;
	return $multilocale_posts->get_post_translation_group( $post );
}

/**
 * Create new post translation group for a post.
 *
 * If the post already belongs to a translation group, it will be removed from that group and added to a new one.
 *
 * @see Multilocale_Posts->insert_post_translation_group()
 *
 * @since 0.0.1
 *
 * @param int|WP_Post|null $post Optional. Post ID or post object. Defaults to global $post.
 * @return int|WP_Error Translation group ID on success, {@see WP_Error} otherwise.
 */
function multilocale_insert_post_translation_group( $post = null ) {
	global $multilocale_posts;
	return $multilocale_posts->insert_post_translation_group( $post );
}

/**
 * Get the default locale term object.
 *
 * @see Multilocale_Locales->get_default_locale()
 *
 * @since 0.0.1
 *
 * @return bool|WP_Term Term object or false.
 */
function multilocale_get_default_locale() {
	global $multilocale_locales;
	return $multilocale_locales->get_default_locale();
}

/**
 * Get the default locale term ID.
 *
 * @see Multilocale_Locales->get_default_locale_id();
 *
 * @since 0.0.1
 *
 * @return bool|int Locale term ID or false.
 */
function multilocale_get_default_locale_id() {
	global $multilocale_locales;
	return $multilocale_locales->get_default_locale_id();
}

/**
 * Set default locale id.
 *
 * @see Multilocale_Locales->set_default_locale()
 *
 * @since 0.0.1
 *
 * @param string $id Locale id.
 * @return boolean|WP_Error True if option is updated, else WP_Error.
 */
function multilocale_set_default_locale( $id ) {
	global $multilocale_locales;
	return $multilocale_locales->set_default_locale( $id );
}

/**
 * Create new locale.
 *
 * @since 0.0.1
 *
 * @see Multilocale_Locales->insert_locale()
 *
 * @param  string $name       The locale name, eg. "English".
 * @param  string $wp_locale  The WP_Locale code.
 * @param  string $slug       The locale slug to use.
 * @param  array  $meta       Term meta, blogname, blogdescription, time_format, date_format etc.
 * @return array|WP_Error     An array containing the `term_id` and `term_taxonomy_id`,
 *                            {@see WP_Error} otherwise.
 */
function multilocale_insert_locale( $name, $wp_locale, $slug, $meta = null ) {
	global $multilocale_locales;
	return $multilocale_locales->insert_locale( $name, $wp_locale, $slug, $meta );
}

/**
 * Delete locale.
 *
 * @see Multilocale_Locales->insert_locale()
 *
 * @since 0.0.1
 *
 * @param  string $id        Term ID.
 * @return bool|int|WP_Error Returns false if not term, true if completes delete action.
 */
function multilocale_delete_locale( $id ) {
	global $multilocale_locales;
	return $multilocale_locales->delete_locale( $id );
}

/**
 * Check if a locale with the specified term_id exists.
 *
 * @see Multilocale_Locales->locale_id_exists()
 *
 * @since 0.0.1
 *
 * @param string $id Locale id.
 * @return boolean
 */
function multilocale_locale_id_exists( $id ) {
	global $multilocale_locales;
	return $multilocale_locales->locale_id_exists( $id );
}

/**
 * Get localized slug for unsupported post types.
 *
 * @see Multilocale_Public_Post->get_localized_unsupported_post_permalink()
 *
 * @since 0.0.1
 *
 * @param int|WP_Post $post   The post in question.
 * @param WP_Term     $locale The locale in question.
 * @return string Localized url.
 */
function multilocale_get_localized_unsupported_post_permalink( $post, $locale ) {
	if ( is_admin() ) {
		return new WP_Error( 'not_in_admin', __( 'Function not available in admin', 'multilocale' ) );
	}
	global $multilocale_public_posts;
	return $multilocale_public_posts->get_localized_unsupported_post_permalink( $post, $locale );
}

/**
 * Get localized home url.
 *
 * @since 1.0.0
 * @param WP_Term $locale The locale in question.
 * @return string Home URL for the specified locale.
 */
function multilocale_get_localized_home_url( $locale ) {

	global $multilocale_public;
	return $multilocale_public->get_localized_home_url( $locale );
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
function multilocale_get_localized_post_type_archive_link( $post_type, $locale ) {

	global $wp_rewrite;

	if ( ! $post_type_obj = get_post_type_object( $post_type ) ) {
		return false;
	}

	if ( ! is_object( $locale ) ) {
		if ( is_int( $locale ) ) {
			$locale = get_term( $locale, 'locale' );
		} else {
			$locale = wpcom_vip_get_term_by( 'slug', $locale, 'locale' );
		}
		if ( ! $locale || is_wp_error( $locale ) ) {
			return new WP_Error( 'invalid_locale', sprintf( __( 'Invalid locale: %s' ), (string) $locale ) );
		}
	}

	if ( 'post' === $post_type ) {
		$show_on_front = get_option( 'show_on_front' );
		$page_for_posts  = get_option( 'page_for_posts' );

		if ( 'page' === $show_on_front && $page_for_posts ) {
			if ( multilocale_get_default_locale_id() === (int) $locale->term_id ) {
				$link = get_permalink( $page_for_posts );
			} else {
				$translations = multilocale_get_post_translations( $page_for_posts, 'publish', true );
				if ( ! empty( $translations[ $locale->term_id ] ) ) {
					$link = get_permalink( $translations[ $locale->term_id ] );
				} else {
					$link = multilocale_get_localized_home_url( $locale );
				}
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
 * Retrieves the localized permalink for a term archive.
 *
 * Todo: Could do with improvement, just inserting a locale slug for now.
 *
 * @since 1.0.0
 *
 * @global $multilocale_public
 *
 * @param object|int|string $term     The term object, ID, or slug whose link will be retrieved.
 * @param string            $taxonomy Optional. Taxonomy. Default empty.
 * @param string|object     $locale Locale.
 * @return string|WP_Error  The term permalink.
 */
function multilocale_get_localized_term_link( $term, $taxonomy = '', $locale ) {

	$link = wpcom_vip_get_term_link( $term, $taxonomy );

	if ( is_wp_error( $link ) ) {
		return $link;
	}

	if ( ! is_object( $locale ) ) {
		if ( is_int( $locale ) ) {
			$locale = get_term( $locale, 'locale' );
		} else {
			$locale = wpcom_vip_get_term_by( 'slug', $locale, 'locale' );
		}
		if ( ! $locale || is_wp_error( $locale ) ) {
			return new WP_Error( 'invalid_locale', sprintf( __( 'Invalid locale: %s' ), (string) $locale ) );
		}
	}

	// Oohh this is all so crude...
	$link = str_replace( trailingslashit( get_home_url() ), '', $link );
	return trailingslashit( multilocale_get_localized_home_url( $locale ) ) . $link;
}

/**
 * Check if the current page or a page in its translation group is 'page_on_front'.
 *
 * @since 1.0.0
 * @param WP_Post $post          The post in question.
 * @param bool    $siblings_only Only look at post translations, ignore the current post.
 * @return bool True if the current page or a page in its translation group is 'page_on_front'.
 */
function multilocale_page_is_page_on_front( $post, $siblings_only = false ) {

	$_post = get_post( $post );

	if ( ! $_post ) {
		return false;
	}

	if ( 'page' === $_post->post_type && 'page' === get_option( 'show_on_front' ) ) {

		$page_on_front = get_option( 'page_on_front' );

		if ( ! $siblings_only && (int) get_option( 'page_on_front' ) === $_post->ID  ) {
			return true;
		}

		if ( post_type_supports( $_post->post_type, 'multilocale' ) ) {

			$options = get_option( 'plugin_multilocale' );
			$post_locale = multilocale_get_post_locale( $_post );

			if (
				$post_locale
				&&
				! empty( $options[ 'page_on_front' ][ $post_locale->term_id ] )
				&&
				$_post->ID === (int) $options[ 'page_on_front' ][ $post_locale->term_id ]
			) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Check if the current page or a page in its translation group is 'page_for_posts'.
 *
 * @since 1.0.0
 * @param WP_Post $post          The post in question.
 * @param bool    $siblings_only Only look at post translations, ignore the current post.
 * @return bool True if the current page or a page in its translation group is 'page_on_front'.
 */
function multilocale_page_is_page_for_posts( $post, $siblings_only = false ) {

	$_post = get_post( $post );

	if ( ! $_post ) {
		return false;
	}

	if ( 'page' === $_post->post_type && 'page' === get_option( 'show_on_front' ) ) {

		if ( ! $siblings_only &&  (int) get_option( 'page_for_posts' ) === $_post->ID ) {
			return true;
		}

		if ( post_type_supports( $_post->post_type, 'multilocale' ) ) {

			$options = get_option( 'plugin_multilocale' );
			$post_locale = multilocale_get_post_locale( $_post );

			if (
				$post_locale
				&&
				! empty( $options[ 'page_for_posts' ][ $post_locale->term_id ] )
				&&
				$_post->ID === (int) $options[ 'page_for_posts' ][ $post_locale->term_id ]
			) {
				return true;
			}
		}
	}

	return false;
}
