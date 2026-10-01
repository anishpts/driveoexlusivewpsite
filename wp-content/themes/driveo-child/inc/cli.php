<?php
/**
 * WP-CLI: reproducible setup of the one-page site.
 *
 *   wp driveo install   Import media, apply Elementor kit settings, create the menu,
 *                       build the page from templates/driveo-home.json, set it as home.
 *   wp driveo export    Write the (edited) page back to templates/driveo-home.json.
 *
 * @package driveo-child
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Plugin;

class Driveo_CLI {

	const TEMPLATE   = '/templates/driveo-home.json';
	const MEDIA_OPT  = 'driveo_media';
	const PAGE_SLUG  = 'home';
	const MEDIA_SEED = '/tools/media/';

	/**
	 * Seed media: key => [file, title, alt]. Credits in CREDITS.md.
	 */
	private function media_manifest(): array {
		return array(
			'hero-video'     => array( 'hero-harbour-1080.mp4', 'Harbour at night (hero film, 1080p)', '' ),
			'hero-video-sm'  => array( 'hero-harbour-720.mp4', 'Harbour at night (hero film, 720p)', '' ),
			'hero-poster'    => array( 'hero-poster.jpg', 'Harbour at night (hero poster)', '' ),
			// Decorative: the brand name always sits beside it, so the alt stays empty.
			'logo'           => array( 'logo-placeholder.png', 'Logo (placeholder)', '' ),
			'fleet-business' => array( 'fleet-business.webp', 'Business Class', 'Black saloon parked on an empty car park deck at night, headlights on' ),
			'fleet-first'    => array( 'fleet-first.webp', 'First Class', 'Close-up of a black car door reflecting warm city lights at dusk' ),
			'fleet-van'      => array( 'fleet-van.webp', 'Business Van', 'Black passenger van with its sliding door open beside a city pavement' ),
		);
	}

	/**
	 * Set up media, Elementor settings, menu and the one-page site.
	 *
	 * @when after_wp_load
	 */
	public function install() {
		$this->as_admin();
		$media = $this->import_media();
		$this->configure_elementor();
		$this->create_menu();
		$this->quote_form( false ); // create only if missing; never overwrite the live form
		$this->site_logo( $media );
		$page_id = $this->build_page( $media );
		Plugin::$instance->files_manager->clear_cache();
		WP_CLI::success( "One-page site ready: " . get_permalink( $page_id ) );
	}

	/**
	 * Create the Contact Form 7 quote form from templates/cf7-quote-form.txt.
	 *
	 * The CF7 form is the source of truth once it exists, so an existing form is left
	 * alone unless --force is given.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Overwrite the existing form (fields, mail, messages) with the template.
	 *
	 * @when after_wp_load
	 */
	public function cf7( $args, $assoc_args ) {
		$this->as_admin();
		$this->quote_form( ! empty( $assoc_args['force'] ) );
	}

	/**
	 * Check that the quote rules still match the CF7 form's options.
	 *
	 * @when after_wp_load
	 */
	public function check() {
		$form = driveo_quote_form();
		if ( $form ) {
			WP_CLI::log( sprintf( 'Quote form: Contact Form 7 #%d "%s"', $form->id(), $form->title() ) );
			foreach ( array( 'city', 'journey-type', 'vehicle', 'hours' ) as $field ) {
				WP_CLI::log( sprintf( '  %-13s %s', $field, implode( ' | ', driveo_quote_form_values( $field ) ) ) );
			}
		}
		$problems = driveo_quote_config_problems();
		if ( $problems ) {
			foreach ( $problems as $problem ) {
				WP_CLI::warning( $problem );
			}
			WP_CLI::halt( 1 );
		}
		WP_CLI::success( 'Hourly-hire service, vehicle limits and Fleet buttons all match the form options.' );
	}

	/**
	 * @return WPCF7_ContactForm
	 */
	private function quote_form( bool $overwrite ) {
		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			WP_CLI::error( 'Contact Form 7 is not active.' );
		}

		$id   = (int) get_option( DRIVEO_QUOTE_FORM_OPT );
		$form = $id ? wpcf7_contact_form( $id ) : null;
		if ( $form && ! $overwrite ) {
			$this->add_missing_messages( $form );
			WP_CLI::log( sprintf( 'Quote form exists: Contact Form 7 #%d (hash %s). Left unchanged; use `wp driveo cf7 --force` to overwrite.', $form->id(), $form->hash() ) );
			return $form;
		}
		if ( ! $form ) {
			$form = WPCF7_ContactForm::get_template( array( 'title' => 'Quote request' ) );
		}

		$template = (string) file_get_contents( DRIVEO_DIR . '/templates/cf7-quote-form.txt' );
		$defaults = WPCF7_ContactFormTemplate::mail();

		$body = implode(
			"\n",
			array(
				'Quote request [_dx_reference]',
				'',
				'Name: [client-name]',
				'Email: [client-email]',
				'Phone: [client-phone]',
				'',
				'City: [city]',
				'Journey type: [journey-type]',
				'Pickup: [pickup]',
				'Destination: [destination]',
				'Hours: [hours]',
				'Date: [pickup-date]',
				'Time: [pickup-time]',
				'Passengers: [passengers]',
				'Luggage: [luggage]',
				'Vehicle: [vehicle]',
				'',
				'Notes: [notes]',
				'',
				'--',
				'Sent from [_site_title] ([_site_url]) on [_date] at [_time]',
			)
		);

		$form->set_properties(
			array(
				'form'                => $template,
				'mail'                => array(
					'active'             => true,
					'subject'            => 'Quote request [_dx_reference] · [city] · [journey-type]',
					// Site-domain sender; the visitor only ever appears as Reply-To.
					'sender'             => $defaults['sender'],
					'recipient'          => '[_site_admin_email]',
					'body'               => $body,
					'additional_headers' => 'Reply-To: [client-name] <[client-email]>',
					'attachments'        => '',
					'use_html'           => false,
					// Drops lines whose mail-tags are empty (Destination for hourly hire, Hours otherwise, Notes).
					'exclude_blank'      => true,
				),
				'mail_2'              => array_merge( WPCF7_ContactFormTemplate::mail_2(), array( 'active' => false ) ),
				'messages'            => array_merge(
					(array) $form->prop( 'messages' ),
					array(
						'mail_sent_ok'     => 'Thank you. Your request has been received.',
						'mail_sent_ng'     => 'We could not send your request just now. Please try again, or call us directly.',
						'validation_error' => 'Please check the highlighted fields and try again.',
						'spam'             => 'Your request could not be sent. Please try again later, or call us directly.',
					)
				),
				'additional_settings' => '',
			)
		);
		$form->save();
		$form = wpcf7_contact_form( $form->id() ); // reload so a new form reports its hash

		update_option( DRIVEO_QUOTE_FORM_OPT, $form->id(), false );
		WP_CLI::log( sprintf( 'Quote form ready: Contact Form 7 #%d (hash %s).', $form->id(), $form->hash() ) );
		return $form;
	}

	/**
	 * Add the theme's validation messages to the form's Messages tab where they are
	 * missing. Existing wording (including admin edits) is never changed.
	 */
	private function add_missing_messages( $form ) {
		$messages = (array) $form->prop( 'messages' );
		$added    = array();
		foreach ( wpcf7_messages() as $key => $message ) {
			if ( 0 === strpos( $key, 'dx_' ) && ! isset( $messages[ $key ] ) ) {
				$messages[ $key ] = $message['default'];
				$added[]          = $key;
			}
		}
		if ( $added ) {
			$form->set_properties( array( 'messages' => $messages ) );
			$form->save();
			WP_CLI::log( 'Added quote-form messages to the CF7 Messages tab: ' . implode( ', ', $added ) );
		}
	}

	/**
	 * Use the seed logo as the Site Identity logo, unless a logo is already set.
	 */
	private function site_logo( array $media ) {
		if ( get_theme_mod( 'custom_logo' ) ) {
			WP_CLI::log( 'Site logo already set; left unchanged.' );
			return;
		}
		if ( ! empty( $media['logo'] ) ) {
			set_theme_mod( 'custom_logo', (int) $media['logo'] );
			WP_CLI::log( sprintf( 'Site logo set to attachment #%d (Appearance → Customize → Site Identity).', $media['logo'] ) );
		}
	}

	/**
	 * Export the current page structure back to the template JSON.
	 *
	 * @when after_wp_load
	 */
	public function export() {
		$page = get_page_by_path( self::PAGE_SLUG );
		if ( ! $page ) {
			WP_CLI::error( 'No page to export. Run `wp driveo install` first.' );
		}
		$data  = json_decode( (string) get_post_meta( $page->ID, '_elementor_data', true ), true );
		$media = (array) get_option( self::MEDIA_OPT, array() );

		// Turn attachment IDs/URLs back into placeholders so the JSON stays portable.
		$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		foreach ( $media as $key => $id ) {
			$url  = wp_get_attachment_url( $id );
			$json = str_replace( wp_json_encode( $url, JSON_UNESCAPED_SLASHES ), '"{{media:' . $key . ':url}}"', $json );
			$json = str_replace( $url, '{{media:' . $key . ':url}}', $json );
			$json = preg_replace( '/"id":\s*"?' . $id . '"?(,\s*"url":\s*"\{\{media:' . $key . ')/', '"id": "{{media:' . $key . ':id}}"$1', $json );
		}
		// The quote form's ID/hash is site-specific: export the placeholder the installer resolves.
		$form = driveo_quote_form();
		if ( $form ) {
			$json = preg_replace( '/contact-form-7 id=\\\\"(' . preg_quote( $form->hash(), '/' ) . '|' . (int) $form->id() . ')\\\\"/', 'contact-form-7 id=\\"{{cf7:quote}}\\"', $json );
		}

		$template            = json_decode( (string) file_get_contents( DRIVEO_DIR . self::TEMPLATE ), true );
		$template['content'] = json_decode( $json, true );
		file_put_contents( DRIVEO_DIR . self::TEMPLATE, wp_json_encode( $template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" );
		WP_CLI::success( 'Exported to ' . DRIVEO_DIR . self::TEMPLATE );
	}

	private function as_admin() {
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			)
		);
		if ( ! $admins ) {
			WP_CLI::error( 'No administrator account found.' );
		}
		wp_set_current_user( (int) $admins[0] );
	}

	private function import_media(): array {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$map = (array) get_option( self::MEDIA_OPT, array() );
		foreach ( $this->media_manifest() as $key => list( $file, $title, $alt ) ) {
			if ( ! empty( $map[ $key ] ) && get_post( $map[ $key ] ) ) {
				continue;
			}
			$source = DRIVEO_DIR . self::MEDIA_SEED . $file;
			if ( ! file_exists( $source ) ) {
				WP_CLI::error( "Missing seed media: $source" );
			}
			$tmp = wp_tempnam( $file );
			copy( $source, $tmp );
			$id = media_handle_sideload(
				array(
					'name'     => $file,
					'tmp_name' => $tmp,
				),
				0,
				$title
			);
			if ( is_wp_error( $id ) ) {
				WP_CLI::error( "$file: " . $id->get_error_message() );
			}
			update_post_meta( $id, '_wp_attachment_image_alt', $alt );
			$map[ $key ] = $id;
			WP_CLI::log( "Imported $file (#$id)" );
		}
		update_option( self::MEDIA_OPT, $map, false );
		return $map;
	}

	private function configure_elementor() {
		// Features used by the template: containers and lean markup.
		update_option( 'elementor_experiment-container', 'active' );
		update_option( 'elementor_experiment-e_optimized_markup', 'active' );
		update_option( 'elementor_experiment-additional_custom_breakpoints', 'active' );

		// Design comes from the theme: no default colours/fonts, no Google Fonts requests.
		update_option( 'elementor_disable_color_schemes', 'yes' );
		update_option( 'elementor_disable_typography_schemes', 'yes' );
		update_option( 'elementor_google_font', '0' );
		update_option( 'elementor_font_display', 'swap' );
		update_option( 'elementor_load_fa4_shim', '' );
		update_option( 'elementor_cpt_support', array( 'page', 'post' ) );

		$kit = Plugin::$instance->kits_manager->get_active_kit();
		if ( ! $kit || ! $kit->get_id() ) {
			WP_CLI::error( 'No active Elementor kit.' );
		}

		$color = static fn( $id, $title, $hex ) => array(
			'_id'   => $id,
			'title' => $title,
			'color' => $hex,
		);
		$type  = static fn( $id, $title, $family, $weight ) => array(
			'_id'                    => $id,
			'title'                  => $title,
			'typography_typography'  => 'custom',
			'typography_font_family' => $family,
			'typography_font_weight' => $weight,
		);
		$zero  = array(
			'unit'     => 'px',
			'top'      => '0',
			'right'    => '0',
			'bottom'   => '0',
			'left'     => '0',
			'isLinked' => true,
		);

		$settings = array_merge(
			(array) $kit->get_settings(),
			array(
				'system_colors'         => array(
					$color( 'primary', 'Cream', '#EFE7DA' ),
					$color( 'secondary', 'Muted text', '#A49A8D' ),
					$color( 'text', 'Body text', '#EFE7DA' ),
					$color( 'accent', 'Copper', '#DEA074' ),
				),
				'custom_colors'         => array(
					$color( 'dxbg', 'Background', '#0D0907' ),
					$color( 'dxsurface', 'Surface', '#18130F' ),
					$color( 'dxmuted', 'Muted', '#221B17' ),
					$color( 'dxcopperdeep', 'Copper on cream', '#8F5A34' ),
				),
				'system_typography'     => array(
					$type( 'primary', 'Display', 'Fraunces', '500' ),
					$type( 'secondary', 'Labels', 'Space Mono', '400' ),
					$type( 'text', 'Body', 'Manrope', '400' ),
					$type( 'accent', 'Buttons', 'Space Mono', '700' ),
				),
				'body_background_background' => 'classic',
				'body_background_color' => '#0D0907',
				'container_width'       => array(
					'unit'  => 'px',
					'size'  => 1320,
					'sizes' => array(),
				),
				'container_padding'     => $zero,
				'space_between_widgets' => array(
					'column'   => '0',
					'row'      => '0',
					'isLinked' => true,
					'unit'     => 'px',
					'size'     => 0,
				),
				'viewport_mobile'       => 639,
				'viewport_mobile_extra' => 767,
				'viewport_tablet'       => 1023,
				'active_breakpoints'    => array( 'viewport_mobile', 'viewport_mobile_extra', 'viewport_tablet' ),
			)
		);

		$kit->save( array( 'settings' => $settings ) );
		WP_CLI::log( 'Elementor kit configured (breakpoints 639/767/1023, 1320px width).' );
	}

	private function create_menu() {
		$name = 'One-page navigation';
		$menu = wp_get_nav_menu_object( $name );
		$id   = $menu ? $menu->term_id : wp_create_nav_menu( $name );

		if ( ! $menu ) {
			foreach ( array( 'Services' => '#services', 'Fleet' => '#fleet', 'Cities' => '#cities', 'Process' => '#process' ) as $title => $url ) {
				wp_update_nav_menu_item(
					$id,
					0,
					array(
						'menu-item-title'  => $title,
						'menu-item-url'    => $url,
						'menu-item-type'   => 'custom',
						'menu-item-status' => 'publish',
					)
				);
			}
		}

		$locations                   = (array) get_theme_mod( 'nav_menu_locations', array() );
		$locations['driveo-primary'] = $id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	private function build_page( array $media ): int {
		$template = json_decode( (string) file_get_contents( DRIVEO_DIR . self::TEMPLATE ), true );
		if ( ! $template ) {
			WP_CLI::error( 'Template JSON missing or invalid. Run: php tools/build-template.php' );
		}

		$content = $this->resolve_media( $template['content'], $media );

		$page = get_page_by_path( self::PAGE_SLUG );
		$id   = $page ? $page->ID : wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Home',
				'post_name'   => self::PAGE_SLUG,
			)
		);

		update_post_meta( $id, '_wp_page_template', 'elementor_canvas' );
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );

		$document = Plugin::$instance->documents->get( $id, false );
		$document->save(
			array(
				'elements' => $content,
				'settings' => $template['page_settings'] ?? array(),
			)
		);

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $id );
		update_option( 'blogname', 'Atelier Chauffeurs' );
		update_option( 'blogdescription', 'Discreet chauffeured travel' );

		return (int) $id;
	}

	/**
	 * Replace {{media:key:id|url}} placeholders anywhere in the element tree.
	 */
	private function resolve_media( $node, array $media ) {
		if ( is_array( $node ) ) {
			foreach ( $node as $k => $v ) {
				$node[ $k ] = $this->resolve_media( $v, $media );
			}
			return $node;
		}
		if ( is_string( $node ) && false !== strpos( $node, '{{cf7:quote}}' ) ) {
			$form = wpcf7_contact_form( (int) get_option( DRIVEO_QUOTE_FORM_OPT ) );
			$node = str_replace( '{{cf7:quote}}', $form ? $form->hash() : '', $node );
		}
		if ( ! is_string( $node ) || false === strpos( $node, '{{media:' ) ) {
			return $node;
		}
		if ( preg_match( '/^\{\{media:([a-z0-9-]+):id\}\}$/', $node, $m ) ) {
			return (int) ( $media[ $m[1] ] ?? 0 );
		}
		return preg_replace_callback(
			'/\{\{media:([a-z0-9-]+):url\}\}/',
			fn( $m ) => isset( $media[ $m[1] ] ) ? (string) wp_get_attachment_url( $media[ $m[1] ] ) : '',
			$node
		);
	}
}

WP_CLI::add_command( 'driveo', 'Driveo_CLI' );
