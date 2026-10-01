<?php
/**
 * Generates templates/driveo-home.json — the Elementor structure of the one-page site.
 *
 * Run:  php tools/build-template.php
 * Then: wp driveo install   (imports media, kit settings, menu and this page)
 *
 * Content lives here once so the JSON stays reproducible. After the page has been
 * edited in Elementor, `wp driveo export` writes the edited version back to the JSON.
 *
 * Media placeholders ({{media:<key>:id|url}}) are resolved by the importer.
 *
 * @package driveo-child
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

/* ---------------------------------------------------------------------------
 * Element helpers
 * ------------------------------------------------------------------------ */

function dx_id(): string {
	static $n = 0;
	return substr( md5( 'driveo-' . ( ++$n ) ), 0, 7 );
}

/**
 * Container. $s: tag, class, id, dir (row|column), grid => [desktop, tablet, mobile_extra, mobile].
 */
function con( array $s, array $children, bool $inner = true ): array {
	$settings = array(
		'content_width' => 'full',
		'html_tag'      => $s['tag'] ?? 'div',
		'css_classes'   => $s['class'] ?? '',
	);
	if ( ! empty( $s['id'] ) ) {
		$settings['_element_id'] = $s['id'];
	}
	if ( ! empty( $s['label'] ) ) {
		$settings['_title'] = $s['label'];
	}
	if ( isset( $s['grid'] ) ) {
		$settings['container_type'] = 'grid';
		foreach ( array( '', '_tablet', '_mobile_extra', '_mobile' ) as $i => $suffix ) {
			$cols = $s['grid'][ $i ];
			$settings[ 'grid_columns_grid' . $suffix ] = is_int( $cols )
				? array( 'unit' => 'fr', 'size' => $cols, 'sizes' => array() )
				: array( 'unit' => 'custom', 'size' => $cols, 'sizes' => array() );
		}
		$settings['grid_rows_grid'] = array( 'unit' => 'custom', 'size' => 'auto', 'sizes' => array() );
	} else {
		$settings['container_type'] = 'flex';
		$settings['flex_direction'] = $s['dir'] ?? 'column';
	}
	return array(
		'id'       => dx_id(),
		'elType'   => 'container',
		'isInner'  => $inner,
		'settings' => $settings,
		'elements' => $children,
	);
}

function widget( string $type, array $settings, string $class = '', string $id = '' ): array {
	if ( $class ) {
		$settings['_css_classes'] = $class;
	}
	if ( $id ) {
		$settings['_element_id'] = $id;
	}
	return array(
		'id'         => dx_id(),
		'elType'     => 'widget',
		'widgetType' => $type,
		'isInner'    => false,
		'settings'   => $settings,
		'elements'   => array(),
	);
}

function heading( string $title, string $tag, string $class = '', string $link = '' ): array {
	$s = array(
		'title'       => $title,
		'header_size' => $tag,
	);
	if ( $link ) {
		$s['link'] = array( 'url' => $link, 'is_external' => '', 'nofollow' => '' );
	}
	return widget( 'heading', $s, $class );
}

function text( string $html, string $class = '' ): array {
	return widget( 'text-editor', array( 'editor' => $html ), $class );
}

function button( string $label, string $url, string $class, string $attrs = '' ): array {
	return widget(
		'button',
		array(
			'text' => $label,
			'size' => 'sm',
			'link' => array(
				'url'               => $url,
				'is_external'       => '',
				'nofollow'          => '',
				'custom_attributes' => $attrs,
			),
		),
		'dx-btn ' . $class
	);
}

function shortcode( string $code, string $class = '' ): array {
	return widget( 'shortcode', array( 'shortcode' => $code ), $class );
}

function image( string $media_key, string $class ): array {
	return widget(
		'image',
		array(
			'image'      => array(
				'id'  => '{{media:' . $media_key . ':id}}',
				'url' => '{{media:' . $media_key . ':url}}',
			),
			'image_size' => 'large',
		),
		$class
	);
}

function eyebrow( string $label ): array {
	return heading( $label, 'p', 'dx-eyebrow' );
}

/**
 * Logo tile: the "Driveo Logo" widget. It shows the Site Identity logo, so replacing the
 * logo once (Appearance → Customize → Site Identity) updates every placement; each
 * placement can still override it with its own image in the Elementor panel.
 * Decorative: the brand name or heading beside it carries the meaning.
 */
