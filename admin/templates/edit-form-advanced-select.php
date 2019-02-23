<?php
/**
 * Locale select used for supported post types which have not yet been assigned a locale
 *
 * If the user does not select a locale, none will be added.
 * A translation group will be automatically created on post save
 * if a locale is selected.
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
<?php if ( ! empty( $locales ) ) : ?>

	<?php wp_nonce_field( 'save-post-locale', 'post-locale-nonce' ); ?>

	<div class="wp-filter wp-filter-multilocale wp-filter-multilocale-select">
		<div class="locale-select-wrap">
			<select name="locale_id" class="locale-select">
				<option value="0"><?php echo esc_html( $label ); ?></option>
				<?php foreach ( $locales as $locale ) : // WPCS: override ok. ?>
					<option value="<?php echo absint( $locale->term_id ); ?>"><?php echo esc_html( $locale->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
<?php endif; ?>
