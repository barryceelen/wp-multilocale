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
	/*
	 * Register tabs for the options page by adding a filter to 'multilocale_options_page_tabs'.
	 * Format: array( 'name' => 'string', 'action' => 'string' ).
	 * If there is only one tab, we'll render it as the page title.
	 */
	$default_tabs = array(
		'locales' => __( 'Locales', 'multilocale' ),
	);
	$tabs = apply_filters(
		'multilocale_options_page_tabs',
		$default_tabs
	);

	$current_action = ( empty( $_GET['action'] ) ) ? 'locales' : (string) $_GET['action'];

	if ( count( $tabs ) == 1 ) {
		foreach ( $tabs as $action => $title ) {
			$page_title = sprintf( '<h1>%s</h1>', esc_html( $title ) );
		}
	} else {
		$html = array();
		foreach ( $tabs as $action => $title ) {
			$html[] = sprintf(
				'<a href="%s" class="nav-tab%s">%s</a>',
				add_query_arg( array( 'action' => $action ) ),
				( $action == $current_action ) ? ' nav-tab-active': '',
				esc_html( $title )
			);
		}
		$page_title = sprintf( '<h1>Multilocale</h1><h2 class="nav-tab-wrapper">%s</h2>', implode( '', $html ) );
	}

	do_action( 'multilocale_settings_page_content', $current_action, $page_title );
	?>
</div>
