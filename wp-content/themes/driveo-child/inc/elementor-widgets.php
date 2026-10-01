<?php
/**
 * Elementor widgets for content Elementor Free has no widget for.
 *
 * All visible content and media are Elementor controls, so the admin edits them in the
 * Elementor panel (Media Library pickers, text fields, repeaters) — never in code.
 *
 *  - Driveo Logo        Site Identity logo (Appearance → Customize → Site Identity), or a
 *                       per-placement override image. One upload updates every placement.
 *  - Driveo Hero Media  Desktop video, mobile video and poster from the Media Library, plus
 *                       the depth-rail words and the pause/play control labels.
 *  - Driveo Social Links Repeater of network + URL + accessible label.
 *
 * Markup comes from the render helpers in inc/shortcodes.php, so CSS/JS behaviour is shared.
 *
 * @package driveo-child
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'elementor/elements/categories_registered',
	function ( $elements_manager ) {
		$elements_manager->add_category(
			'driveo',
			array(
				'title' => __( 'Driveo', 'driveo-child' ),
				'icon'  => 'eicon-star',
			)
		);
	}
);

add_action(
	'elementor/widgets/register',
	function ( $widgets_manager ) {
		$widgets_manager->register( new Driveo_Logo_Widget() );
		$widgets_manager->register( new Driveo_Hero_Media_Widget() );
		$widgets_manager->register( new Driveo_Social_Links_Widget() );
	}
);

/**
 * Shared base: same lean markup as core widgets (no inner wrapper), Driveo category.
 */
abstract class Driveo_Widget_Base extends \Elementor\Widget_Base {
	public function get_categories() {
		return array( 'driveo' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/**
	 * A short hint shown only inside the Elementor editor.
	 */
	protected function editor_hint( string $text ): void {
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<p style="padding:1rem;border:1px dashed #dea074;color:#dea074;font:12px/1.4 sans-serif">' . esc_html( $text ) . '</p>';
		}
	}
}

/*
 * -------------------------------------------------------------------------
 * Driveo Logo
 * -------------------------------------------------------------------------
 */

class Driveo_Logo_Widget extends Driveo_Widget_Base {
	public function get_name() {
		return 'driveo-logo';
	}

	public function get_title() {
		return __( 'Driveo Logo', 'driveo-child' );
	}

	public function get_icon() {
		return 'eicon-site-logo';
	}