function logo( string $class, string $link = '' ): array {
	$s = array(
		'source'     => 'site',
		'decorative' => 'yes',
	);
	if ( $link ) {
		$s['link'] = array(
			'url'         => $link,
			'is_external' => '',
			'nofollow'    => '',
		);
	}
	return widget( 'driveo-logo', $s, 'dx-logo ' . $class );
}

/**
 * Icon links: the "Driveo Social Links" widget (one row per link: icon, URL, accessible name).
 *
 * @param array $links [ network, url, accessible name ].
 */
function social( array $links, string $size, string $list_label, string $class = '' ): array {
	return widget(
		'driveo-social-links',
		array(
			'items'      => array_map(
				fn( $link ) => array(
					'_id'     => dx_id(),
					'network' => $link[0],
					'url'     => array(
						'url'         => $link[1],
						'is_external' => 'on',
						'nofollow'    => '',
					),
					'label'   => $link[2],
				),
				$links
			),
			'size'       => $size,
			'list_label' => $list_label,
		),
		$class
	);
}

/**
 * Logo + brand name, used in the header and footer.
 */
function brand( string $name, string $modifier = '' ): array {
	return con(
		array(
			'class' => trim( 'dx-brand ' . $modifier ),
			'dir'   => 'row',
		),
		array(
			logo( '', '#top' ),
			heading( $name, 'div', 'dx-brand-name', '#top' ),
		)
	);
}

/**
 * Empty, labelled section reserved for a later phase. It already carries its final
 * anchor so navigation targets stay stable; drop widgets into it in Elementor.
 */
function reserved( string $id, string $label ): array {
	return con(
		array(
			'tag'   => 'section',
			'id'    => $id,
			'class' => 'dx-reserved',
			'label' => 'Reserved — ' . $label . ' (later phase)',
		),
		array()
	);
}

/* ---------------------------------------------------------------------------
 * Content (placeholder brand: "Atelier Chauffeurs" — replace in Elementor)
 * ------------------------------------------------------------------------ */

$brand     = 'Atelier Chauffeurs';
$whatsapp  = 'https://wa.me/447700900000';
$cities    = array( 'London', 'Paris', 'Bulgaria', 'Dubai' );

/*
 * Sections built in this phase. The others render as empty reserved sections with
 * their final IDs. Phase 1: header, hero, assurance, footer. Phase 2: services, fleet. Phase 3: cities, process. Phase 4: quote. Phase 5: testimonial.
 */
$build = array( 'hero', 'assurance', 'services', 'fleet', 'cities', 'process', 'quote', 'testimonial' );
$city_list = '<ul>' . implode( '', array_map( fn( $c ) => "<li>$c</li>", $cities ) ) . '</ul>';

$header = con(
	array(
		'tag'   => 'header',
		'class' => 'dx-header',
		'dir'   => 'row',
		'label' => 'Header',
	),
	array(
		brand( $brand ),
		shortcode( '[driveo_nav label="Main navigation" menu_label="Menu"]', 'dx-header-nav' ),
		button( 'Request quote', '#quote', 'dx-btn--primary dx-btn--arrow dx-header-cta' ),
	),
	false
);

$hero = con(
	array(
		'tag'   => 'section',
		'id'    => 'top',
		'class' => 'dx-hero',
		'label' => 'Hero',
	),
	array(
		widget(
			'driveo-hero-media',
			array(
				'video'        => array(
					'id'  => '{{media:hero-video:id}}',
					'url' => '{{media:hero-video:url}}',
				),
				'video_mobile' => array(
					'id'  => '{{media:hero-video-sm:id}}',
					'url' => '{{media:hero-video-sm:url}}',
				),
				'poster'       => array(
					'id'  => '{{media:hero-poster:id}}',
					'url' => '{{media:hero-poster:url}}',
				),
				'rail'         => "Discreet\nPunctual\nComposed",
				'pause_label'  => 'Pause background video',
				'play_label'   => 'Play background video',
			),
			'dx-hero-media'
		),
		con(
			array( 'class' => 'dx-hero-content' ),
			array(
				logo( 'dx-logo--xl dx-hero-logo' ),
				heading( 'Chauffeured travel · By appointment', 'p', 'dx-eyebrow dx-rise' ),
				heading( 'Discreet travel. <em>Arrive at ease.</em>', 'h1', 'dx-hero-title dx-rise-delay' ),
				text( '<p>Fares agreed in writing before you travel, professional drivers in business dress, flights monitored as standard and cars kept spotless. Privacy is part of the service.</p>', 'dx-hero-lede' ),
				con(
					array(
						'class' => 'dx-hero-actions',
						'dir'   => 'row',
					),
					array(
						button( 'Get a fixed quote', '#quote', 'dx-btn--primary dx-btn--lg dx-btn--arrow' ),
						social( array( array( 'whatsapp', $whatsapp, 'Message us on WhatsApp (opens in a new tab)' ) ), 'lg', 'Contact' ),
					)
				),
				text( $city_list, 'dx-hero-cities' ),
			)
		),
	)
);

