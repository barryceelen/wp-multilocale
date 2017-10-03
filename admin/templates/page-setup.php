<?php
/**
 * Plugin setup page
 *
 * @todo Explain what is going on here, myself in six months is already confused.
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

<div class="wrap">
	<h1><?php esc_html_e( 'Multilocale Setup', 'multilocale' ); ?></h1>
	<?php
	/**
	 * Fires before the plugin setup form, but after the page title.
	 *
	 * @since 0.0.1
	 */
	do_action( 'multilocale_pre_init_form' );
	?>
	<form id="multilocale-setup" method="post" action="">
		<?php wp_nonce_field( 'multilocale_settings', 'multilocale_settings' ); ?>
		<input type="hidden" name="action" value="init">
		<table class="form-table">
			<tbody>
				<?php
				/**
				 * Fires inside the plugin setup form table.
				 *
				 * Use this hook to add table rows.
				 *
				 * @since 0.0.1
				 */
				do_action( 'multilocale_init_form_table_rows' );
				?>
			</tbody>
		</table>
		<?php
		/**
		 * Fires at the end of the plugin setup form.
		 *
		 * @since 0.0.1
		 */
		do_action( 'multilocale_init_form' );
		submit_button( __( 'Confirm', 'multilocale' ), 'primary', 'submit', true, $submit_button_attr );
		?>
	</form>
</div>

<script type="text/javascript">
//<![CDATA[
jQuery( document ).ready( function( $ ) {
	$( '#multilocale-setup' ).submit( function() {
		// Todo: Add styles via external css file.
		$( '#submit', this ).after( '<span class="spinner multilocale-init-submit-spinner" style="visibility: visible; float: none; margin-top:-2px;" />' );
	});
});
//]]>
</script>
