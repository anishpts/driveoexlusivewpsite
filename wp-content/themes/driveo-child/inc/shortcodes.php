<?php
/**
 * Render helpers (used by the Elementor widgets in inc/elementor-widgets.php) and the
 * few shortcodes that remain.
 *
 * No visible marketing copy lives here: text and media arrive from Elementor controls,
 * the WordPress menu, or shortcode attributes set in the Elementor panel.
 *
 * @package driveo-child
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/icons.php';

/*
 * -------------------------------------------------------------------------
 * Render helpers
 * -------------------------------------------------------------------------
 */

/**
 * Logo image from the Media Library, optionally linked.
 *
 * Decorative logos (a brand name sits next to them) get an empty alt, and their link is
 * removed from the tab order because the brand name carries the same link.
 */
function driveo_render_logo( int $attachment_id, bool $decorative, string $link = '' ): string {
	$img = wp_get_attachment_image(
		$attachment_id,
		'medium',
		false,
		$decorative ? array( 'alt' => '' ) : array()
	);
	if ( ! $img ) {
		return '';
	}
	if ( '' === $link ) {
		return $img;
	}
	return sprintf(
		'<a href="%1$s"%2$s>%3$s</a>',
		esc_url( $link ),
		$decorative ? ' tabindex="-1" aria-hidden="true"' : '',
		$img
	);
}

/**
 * Hero background film, parallax planes, depth rail and pause/play control.
 *
 * The video has no src in the markup: driveo.js attaches it only when motion is allowed,
 * so reduced-motion and no-JS visitors see the poster.
 *
 * @param array $args video, video_sm, poster (URLs); rail (string[]); pause, play (labels).
 */
function driveo_render_hero_media( array $args ): string {
	$rail = '';
	foreach ( array_filter( array_map( 'trim', (array) ( $args['rail'] ?? array() ) ), 'strlen' ) as $word ) {
		$rail .= '<span>' . esc_html( $word ) . '</span>';
	}

	$video  = '';
	$toggle = '';
	if ( ! empty( $args['video'] ) ) {
		$video = sprintf(
			'<video class="dx-hero-video" muted playsinline preload="none" poster="%1$s" data-src="%2$s" data-src-sm="%3$s"></video>',
			esc_url( $args['poster'] ?? '' ),
			esc_url( $args['video'] ),
			esc_url( ! empty( $args['video_sm'] ) ? $args['video_sm'] : $args['video'] )
		);
		// The control is only useful with both labels; they come from the widget.
		if ( ! empty( $args['pause'] ) && ! empty( $args['play'] ) ) {
			$toggle = sprintf(
				'<button class="dx-video-toggle" type="button" hidden data-label-pause="%1$s" data-label-play="%2$s" aria-label="%1$s">%3$s%4$s</button>',
				esc_attr( $args['pause'] ),
				esc_attr( $args['play'] ),
				driveo_icon( 'player-pause' ),
				driveo_icon( 'player-play' )
			);
		}
	} elseif ( ! empty( $args['poster'] ) ) {
		$video = sprintf( '<img class="dx-hero-video" src="%s" alt="" fetchpriority="high">', esc_url( $args['poster'] ) );
	}

	return sprintf(
		'<div class="dx-hero-stage">
			<div class="dx-hero-camera" aria-hidden="true">%1$s
				<div class="dx-plane dx-plane--back"></div>
				<div class="dx-plane dx-plane--glass"></div>
				<div class="dx-light-line"></div>
			</div>
			<div class="dx-hero-shade" aria-hidden="true"></div>
			%2$s
		</div>%3$s',
		$video,
		$rail ? '<div class="dx-depth-rail" aria-hidden="true">' . $rail . '</div>' : '',
		$toggle
	);
}

/**
 * Icon choices for social links: network key => icon name used by driveo_icon().
 * These are icon identifiers, not visible text (the visible/accessible name is per link).
 */
function driveo_social_networks(): array {
	return array(
		'whatsapp'  => 'WhatsApp',
		'linkedin'  => 'LinkedIn',
		'instagram' => 'Instagram',
		'facebook'  => 'Facebook',
		'tiktok'    => 'TikTok',
	);
}

