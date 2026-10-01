<?php
/**
 * Inline SVG icons from Tabler Icons v3.48.0 (MIT, https://tabler.io/icons).
 *
 * @package driveo-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return an icon as inline SVG. Icons are decorative; label the parent control instead.
 */
function driveo_icon( string $name ): string {
	static $paths = array(
		'arrow-down-right' => array( 'M7 7l10 10', 'M17 8l0 9l-9 0' ),
		'arrow-up-right' => array( 'M17 7l-10 10', 'M8 7l9 0l0 9' ),
		'brand-facebook' => array( 'M7 10v4h3v7h4v-7h3l1 -4h-4v-2a1 1 0 0 1 1 -1h3v-4h-3a5 5 0 0 0 -5 5v2h-3' ),
		'brand-instagram' => array( 'M4 8a4 4 0 0 1 4 -4h8a4 4 0 0 1 4 4v8a4 4 0 0 1 -4 4h-8a4 4 0 0 1 -4 -4l0 -8', 'M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0', 'M16.5 7.5v.01' ),
		'brand-linkedin' => array( 'M8 11v5', 'M8 8v.01', 'M12 16v-5', 'M16 16v-3a2 2 0 1 0 -4 0', 'M3 7a4 4 0 0 1 4 -4h10a4 4 0 0 1 4 4v10a4 4 0 0 1 -4 4h-10a4 4 0 0 1 -4 -4l0 -10' ),
		'brand-tiktok' => array( 'M21 7.917v4.034a9.948 9.948 0 0 1 -5 -1.951v4.5a6.5 6.5 0 1 1 -8 -6.326v4.326a2.5 2.5 0 1 0 4 2v-11.5h4.083a6.005 6.005 0 0 0 4.917 4.917' ),
		'brand-whatsapp' => array( 'M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9', 'M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1' ),
		'check' => array( 'M5 12l5 5l10 -10' ),
		'chevron-down' => array( 'M6 9l6 6l6 -6' ),
		'mail' => array( 'M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10', 'M3 7l9 6l9 -6' ),
		'minus' => array( 'M5 12l14 0' ),
		'phone' => array( 'M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2' ),
		'player-pause' => array( 'M6 6a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v12a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1l0 -12', 'M14 6a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v12a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1l0 -12' ),
		'player-play' => array( 'M7 4v16l13 -8l-13 -8' ),
		'plus' => array( 'M12 5l0 14', 'M5 12l14 0' ),
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	$d = '';
	foreach ( $paths[ $name ] as $path ) {
		$d .= '<path d="' . $path . '"/>';
	}
	return '<svg class="dx-icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d . '</svg>';
}
