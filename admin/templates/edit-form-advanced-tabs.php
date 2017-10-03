<?php
/**
 * Locale tabs for inclusion in the post edit page
 *
 * @todo Allow plugins to add or modify stuff.
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 */

// Don't load directly.
defined( 'ABSPATH' ) || die();
?>

<input type="hidden" name="locale_id" value="<?php echo absint( $post_locale->term_id ); ?>">
<input type="hidden" name="translation_id" value="<?php echo absint( $translation_id ); ?>">
<div class="wp-filter locale-tabs"><ul class="filter-links"><?php echo implode( $filter_links ); ?></ul></div>
