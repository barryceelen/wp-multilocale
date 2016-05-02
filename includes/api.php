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
 * @return array List of locale taxonomy terms.
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
	return multilocale_locale()->locale_obj;
}

/**
 * Get the locale for a post.
 *
 * @see Multilocale_Posts->get_post_locale()
 *
 * @param int|WP_Post|null $post Optional. Post ID or post object. Defaults to global $post.
 * @return obj|null Locale object on success or null on failure.
 */
function multilocale_get_post_locale( $post ) {
	global $multilocale_posts;
	return $multilocale_posts->get_post_locale( $post );
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
 * @param int|WP_Post $post    Post ID or post object.
 * @param string      $status  Post translation status.
 * @param bool        $exclude Exclude the post we're getting the translations of.
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
 * @see Multilocale_Public_Post->get_locale_unsupported_post_url()
 *
 * @since 0.0.1
 *
 * @param int|WP_Post $post   The post in question.
 * @param WP_Term     $locale The locale in question.
 * @return string Localized url.
 */
function multilocale_get_locale_unsupported_post_url( $post, $locale ) {
	if ( is_admin() ) {
		return new WP_Error( 'not_in_admin', __( 'Function not available in admin', 'multilocale' ) );
	}
	global $multilocale_public_posts;
	return $multilocale_public_posts->get_locale_unsupported_post_url( $post, $locale );
}
