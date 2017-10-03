<?php
/**
 * Delete locale confirmation for inclusion in plugin settings page
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

<h1><?php printf( esc_html__( 'Delete %s', 'multilocale' ), esc_html( $locale_taxonomy_obj->labels->singular_name ) ); ?></h1>

<?php if ( empty( $_REQUEST['locale_id'] ) ) : ?>

	<p><?php echo esc_html( $this->error_messages['invalid_term_id'] ); ?></p>

<?php else : ?>

	<?php
	$locale_id           = (int) $_REQUEST['locale_id'];
	$options             = get_option( 'plugin_multilocale' );
	$locale_taxonomy_obj = get_taxonomy( $this->_locale_taxonomy );
	$locale_obj          = get_term( $locale_id, $locale_taxonomy_obj->name, OBJECT, 'edit' );
	$active_locales      = multilocale_get_locales();
	?>

	<?php if ( ! $locale_obj ) : ?>

		<p><?php echo esc_html( $this->error_messages['invalid_term'] ); ?></p>

	<?php else : ?>

		<p><?php esc_html_e( 'You have specified this locale for deletion:', 'multilocale' ); ?></p>

		<?php
		// Todo: (handle in post admin class) If has content assigned, tell user we're deleting that content unless it is the last locale.
		?>
		<form name="multilocale-edit-locale" id="multilocale-edit-locale" method="post" action="">

			<?php wp_nonce_field( 'multilocale_settings', 'multilocale_settings' ); ?>
			<input type="hidden" name="locale_id" value="<?php echo esc_attr( $locale_id ); ?>" />
			<input type="hidden" name="action" value="delete_locale">

			<ul>
				<li><?php printf( 'ID #%d: %s', esc_html( $locale_id ), esc_html( $locale_obj->name ) ); ?></li>
			</ul>
			<?php if ( count( $active_locales ) > 1 && (int) $locale_id === (int) $options['default_locale_id'] ) : ?>
				<br />
				<fieldset>
					<label for="default_locale_id"><?php esc_html_e( 'Select a new default locale', 'multilocale' ); ?>: </label>
					<select name="default_locale_id">
						<?php
						foreach ( $active_locales as $locale ) {
							if ( (int) $locale->term_id !== (int) $options['default_locale_id'] ) {
								printf( '<option value="%d">%s</option>', esc_attr( $locale->term_id ), esc_html( $locale->name ) );
							}
						}
						?>
					</select>
				</fieldset>
			<?php endif; ?>
			<?php if ( count( $active_locales ) > 1 && $locale_obj->count > 0 ) : ?>
				<br />
				<fieldset>
					<p><legend><?php esc_html_e( 'What should be done with content in this locale?', 'multilocale' ); ?></legend></p>
					<ul style="list-style: none;">
						<li>
							<label>
								<input type="radio" id="delete_option0" name="delete_option" value="delete" /> <?php esc_html_e( 'Delete existing content', 'multilocale' ); ?>
							</label>
						</li>
						<li>
							<input type="radio" id="delete_option1" name="delete_option" value="reassign" disabled />
							<label for="delete_option1"><?php esc_html_e( 'Assign to locale', 'multilocale' ); ?>: </label>
							<select name="reassign_user" id="reassign_user" class="" disabled>
								<option value="1"><?php esc_html_e( 'Undefined', 'multilocale' ); ?></option>
							</select>
						</li>
					</ul>
				</fieldset>
			<?php endif; ?>
			<?php
			submit_button(
				__( 'Confirm Deletion', 'default' ),
				'primary',
				'submit',
				true,
				array(
					'disabled' => 'disabled',
				)
			);
			?>
		</form>


		<script type="text/javascript">
		//<![CDATA[
		jQuery( document ).ready( function( $ ) {
			var $delete_option, $submit;
			$delete_option = $( 'input[name="delete_option"]' );
			$submit = $( '#submit' );
			if ( $delete_option.length ) {
				$( 'input[name="delete_option"]' ).focus( function() {
					$submit.removeAttr( 'disabled' );
				});
			} else {
				$submit.removeAttr( 'disabled' );
			}

			$( '#multilocale-edit-locale' ).submit( function() {
				// Todo: Add styles via external css file.
				$( '#submit', this ).after( '<span class="spinner" style="visibility: visible; float: none; margin-top:-2px;" />' );
			});
		});
		//]]>
		</script>
	<?php endif; ?>

<?php endif; ?>