$assurance_items = array(
	array( 'Professional drivers', 'Licensed, background-checked and dressed for business.' ),
	array( 'Flights monitored', 'Pickup times move with your arrival, automatically.' ),
	array( 'Fares agreed upfront', 'One written price. No meters, no surge, no extras.' ),
	array( 'Strict privacy', 'Names, routes and addresses stay confidential.' ),
);
$assurance       = con(
	array(
		'tag'   => 'section',
		'id'    => 'assurance',
		'class' => 'dx-section dx-section--alt dx-assurance',
		'grid'  => array( 4, 2, 2, 1 ),
		'label' => 'Assurance',
	),
	array_merge(
		array( heading( 'Our standards', 'h2', 'dx-sr' ) ),
		array_map(
			fn( $item, $i ) => con(
				array(
					'tag'   => 'article',
					'class' => 'dx-assure',
				),
				array(
					heading( sprintf( '%02d', $i + 1 ), 'p', 'dx-index' ),
					heading( $item[0], 'h3', 'dx-card-title' ),
					text( '<p>' . $item[1] . '</p>', 'dx-card-copy' ),
				)
			),
			$assurance_items,
			array_keys( $assurance_items )
		)
	)
);

$service_items = array(
	array( 'Arrivals', 'Airport transfers', 'Arrivals tracked in real time, a named greeting in the hall and an hour of waiting included.' ),
	array( 'Direct', 'Point to point', 'One fixed fare agreed in advance for any door-to-door journey, across town or across the country.' ),
	array( 'By the hour', 'Chauffeur on call', 'Your driver stays close by for meetings, viewings and every stop that follows.' ),
	array( 'Occasions', 'Events & long distance', 'Weddings, galas and extended road trips, planned with you and priced in writing.' ),
);
$services      = con(
	array(
		'tag'   => 'section',
		'id'    => 'services',
		'class' => 'dx-section',
		'label' => 'Services',
	),
	array(
		con(
			array(
				'class' => 'dx-section-heading',
				'grid'  => array( '1.2fr .8fr', '1.2fr .8fr', '1.2fr .8fr', 1 ),
			),
			array(
				con(
					array(),
					array(
						eyebrow( 'Services' ),
						heading( 'Chauffeured four ways', 'h2', 'dx-section-title' ),
					)
				),
				text( '<p>Every booking is priced individually. Share the details and receive a single, written fare.</p>', 'dx-intro' ),
			)
		),
		con(
			array(
				'class' => 'dx-services-grid',
				'grid'  => array( 2, 2, 2, 1 ),
			),
			array_map(
				fn( $item, $i ) => con(
					array(
						'tag'   => 'article',
						'class' => 'dx-service',
					),
					array(
						heading( sprintf( '%02d · %s', $i + 1, $item[0] ), 'p', 'dx-index' ),
						heading( $item[1], 'h3', 'dx-card-title' ),
						text( '<p>' . $item[2] . '</p>', 'dx-card-copy' ),
					)
				),
				$service_items,
				array_keys( $service_items )
			)
		),
	)
);

$fleet_items = array(
	array( 'fleet-business', 'Business Class', 'An executive saloon with a quiet cabin, Wi-Fi, device charging and bottled water on board.' ),
	array( 'fleet-first', 'First Class', 'Long-wheelbase comfort with reclining rear seats, privacy glass and individual climate control.' ),
	array( 'fleet-van', 'Business Van', 'Room for groups, families and luggage, with facing seats, Wi-Fi and USB-C at every row.' ),
);
$fleet       = con(
	array(
		'tag'   => 'section',
		'id'    => 'fleet',
		'class' => 'dx-section dx-section--alt',
		'label' => 'Fleet',
	),
	array(
		eyebrow( 'The fleet' ),
		heading( 'Three classes, one standard of care', 'h2', 'dx-section-title' ),
		con(
			array(
				'class' => 'dx-fleet-grid',
				'grid'  => array( 3, 3, 1, 1 ),
			),
			array_map(
				fn( $item ) => con(
					array(
						'tag'   => 'article',
						'class' => 'dx-fleet-card',
					),
					array(
						image( $item[0], 'dx-fleet-media' ),
						heading( $item[1], 'h3', 'dx-card-title' ),
						text( '<p>' . $item[2] . '</p>', 'dx-card-copy' ),
						button( 'Quote this car', '#quote', 'dx-btn--primary dx-btn--arrow dx-fleet-button', 'data-vehicle|' . $item[1] . ',aria-label|Quote this car: ' . $item[1] ),
					)
				),
				$fleet_items
			)
		),
	)
);

