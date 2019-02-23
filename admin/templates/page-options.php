<?php
/**
 * Plugin options page
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
	<?php
	if ( count( $tabs ) === 1 ) {

		foreach ( $tabs as $action => $title ) { // WPCS: override ok.
			/**
			 * Fires on the settings page content area.
			 *
			 * Use this hook to add content to the settings page.
			 *
			 * @since 0.0.1
			 */
			do_action(
				'multilocale_settings_page_content',
				$current_action,
				sprintf( '<h1>%s</h1>', esc_html( $title ) )
			);
		}
	} else {

		foreach ( $tabs as $action => $title ) { // WPCS: override ok.
			$html[] = sprintf( // WPCS: prefix ok.
				'<a href="%s" class="nav-tab%s">%s</a>',
				add_query_arg(
					array(
						'action' => $action,
					)
				),
				( $action === $current_action ) ? ' nav-tab-active' : '',
				esc_html( $title )
			);
		}

		/** This action is documented in page-options.php */
		do_action(
			'multilocale_settings_page_content',
			$current_action,
			sprintf( '<h1>Multilocale</h1><h2 class="nav-tab-wrapper">%s</h2>', implode( '', $html ) )
		);
	}
	?>
</div>