	public function get_keywords() {
		return array( 'logo', 'brand', 'driveo' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Logo', 'driveo-child' ) ) );

		$this->add_control(
			'source',
			array(
				'label'       => __( 'Image', 'driveo-child' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'site',
				'options'     => array(
					'site'   => __( 'Site logo (Customize → Site Identity)', 'driveo-child' ),
					'custom' => __( 'Custom image for this placement', 'driveo-child' ),
				),
				'description' => __( 'Changing the site logo updates every placement that uses it.', 'driveo-child' ),
			)
		);

		$this->add_control(
			'image',
			array(
				'label'     => __( 'Custom image', 'driveo-child' ),
				'type'      => \Elementor\Controls_Manager::MEDIA,
				'condition' => array( 'source' => 'custom' ),
			)
		);

		$this->add_control(
			'decorative',
			array(
				'label'        => __( 'Decorative (brand name is shown next to it)', 'driveo-child' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => __( 'Off: the Media Library alt text is announced to screen readers.', 'driveo-child' ),
			)
		);

		$this->add_control(
			'link',
			array(
				'label'       => __( 'Link', 'driveo-child' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'placeholder' => '#top',
				'options'     => false,
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s  = $this->get_settings_for_display();
		$id = 'custom' === $s['source'] ? (int) ( $s['image']['id'] ?? 0 ) : (int) get_theme_mod( 'custom_logo' );

		if ( ! $id ) {
			$this->editor_hint( __( 'No logo yet: set one in Appearance → Customize → Site Identity, or choose a custom image.', 'driveo-child' ) );
			return;
		}

		echo driveo_render_logo( $id, 'yes' === $s['decorative'], (string) ( $s['link']['url'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper.
	}
}

/*
 * -------------------------------------------------------------------------
 * Driveo Hero Media
 * -------------------------------------------------------------------------
 */

class Driveo_Hero_Media_Widget extends Driveo_Widget_Base {
	public function get_name() {
		return 'driveo-hero-media';
	}

	public function get_title() {
		return __( 'Driveo Hero Media', 'driveo-child' );
	}

	public function get_icon() {
		return 'eicon-video-camera';
	}

	protected function register_controls() {
		$this->start_controls_section( 'media', array( 'label' => __( 'Background film', 'driveo-child' ) ) );

		$this->add_control(
			'video',
			array(
				'label'       => __( 'Video (desktop / tablet)', 'driveo-child' ),
				'type'        => \Elementor\Controls_Manager::MEDIA,
				'media_types' => array( 'video' ),
				'description' => __( 'MP4 (H.264). Plays once, muted; not loaded for visitors who prefer reduced motion.', 'driveo-child' ),
			)
		);

		$this->add_control(
			'video_mobile',
			array(
				'label'       => __( 'Video (phones, up to 767px)', 'driveo-child' ),
				'type'        => \Elementor\Controls_Manager::MEDIA,
				'media_types' => array( 'video' ),
				'description' => __( 'Optional lighter file. Falls back to the desktop video.', 'driveo-child' ),
			)
		);

		$this->add_control(
			'poster',
			array(
				'label'       => __( 'Poster image', 'driveo-child' ),
				'type'        => \Elementor\Controls_Manager::MEDIA,
				'description' => __( 'Shown before the film plays, for reduced motion, and without JavaScript. Used alone when no video is set.', 'driveo-child' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'labels', array( 'label' => __( 'Text', 'driveo-child' ) ) );

		$this->add_control(
			'rail',
			array(
				'label'       => __( 'Depth rail words (one per line)', 'driveo-child' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'description' => __( 'Decorative vertical words on large screens.', 'driveo-child' ),
			)
		);

		$this->add_control(
			'pause_label',
			array(
				'label' => __( 'Pause button label (screen readers)', 'driveo-child' ),
				'type'  => \Elementor\Controls_Manager::TEXT,
			)
		);

		$this->add_control(
			'play_label',
			array(
				'label' => __( 'Play button label (screen readers)', 'driveo-child' ),
				'type'  => \Elementor\Controls_Manager::TEXT,
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$poster_id = (int) ( $s['poster']['id'] ?? 0 );
		$poster    = $poster_id ? (string) wp_get_attachment_image_url( $poster_id, 'full' ) : (string) ( $s['poster']['url'] ?? '' );

		echo driveo_render_hero_media( // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper.
			array(
				'video'    => (string) ( $s['video']['url'] ?? '' ),
				'video_sm' => (string) ( $s['video_mobile']['url'] ?? '' ),
				'poster'   => $poster,
				'rail'     => preg_split( '/\R/', (string) $s['rail'] ),
				'pause'    => (string) $s['pause_label'],
				'play'     => (string) $s['play_label'],
			)
		);
	}
}

/*
 * -------------------------------------------------------------------------
 * Driveo Social Links
 * -------------------------------------------------------------------------
 */

class Driveo_Social_Links_Widget extends Driveo_Widget_Base {
	public function get_name() {
		return 'driveo-social-links';
	}

	public function get_title() {
		return __( 'Driveo Social Links', 'driveo-child' );
	}

	public function get_icon() {
		return 'eicon-social-icons';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Links', 'driveo-child' ) ) );

		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'network',
			array(
				'label'   => __( 'Icon', 'driveo-child' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'whatsapp',
				'options' => driveo_social_networks(),
			)
		);
		$repeater->add_control(
			'url',
			array(
				'label'       => __( 'URL', 'driveo-child' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'placeholder' => 'https://',
				'default'     => array( 'is_external' => 'on' ),
			)
		);
		$repeater->add_control(
			'label',
			array(
				'label'       => __( 'Accessible name', 'driveo-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'description' => __( 'Read by screen readers, e.g. "Instagram (opens in a new tab)".', 'driveo-child' ),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => __( 'Links', 'driveo-child' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label || network }}}',
			)
		);

		$this->add_control(
			'list_label',
			array(
				'label'       => __( 'List name (screen readers)', 'driveo-child' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'size',
			array(
				'label'   => __( 'Size', 'driveo-child' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'md',
				'options' => array(
					'md' => __( 'Standard', 'driveo-child' ),
					'lg' => __( 'Large (hero)', 'driveo-child' ),
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = array();
		foreach ( (array) $s['items'] as $item ) {
			$items[] = array(
				'network'  => (string) $item['network'],
				'url'      => (string) ( $item['url']['url'] ?? '' ),
				'external' => ! empty( $item['url']['is_external'] ),
				'label'    => (string) $item['label'],
			);
		}
		$html = driveo_render_social( $items, (string) $s['size'], (string) $s['list_label'] );
		if ( '' === $html ) {
			$this->editor_hint( __( 'Add at least one link with a URL.', 'driveo-child' ) );
			return;
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper.
	}
}
