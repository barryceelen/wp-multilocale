<?php
/**
 * Manage locales content for inclusion in plugin settings page
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
<h1><?php echo esc_html( $locale_taxonomy_obj->labels->menu_name ); ?></h1>
<br class="clear" />
<div id="col-container">
	<div id="col-right">
		<div class="col-wrap">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<?php
						foreach ( $manage_columns as $k => $v ) { // WPCS: prefix ok.
							printf(
								'<th scope="col" id="%s" class="column-%s manage-column">%s</th>',
								esc_attr( $k ),
								esc_attr( $k ),
								esc_html( $v )
							);
						}
						?>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<?php
						foreach ( $manage_columns as $k => $v ) { // WPCS: prefix ok.
							printf( '<th scope="col" class="manage-column">%s</th>', esc_html( $v ) );
						}
						?>
					</tr>
				</tfoot>
				<tbody id="the-list">
				<?php if ( ! $locales ) : ?>
					<tr class="no-items">
						<td class="colspanchange" colspan="<?php echo count( $manage_columns ); ?>">
							<?php esc_html_e( 'No locales found.', 'multilocale' ); ?>
						</td>
					</tr>
				<?php elseif ( is_wp_error( $locales ) ) : ?>
					<tr class="no-items">
						<td class="colspanchange" colspan="<?php echo count( $manage_columns ); ?>">
							<?php
							printf(
								'<p>%s: %s</p>',
								esc_html__( 'Error', 'default' ),
								esc_html( $locales->get_error_message() )
							);
							?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $locales as $locale ) : ?>
						<tr>
							<td class="column-locale-name name column-name">
								<strong><a class="row-title" href="<?php echo esc_url( $locale->multilocale_edit_url ); ?>"><?php echo esc_html( $locale->name ); ?></a></strong>
								<?php
								if ( (int) $options['default_locale_id'] === (int) $locale->term_id ) {
									esc_html_e( ' &ndash; Default', 'default' );
								}
								?>
								<br>
								<div class="row-actions">
									<span class="edit">
										<a href="<?php echo esc_url( $locale->multilocale_edit_url ); ?>"><?php esc_html_e( 'Edit', 'default' ); ?></a></span> | <span class="delete"><a href="<?php echo esc_url( $locale->multilocale_delete_url ); ?>"><?php esc_html_e( 'Delete', 'default' ); ?></a></span> | <span class="view"><a href="<?php echo esc_url( $locale->multilocale_view_url ); ?>"><?php esc_html_e( 'View', 'default' ); ?></a>
									</span>
								</div>
							</td>
							<td class="column-locale-slug">
								<?php echo esc_html( $locale->slug ); ?>
							</td>
							<td class="column-locale-wp-locale">
								<?php echo esc_html( $locale->description ); ?>
							</td>
							<td class="column-posts">
								<?php echo esc_html( $locale->count ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
			<br class="clear" />
			<?php
			/**
			 * Fires after the locale list table.
			 *
			 * @since 0.0.1
			 *
			 * @param string $locale_taxonomy The taxonomy name.
			 */
			do_action( "multilocale_after_{$locale_taxonomy_obj->name}_table", $locale_taxonomy_obj );
			?>
		</div>
	</div>
	<div id="col-left">
		<div class="col-wrap">
			<?php
			/**
			 * Fires before the Add Locale form.
			 *
			 * @since 0.0.1
			 *
			 * @param string $locale_taxonomy_obj->name The taxonomy slug.
			 */
			do_action( "multilocale_{$locale_taxonomy_obj->name}_pre_add_form", $locale_taxonomy_obj->name );
			?>
			<div class="form-wrap">

				<h3><?php echo esc_html( $locale_taxonomy_obj->labels->add_new_item ); ?></h3>

				<form id="multilocale-insert-locale" method="post" action="">
					<?php wp_nonce_field( 'multilocale_settings', 'multilocale_settings' ); ?>
					<input type="hidden" name="action" value="insert_locale">
					<div class="form-field form-required">
						<label for="">
							<?php
							printf(
								/* translators: %s: locale taxonomy name */
								esc_html_x( 'Select %s', 'Select locale', 'multilocale' ),
								esc_html( $locale_taxonomy_obj->labels->singular_name )
							);
							?>
						</label>
						<?php
						echo $this->get_locale_dropdown( // WPCS: XSS ok.
							array(
								'disabled' => $active_locale_names,
							)
						);
						?>
					</div>
					<?php
					/**
					 * Fires after the Add Locale form fields.
					 *
					 * The dynamic portion of the hook name, `$tax`, refers to the taxonomy slug.
					 *
					 * @since 0.0.1
					 *
					 * @param string $locale_taxonomy_obj->name The taxonomy slug.
					 */
					do_action( "multilocale_{$locale_taxonomy_obj->name}_add_form_fields", $locale_taxonomy_obj );

					submit_button( $locale_taxonomy_obj->labels->add_new_item );

					/**
					 * Fires at the end of the Add Locale form.
					 *
					 * The dynamic portion of the hook name, `$locale_taxonomy`, refers to the taxonomy slug.
					 *
					 * @since 0.0.1
					 *
					 * @param string $locale_taxonomy The taxonomy slug.
					 */
					do_action( "multilocale_{$locale_taxonomy_obj->name}_add_form", $locale_taxonomy_obj );
					?>
				</form>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
//<![CDATA[
jQuery( document ).ready( function( $ ) {
	$( '#multilocale-insert-locale' ).submit( function() {
		// Todo: Add styles via external css file.
		$( '#submit', this ).after( '<span class="spinner" style="visibility: visible; float: none; margin-top:-2px;" />' );
	});
});
//]]>
</script>