/**
 * Icon link list. Links without a URL are skipped, so no dead links reach the page.
 *
 * @param array $items Each: network, url, external (bool), label (accessible name).
 */
function driveo_render_social( array $items, string $size, string $list_label ): string {
	$links = '';
	foreach ( $items as $item ) {
		if ( empty( $item['url'] ) || ! isset( driveo_social_networks()[ $item['network'] ] ) ) {
			continue;
		}
		$links .= sprintf(
			'<li><a class="dx-social__link" href="%1$s"%2$s%3$s>%4$s</a></li>',
			esc_url( $item['url'] ),
			! empty( $item['external'] ) ? ' target="_blank" rel="noopener noreferrer"' : '',
			! empty( $item['label'] ) ? ' aria-label="' . esc_attr( $item['label'] ) . '"' : '',
			driveo_icon( 'brand-' . $item['network'] )
		);
	}
	if ( '' === $links ) {
		return '';
	}
	return sprintf(
		'<ul class="dx-social dx-social--%1$s"%2$s>%3$s</ul>',
		esc_attr( 'lg' === $size ? 'lg' : 'md' ),
		$list_label ? ' aria-label="' . esc_attr( $list_label ) . '"' : '',
		$links
	);
}

/*
 * -------------------------------------------------------------------------
 * Shortcodes
 * -------------------------------------------------------------------------
 */

/**
 * [driveo_nav label="" menu_label=""] – desktop link row plus the mobile disclosure.
 *
 * Link labels and targets come from Appearance → Menus → "One-page navigation".
 * Both attributes are set in the Elementor Shortcode widget.
 */
add_shortcode(
	'driveo_nav',
	function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'label'      => '',
				'menu_label' => '',
			),
			$atts,
			'driveo_nav'
		);

		$links     = '';
		$locations = get_nav_menu_locations();
		if ( ! empty( $locations['driveo-primary'] ) ) {
			foreach ( (array) wp_get_nav_menu_items( $locations['driveo-primary'] ) as $item ) {
				$links .= sprintf( '<li><a class="dx-nav__link" href="%s">%s</a></li>', esc_url( $item->url ), esc_html( $item->title ) );
			}
		}
		if ( '' === $links ) {
			// No menu assigned yet: show nothing to visitors, a hint to the admin.
			return current_user_can( 'edit_theme_options' )
				? '<p class="dx-nav__hint">' . esc_html__( 'Assign a menu to "One-page navigation" in Appearance → Menus.', 'driveo-child' ) . '</p>'
				: '';
		}

		return sprintf(
			'<nav class="dx-nav"%1$s>
				<button class="dx-menu-toggle" type="button" aria-expanded="false" aria-controls="dx-nav-list">
					<span class="dx-menu-toggle__bars" aria-hidden="true"></span><span class="dx-sr">%2$s</span>
				</button>
				<ul class="dx-nav__list" id="dx-nav-list">%3$s</ul>
			</nav>',
			$atts['label'] ? ' aria-label="' . esc_attr( $atts['label'] ) . '"' : '',
			esc_html( $atts['menu_label'] ),
			$links
		);
	}
);

/**
 * [driveo_install label=""] – progressive-enhancement placeholder. Stays hidden unless the
 * browser fires `beforeinstallprompt`, which needs a web app manifest and service worker
 * (not part of this build). The label is set in the Elementor Shortcode widget.
 */
add_shortcode(
	'driveo_install',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'label' => '' ), $atts, 'driveo_install' );
		if ( '' === $atts['label'] ) {
			return '';
		}
		return sprintf(
			'<button class="dx-install" type="button" hidden>%s<span>%s</span></button>',
			driveo_icon( 'arrow-down-right' ),
			esc_html( $atts['label'] )
		);
	}
);

/**
 * [driveo_year] – current year for the copyright line.
 */
add_shortcode(
	'driveo_year',
	function () {
		return esc_html( wp_date( 'Y' ) );
	}
);

/**
 * [driveo_orbit] – decorative concentric rings behind the quote form.
 */
add_shortcode(
	'driveo_orbit',
	function () {
		return '<div class="dx-orbit" aria-hidden="true"><i></i><i></i><i></i></div>';
	}
);
