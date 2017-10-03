<?php
/**
 * Edit locale form for inclusion in plugin settings page
 *
 * @todo Set different theme per locale.
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

<h1><?php echo esc_html( $locale_taxonomy_obj->labels->edit_item ); ?></h1>

<?php
/**
 * Fires before the Edit Locale form.
 *
 * @since 0.0.1
 *
 * @param object $locale_obj            Current taxonomy term object.
 * @param string $locale_taxonomy->name Current $taxonomy slug.
 */
do_action( "multilocale_{$locale_taxonomy_obj->name}_pre_edit_form", $locale_obj, $locale_taxonomy_obj->name );
?>

<form name="multilocale-edit-locale" id="multilocale-edit-locale" method="post" action="">

	<?php wp_nonce_field( 'multilocale_settings', 'multilocale_settings' ); ?>
	<input type="hidden" name="locale_id" value="<?php echo absint( $locale_obj->term_id ); ?>" />
	<input type="hidden" name="action" value="edit_locale">
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="name"><?php esc_html_e( 'Name', 'default' ); ?></label>
				</th>
				<td>
					<?php
					printf(
						'<input name="name" id="name" type="text" value="%s" class="regular-text" aria-required="true" />',
						isset( $locale_obj->name ) ? esc_html( $locale_obj->name ) : ''
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="slug"><?php esc_html_e( 'Slug', 'default' ); ?></label>
				</th>
				<td>
					<input name="slug" id="slug" class="" type="text" value="<?php echo esc_attr( $locale_obj->slug ); ?>" />
					<p class="description"><?php esc_html_e( 'The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.', 'multilocale' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="description"><?php esc_html_e( 'WordPress Locale', 'multilocale' ); ?></label>
				</th>
				<td>
					<?php
					printf(
						'<input type="text" name="description" class="disabled" value="%s" disabled />',
						isset( $locale_obj->description ) ? esc_html( $locale_obj->description ) : ''
					);
					?>
					<p class="description"><?php esc_html_e( 'The code WordPress uses internally to indentify locales.', 'multilocale' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="blogname"><?php esc_html_e( 'Site Title', 'default' ); ?></label>
				</th>
				<td>
					<input name="blogname" class="regular-text" id="blogname" type="text" value="<?php echo esc_attr( get_term_meta( $locale_obj->term_id, 'blogname', true ) ); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="blogdescription"><?php esc_html_e( 'Tagline', 'default' ); ?></label>
				</th>
				<td>
					<input name="blogdescription" class="regular-text" id="blogdescription" type="text" value="<?php echo esc_attr( get_term_meta( $locale_obj->term_id, 'blogdescription', true ) ); ?>" size="40" />
					<p class="description"><?php esc_html_e( 'In a few words, explain what this site is about.', 'default' ); ?></p>
				</td>
			</tr>
			<tr>
			<th scope="row"><?php esc_html_e( 'Date Format', 'default' ); ?></th>
			<td>
				<fieldset><legend class="screen-reader-text"><span><?php esc_html_e( 'Date Format', 'default' ); ?></span></legend>
				<?php
				// Load the translation file for the locale we're editing so we can show the correct default date format.
				if ( 'en_US' !== $locale_obj->description ) {
					unload_textdomain( get_locale() );
					load_default_textdomain( $locale_obj->description );
				}

				foreach ( $date_formats as $format ) {
					echo "\t<label title='" . esc_attr( $format ) . "'><input type='radio' name='date_format' value='" . esc_attr( $format ) . "'";
					if ( $locale_date_format === $format ) { // checked() uses "==" rather than "===".
						echo " checked='checked'";
						$custom_date = false; // WPCS: prefix ok.
					}
					echo ' /> ' . esc_html( date_i18n( $format ) ) . "</label><br />\n";
				}

				// Load the default textdomain.
				if ( 'en_US' !== $locale_obj->description ) {
					unload_textdomain( $locale_obj->description );
					load_default_textdomain( get_locale() );
				}

				echo '	<label><input type="radio" name="date_format" id="date_format_custom_radio" value="\c\u\s\t\o\m"';
				checked( $custom_date );
				echo '/> ' . esc_html__( 'Custom:', 'default' ) . '<span class="screen-reader-text"> ' . esc_html__( 'enter a custom date format in the following field', 'default' ) . "</span></label>\n";
				echo '<label for="date_format_custom" class="screen-reader-text">' . esc_html__( 'Custom date format:', 'default' ) . '</label><input type="text" name="date_format_custom" id="date_format_custom" value="' . esc_attr( $locale_date_format ) . '" class="" /> <span class="screen-reader-text">' . esc_html__( 'example:', 'default' ) . ' </span><span class="example"> ' . esc_html( date_i18n( $locale_date_format ) ) . "</span> <span class='spinner'></span>\n";
				?>
				</fieldset>
			</td>
			</tr>
			<tr>
			<th scope="row"><?php esc_html_e( 'Time Format', 'default' ); ?></th>
			<td>
				<fieldset><legend class="screen-reader-text"><span><?php esc_html_e( 'Time Format', 'default' ); ?></span></legend>
			<?php
			// Load the translation file for the locale we're editing so we can show the correct default time format.
			if ( 'en_US' !== $locale_obj->description ) {
				unload_textdomain( get_locale() );
				load_default_textdomain( $locale_obj->description );
			}

			foreach ( $time_formats as $format ) {
				echo "\t<label title='" . esc_attr( $format ) . "'><input type='radio' name='time_format' value='" . esc_attr( $format ) . "'";
				if ( $locale_time_format === $format ) { // checked() uses "==" rather than "===".
					echo " checked='checked'";
					$custom_time = false; // WPCS: prefix ok.
				}
				echo ' /> ' . esc_html( date_i18n( $format ) ) . "</label><br />\n";
			}

			// Load the default textdomain.
			if ( 'en_US' !== $locale_obj->description ) {
				unload_textdomain( $locale_obj->description );
				load_default_textdomain( get_locale() );
			}

			echo '	<label><input type="radio" name="time_format" id="time_format_custom_radio" value="\c\u\s\t\o\m"';
			checked( $custom_time );
			echo '/> ' . esc_html__( 'Custom:', 'default' ) . '<span class="screen-reader-text"> ' . esc_html__( 'enter a custom time format in the following field', 'default' ) . "</span></label>\n";
			echo '<label for="time_format_custom" class="screen-reader-text">' . esc_html__( 'Custom time format:', 'default' ) . '</label><input type="text" name="time_format_custom" id="time_format_custom" value="' . esc_attr( $locale_time_format ) . '" class="small-text" /> <span class="screen-reader-text">' . esc_html__( 'example:', 'default' ) . ' </span><span class="example"> ' . esc_html( date_i18n( $locale_time_format ) ) . "</span> <span class='spinner'></span>\n";

			echo "\t<p>";
			echo wp_kses(
				__( '<a href="https://codex.wordpress.org/Formatting_Date_and_Time">Documentation on date and time formatting</a>.', 'default' ),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			);
			echo "</p>\n";
			?>
				</fieldset>
			</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Default Locale', 'multilocale' ); ?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Default Locale', 'multilocale' ); ?></span></legend>
						<label for="default_locale">
						<?php
						printf(
							'<input name="default_locale" type="checkbox" id="default_locale" value="%d"%s/> %s',
							absint( $locale_obj->term_id ),
							(int) $options['default_locale_id'] === (int) $locale_obj->term_id ? ' checked="checked" disabled="disabled"' : '',
							(int) $options['default_locale_id'] === (int) $locale_obj->term_id ? sprintf( esc_html__( '&#8220;%s&#8221; is the default locale', 'multilocale' ), esc_html( $locale_obj->name ) ) : sprintf( esc_html__( 'Make &#8220;%s&#8221; the default locale', 'multilocale' ), esc_html( $locale_obj->name ) )
						);
						?>
						</label>
					</fieldset>
				</td>
			</tr>
		</tbody>
	</table>

	<?php
	/**
	 * Fires at the end of the Edit Locale form.
	 *
	 * @since 0.0.1
	 *
	 * @param object $locale_obj                   Taxonomy term object.
	 * @param string $locale_taxonomy_obj->name Taxonomy slug.
	 */
	do_action( "multilocale_{$locale_taxonomy_obj->name}_edit_form", $locale_obj, $locale_taxonomy_obj->name );

	submit_button( __( 'Update', 'default' ) );
	?>
</form>

<script type="text/javascript">
//<![CDATA[
jQuery( document ).ready( function( $ ) {
	$( '#date_format_custom_radio' ).focus( function() {
		$( '#date_format_custom' ).focus();
	});
	$( '#time_format_custom_radio' ).focus( function() {
		$( '#time_format_custom' ).focus();
	});
});
//]]>
</script>
