<?php
/**
 * Driveo child theme bootstrap.
 *
 * @package driveo-child
 */

defined( 'ABSPATH' ) || exit;

define( 'DRIVEO_DIR', get_stylesheet_directory() );
define( 'DRIVEO_URI', get_stylesheet_directory_uri() );

require_once DRIVEO_DIR . '/inc/shortcodes.php';
require_once DRIVEO_DIR . '/inc/quote-form.php';

// Elementor widgets extend Elementor classes, so load them only once Elementor is ready.
add_action(
	'elementor/init',
	function () {
		require_once DRIVEO_DIR . '/inc/elementor-widgets.php';
	}
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once DRIVEO_DIR . '/inc/cli.php';
}

/**
 * Version an asset by its modification time so browsers pick up edits.
 */
function driveo_asset_version( string $relative ): string {
	$file = DRIVEO_DIR . '/' . ltrim( $relative, '/' );
	return file_exists( $file ) ? (string) filemtime( $file ) : wp_get_theme()->get( 'Version' );
}

/**
 * Hello Elementor's reset.css and theme.css style bare elements (links, buttons, inputs)
 * in ways that fight the design system, so driveo.css ships its own base layer instead.
 */
add_filter( 'hello_elementor_enqueue_style', '__return_false' );
add_filter( 'hello_elementor_enqueue_theme_style', '__return_false' );

add_action(
	'after_setup_theme',
	function () {
		register_nav_menus( array( 'driveo-primary' => __( 'One-page navigation', 'driveo-child' ) ) );
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'driveo', DRIVEO_URI . '/assets/css/driveo.css', array(), driveo_asset_version( 'assets/css/driveo.css' ) );

		wp_enqueue_script(
			'driveo',
			DRIVEO_URI . '/assets/js/driveo.js',
			array(),
			driveo_asset_version( 'assets/js/driveo.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		// Rules for the CF7 quote form UI; the server enforces the same values (inc/quote-form.php).
		wp_localize_script(
			'driveo',
			'driveoQuote',
			array(
				'limits' => driveo_vehicle_limits(),
				'hourly' => DRIVEO_HOURLY,
			)
		);
	},
	20
);

/**
 * Preload the two faces used above the fold. Space Mono and the italic load on demand.
 */
add_action(
	'wp_head',
	function () {
		foreach ( array( 'fraunces.woff2', 'manrope.woff2' ) as $font ) {
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
				esc_url( DRIVEO_URI . '/assets/fonts/' . $font )
			);
		}
	},
	1
);

/**
 * Skip link. The Elementor Canvas template bypasses header.php, so Hello's own link never prints.
 */
add_action(
	'wp_body_open',
	function () {
		echo '<a class="dx-skip-link" href="#main">' . esc_html__( 'Skip to content', 'driveo-child' ) . '</a>';
	},
	5
);

/**
 * Fonts are self-hosted; stop Elementor requesting them from Google.
 */
add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