$city_items = array(
	array( 'London', 'Heathrow · City · Mayfair' ),
	array( 'Paris', 'CDG · Orly · Rive Gauche' ),
	array( 'Bulgaria', 'Sofia · Varna · Bansko' ),
	array( 'Dubai', 'DXB · Marina · Downtown' ),
);
$cities_sec = con(
	array(
		'tag'   => 'section',
		'id'    => 'cities',
		'class' => 'dx-section dx-cities',
		'label' => 'Cities',
	),
	array(
		con(
			array(
				'class' => 'dx-cities-layout',
				'grid'  => array( '.8fr 1.2fr', 1, 1, 1 ),
			),
			array(
				con(
					array(),
					array(
						eyebrow( 'Where we operate' ),
						heading( 'Four cities.<br>One standard.', 'h2', 'dx-section-title' ),
						text( '<p>The same trained drivers, well-kept cars and fixed-fare approach in every city we serve.</p>', 'dx-intro' ),
					)
				),
				con(
					array(
						'class' => 'dx-city-grid',
						'grid'  => array( 2, 2, 2, 2 ),
					),
					array_map(
						fn( $item, $i ) => con(
							array(
								'tag'   => 'article',
								'class' => 'dx-city-cell',
							),
							array(
								heading( sprintf( '%02d', $i + 1 ), 'p', 'dx-index' ),
								heading( $item[0], 'h3', 'dx-card-title' ),
								heading( $item[1], 'p', 'dx-caption' ),
							)
						),
						$city_items,
						array_keys( $city_items )
					)
				),
			)
		),
	)
);

$steps   = array(
	array( 'Share the journey', 'City, service, date and stops — the quote form takes about a minute.' ),
	array( 'Receive your fare', 'A member of our team prices it and replies with one all-inclusive figure.' ),
	array( 'Take your seat', 'Your driver arrives early, waits patiently and handles the rest.' ),
);
$process = con(
	array(
		'tag'   => 'section',
		'id'    => 'process',
		'class' => 'dx-section dx-section--alt dx-process',
		'label' => 'Process',
	),
	array(
		eyebrow( 'How it works' ),
		heading( 'Three steps, nothing more', 'h2', 'dx-section-title' ),
		con(
			array(
				'class' => 'dx-process-grid',
				'grid'  => array( 3, 3, 1, 1 ),
			),
			array_map(
				fn( $item, $i ) => con(
					array(
						'tag'   => 'article',
						'class' => 'dx-process-step',
					),
					array(
						heading( sprintf( '%02d', $i + 1 ), 'p', 'dx-process-number' ),
						heading( $item[0], 'h3', 'dx-card-title' ),
						text( '<p>' . $item[1] . '</p>', 'dx-card-copy' ),
					)
				),
				$steps,
				array_keys( $steps )
			)
		),
	)
);

$quote = con(
	array(
		'tag'   => 'section',
		'id'    => 'quote',
		'class' => 'dx-section dx-quote-band',
		'label' => 'Quote',
	),
	array(
		shortcode( '[driveo_orbit]', 'dx-orbit-widget' ),
		con(
			array(
				'class' => 'dx-quote-layout',
				'grid'  => array( '.78fr 1.22fr', 1, 1, 1 ),
			),
			array(
				con(
					array( 'class' => 'dx-quote-intro' ),
					array(
						eyebrow( 'Private enquiry' ),
						heading( 'Where shall we collect you?', 'h2', 'dx-quote-title' ),
						text( '<p>Tell us the essentials. Your request is stored securely and reviewed by a member of our team.</p>', 'dx-quote-copy' ),
						con(
							array(
								'class' => 'dx-seal',
								'dir'   => 'row',
							),
							array(
								logo( 'dx-logo--seal' ),
								text( '<p>Fixed fare<br>Personal reply</p>', 'dx-seal-text' ),
							)
						),
					)
				),
				// The form itself lives in Contact Form 7 (Contact → Contact Forms → "Quote request").
				shortcode( '[contact-form-7 id="{{cf7:quote}}" title="Quote request" html_class="dx-quote-form"]', 'dx-quote-card' ),
			)
		),
	)
);

