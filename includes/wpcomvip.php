<?php
/**
 * Wrap WordPress VIP functions to make PHP Code Sniffer happy
 *
 * Note: Other candidates, which are currently not used:
 *       - wpcom_vip_get_page_by_title()
 *       - wpcom_vip_get_category_by_slug()
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 */

if ( ! function_exists( 'wpcom_vip_get_term_by' ) ) {

	/**
	 * Get all Term data from database by Term field and data.
	 *
	 * @since 0.0.1
	 *
	 * @see get_term_by()
	 *
	 * @param string     $field    Either 'slug', 'name', 'id' (term_id), or 'term_taxonomy_id'.
	 * @param string|int $value    Search for this term value.
	 * @param string     $taxonomy Taxonomy name. Optional, if `$field` is 'term_taxonomy_id'.
	 * @param string     $output   Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which correspond to
	 *                             a WP_Term object, an associative array, or a numeric array, respectively. Default OBJECT.
	 * @param string     $filter   Optional, default is raw or no WordPress defined filter will applied.
	 * @return WP_Term|array|false WP_Term instance (or array) on success. Will return false if `$taxonomy` does not exist
	 *                             or `$term` was not found.
	 */
	function wpcom_vip_get_term_by( $field, $value, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {
		return get_term_by( $field, $value, $taxonomy, $output, $filter );
	}
}

if ( ! function_exists( 'wpcom_vip_get_term_link' ) ) {

	/**
	 * Generate a permalink for a taxonomy term archive.
	 *
	 * @since 0.0.1
	 *
	 * @see get_term_link()
	 *
	 * @param object|int|string $term     The term object, ID, or slug whose link will be retrieved.
	 * @param string            $taxonomy Optional. Taxonomy. Default empty.
	 * @return string|WP_Error HTML link to taxonomy term archive on success, WP_Error if term does not exist.
	 */
	function wpcom_vip_get_term_link( $term, $taxonomy = '' ) {
		return get_term_link( $term, $taxonomy );
	}
}

if ( ! function_exists( 'wpcom_vip_term_exists' ) ) {

	/**
	 * Check if Term exists.
	 *
	 * @since 0.0.3
	 *
	 * @see term_exists()
	 *
	 * @param int|string $term     The term to check. Accepts term ID, slug, or name.
	 * @param string     $taxonomy The taxonomy name to use
	 * @param int        $parent   Optional. ID of parent term under which to confine the exists search.
	 * @return mixed Returns null if the term does not exist. Returns the term ID
	 *               if no taxonomy is specified and the term ID exists. Returns
	 *               an array of the term ID and the term taxonomy ID the taxonomy
	 *               is specified and the pairing exists.
	 */
	function wpcom_vip_term_exists( $term, $taxonomy = '', $parent = null ) {
		return term_exists( $term, $taxonomy );
	}
}