$testimonial = con(
	array(
		'tag'   => 'section',
		'id'    => 'testimonial',
		'class' => 'dx-section dx-testimonial',
		'label' => 'Testimonial',
	),
	array(
		// The eyebrow is the section's h2 so the outline stays h1 → h2 → h3.
		heading( 'Client note', 'h2', 'dx-eyebrow' ),
		// Placeholder: replace with a real, attributable client quote before launch.
		text( '<figure><blockquote><p>“Four cities in one week, and every car was waiting before we landed. It is the one part of the trip I no longer double-check.”</p></blockquote><figcaption class="dx-testimonial-attribution">Placeholder — replace with a verified client quote</figcaption></figure>', 'dx-testimonial-quote' ),
	)
);

$main = con(
	array(
		'tag'   => 'main',
		'id'    => 'main',
		'class' => 'dx-main',
		'label' => 'Main',
	),
	array(
		$hero,
		$assurance,
		in_array( 'services', $build, true ) ? $services : reserved( 'services', 'Services' ),
		in_array( 'fleet', $build, true ) ? $fleet : reserved( 'fleet', 'Fleet' ),
		in_array( 'cities', $build, true ) ? $cities_sec : reserved( 'cities', 'Cities' ),
		in_array( 'process', $build, true ) ? $process : reserved( 'process', 'Process' ),
		in_array( 'quote', $build, true ) ? $quote : reserved( 'quote', 'Quote' ),
		in_array( 'testimonial', $build, true ) ? $testimonial : reserved( 'testimonial', 'Testimonial' ),
	),
	false
);

$footer = con(
	array(
		'tag'   => 'footer',
		'id'    => 'contact',
		'class' => 'dx-footer',
		'label' => 'Footer',
	),
	array(
		con(
			array(
				'class' => 'dx-footer-grid',
				'grid'  => array( '1.5fr 1fr 1fr', '1.5fr 1fr 1fr', '1.5fr 1fr 1fr', 1 ),
			),
			array(
				con(
					array(),
					array(
						brand( $brand, 'dx-brand--footer' ),
						heading( 'Arrive at ease.', 'p', 'dx-footer-tagline' ),
						button( 'Request a quote', '#quote', 'dx-btn--primary dx-btn--arrow-ne' ),
					)
				),
				con(
					array(),
					array(
						heading( 'Contact', 'h2', 'dx-footer-label' ),
						text( '<p><a href="tel:+442079460000">+44 20 7946 0000</a><br><a href="mailto:hello@example.com">hello@example.com</a></p><p>Private bookings and enquiries</p>', 'dx-footer-contact' ),
						social(
							array(
								array( 'whatsapp', $whatsapp, 'WhatsApp (opens in a new tab)' ),
								array( 'linkedin', 'https://www.linkedin.com/', 'LinkedIn (opens in a new tab)' ),
								array( 'instagram', 'https://www.instagram.com/', 'Instagram (opens in a new tab)' ),
								array( 'facebook', 'https://www.facebook.com/', 'Facebook (opens in a new tab)' ),
							),
							'md',
							'Social media',
							'dx-footer-social'
						),
						heading( 'Install as an app on your device', 'p', 'dx-footer-label dx-install-title' ),
						shortcode( '[driveo_install label="Install app"]' ),
					)
				),
				con(
					array(),
					array(
						heading( 'Cities', 'h2', 'dx-footer-label' ),
						text( $city_list, 'dx-footer-cities' ),
					)
				),
			)
		),
		con(
			array(
				'class' => 'dx-footer-bar',
				'dir'   => 'row',
			),
			array(
				text( '<p>© [driveo_year] ' . $brand . ' Ltd</p>' ),
				text( '<p>Discreet · Punctual · Composed</p>' ),
			)
		),
	),
	false
);

$template = array(
	'version'       => '0.4',
	'title'         => 'Driveo — One page',
	'type'          => 'wp-page',
	'page_settings' => array( 'hide_title' => 'yes' ),
	'content'       => array( $header, $main, $footer ),
);

$out = dirname( __DIR__ ) . '/templates/driveo-home.json';
file_put_contents( $out, json_encode( $template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" );
echo "Wrote $out\n";
