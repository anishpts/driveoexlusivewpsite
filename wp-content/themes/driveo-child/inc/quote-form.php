<?php
/**
 * Quote request: Contact Form 7 integration.
 *
 * Contact Form 7 owns the form (fields, labels, messages, mail) and its submission
 * flow. The form is created once by `wp driveo install` from templates/cf7-quote-form.txt
 * and edited afterwards in WP Admin → Contact → Contact Forms. Its ID is stored in the
 * `driveo_quote_form_id` option; every hook below only acts on that form.
 *
 * Single source of truth for selectable values: the CF7 form. City, service, vehicle and
 * hours options exist only there, and CF7's own enum rule rejects anything else. This file
 * holds business rules that *refer* to option values (the "Hourly hire" service and the
 * per-vehicle limits); `driveo_quote_config_problems()` reports any rule that no longer
 * matches the form (shown to admins and by `wp driveo check`).
 *
 * What this file adds to CF7 (one submission flow, no parallel endpoint):
 *  - a [time] / [time*] form-tag (CF7 core has none) with client + server validation;
 *  - cross-field server-side rules CF7 cannot express on its own:
 *      Destination required unless "Hourly hire"; Hours required for "Hourly hire";
 *      passengers/luggage limited by vehicle (Business Van 7/8, others 4/3);
 *  - spam checks through CF7's `wpcf7_spam` filter: honeypot field + per-IP throttle;
 *  - a server-generated reference ([_dx_reference] mail-tag, returned to the page in
 *    CF7's REST response only after the mail was sent);
 *  - optional SMTP transport for wp_mail (used by CF7) from wp-config constants.
 *
 * Replaced by CF7 and removed (previous custom implementation): form markup shortcode,
 * REST endpoint, admin-post fallback, nonce, generic validation, wp_mail call and the
 * "Quote request" post type. CF7 itself does not store submissions; Flamingo would.
 *
 * @package driveo-child
 */

defined( 'ABSPATH' ) || exit;

const DRIVEO_HOURLY          = 'Hourly hire';
const DRIVEO_QUOTE_FORM_OPT  = 'driveo_quote_form_id';
const DRIVEO_QUOTE_HONEYPOT  = 'dx-website';

/**
 * The quote form object, or null when CF7 or the form is missing.
 */
function driveo_quote_form(): ?WPCF7_ContactForm {
	if ( ! function_exists( 'wpcf7_contact_form' ) ) {
		return null;
	}
	$form = wpcf7_contact_form( (int) get_option( DRIVEO_QUOTE_FORM_OPT ) );
	return $form ? $form : null;
}

/**
 * Selectable values of a field, read from the CF7 form itself (the single source of
 * truth). A `first_as_label` placeholder ("Select city") is not a value.
 *
 * @return string[]
 */
function driveo_quote_form_values( string $field ): array {
	$form = driveo_quote_form();
	if ( ! $form ) {
		return array();
	}
	$values = array();
	foreach ( $form->scan_form_tags( array( 'name' => $field ) ) as $tag ) {
		$tag_values = (array) $tag->values;
		if ( $tag->has_option( 'first_as_label' ) ) {
			array_shift( $tag_values );
		}
		$values = array_merge( $values, $tag_values );
	}
	return array_values( array_unique( array_map( 'strval', $values ) ) );
}

/**
 * Passenger and luggage limits per vehicle. Anything not listed uses "default" (the
 * stricter limits), so a renamed vehicle falls back safely rather than allowing more.
 * Keys must match vehicle option values in the CF7 form.
 */
function driveo_vehicle_limits(): array {
	return apply_filters(
		'driveo_vehicle_limits',
		array(
			'default'      => array(
				'passengers' => 4,
				'luggage'    => 3,
			),
			'Business Van' => array(
				'passengers' => 7,
				'luggage'    => 8,
			),
		)
	);
}

function driveo_limits_for( string $vehicle ): array {
	$limits = driveo_vehicle_limits();
	return $limits[ $vehicle ] ?? $limits['default'];
}

/**
 * Rules that refer to option values which the CF7 form no longer offers.
 *
 * @return string[] Human-readable problems; empty when everything matches.
 */
function driveo_quote_config_problems(): array {
	$form = driveo_quote_form();
	if ( ! $form ) {
		return array( 'The quote form (Contact Form 7) was not found. Run `wp driveo cf7`.' );
	}

	$problems = array();
	$services = driveo_quote_form_values( 'journey-type' );
	$vehicles = driveo_quote_form_values( 'vehicle' );

	if ( ! in_array( DRIVEO_HOURLY, $services, true ) ) {
		$problems[] = sprintf( 'The Service field has no "%s" option, so the hourly-hire rules (Hours instead of Destination) never apply.', DRIVEO_HOURLY );
	}
	if ( ! driveo_quote_form_values( 'hours' ) ) {
		$problems[] = 'The Hours field has no options.';
	}
	foreach ( array_keys( driveo_vehicle_limits() ) as $vehicle ) {
		if ( 'default' !== $vehicle && ! in_array( $vehicle, $vehicles, true ) ) {
			$problems[] = sprintf( 'A passenger/luggage limit is set for "%s", but the Vehicle field has no such option.', $vehicle );
		}
	}

	// Fleet "Quote this car" buttons on the front page must name real vehicle options.
	$front = (int) get_option( 'page_on_front' );
	if ( $front ) {
		preg_match_all( '/data-vehicle\|([^,"\\\\]+)/', (string) get_post_meta( $front, '_elementor_data', true ), $matches );
		foreach ( array_unique( $matches[1] ) as $vehicle ) {
			if ( ! in_array( $vehicle, $vehicles, true ) ) {
				$problems[] = sprintf( 'A "Quote this car" button selects "%s", but the Vehicle field has no such option.', $vehicle );
			}
		}
	}

	return $problems;
}

// Tell administrators when the form and the rules have drifted apart.
add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'toplevel_page_wpcf7' ), true ) ) {
			return;
		}
		$problems = driveo_quote_config_problems();
		if ( ! $problems ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Quote form check', 'driveo-child' ) . '</strong></p><ul style="list-style:disc;padding-left:1.5em">';
		foreach ( $problems as $problem ) {
			echo '<li>' . esc_html( $problem ) . '</li>';
		}
		echo '</ul></div>';
	}
);

/**
 * Is this CF7 form (object or ID) the quote form?
 */
function driveo_is_quote_form( $contact_form ): bool {
	$id = is_object( $contact_form ) ? (int) $contact_form->id() : (int) $contact_form;
	return $id > 0 && (int) get_option( DRIVEO_QUOTE_FORM_OPT ) === $id;
}

/**
 * The quote form of the submission being processed, or null.
 */
function driveo_quote_submission(): ?WPCF7_Submission {
	if ( ! class_exists( 'WPCF7_Submission' ) ) {
		return null;
	}
	$submission = WPCF7_Submission::get_instance();
	return ( $submission && driveo_is_quote_form( $submission->get_contact_form() ) ) ? $submission : null;
}

/**
 * A single posted value (select fields arrive as arrays).
 */
function driveo_posted( WPCF7_Submission $submission, string $name ): string {
	$value = $submission->get_posted_data( $name );
	if ( is_array( $value ) ) {
		$value = reset( $value );
	}
	return trim( (string) $value );
}

/*
 * -------------------------------------------------------------------------
 * Validation messages — editable in CF7 → Quote request → Messages
 * -------------------------------------------------------------------------
 */

/**
 * The theme's extra validation messages, registered with CF7 so they appear (and can be
 * edited) in the form's Messages tab. The defaults below only apply until the form is
 * saved with its own wording. {max} is replaced with the vehicle limit.
 */
add_filter(
	'wpcf7_messages',
	function ( $messages ) {
		return array_merge(
			$messages,
			array(
				'dx_destination_required' => array(
					'description' => __( 'Quote form: Destination missing (all services except Hourly hire)', 'driveo-child' ),
					'default'     => __( 'Please tell us where you are going.', 'driveo-child' ),
				),
				'dx_hours_required'       => array(
					'description' => __( 'Quote form: Hours missing for Hourly hire', 'driveo-child' ),
					'default'     => __( 'Please select the number of hours.', 'driveo-child' ),
				),
				'dx_invalid_time'         => array(
					'description' => __( 'Quote form: pickup time is not a valid time', 'driveo-child' ),
					'default'     => __( 'Please enter a valid time.', 'driveo-child' ),
				),
				'dx_passengers_limit'     => array(
					'description' => __( 'Quote form: too many passengers for the vehicle ({max} = limit)', 'driveo-child' ),
					'default'     => __( 'This vehicle seats 1 to {max} passengers.', 'driveo-child' ),
				),
				'dx_luggage_limit'        => array(
					'description' => __( 'Quote form: too much luggage for the vehicle ({max} = limit)', 'driveo-child' ),
					'default'     => __( 'This vehicle takes up to {max} bags.', 'driveo-child' ),
				),
			)
		);
	}
);

/**
 * A quote-form message as edited in CF7, falling back to its registered default.
 */
function driveo_quote_message( string $key, array $replace = array() ): string {
	$form    = driveo_quote_form();
	$message = $form ? (string) $form->message( $key ) : '';
	if ( '' === $message && function_exists( 'wpcf7_messages' ) ) {
		$all     = wpcf7_messages();
		$message = (string) ( $all[ $key ]['default'] ?? '' );
	}
	return strtr( $message, $replace );
}

/*
 * -------------------------------------------------------------------------
 * [time] / [time*] form-tag
 * -------------------------------------------------------------------------
 */

add_action(
	'wpcf7_init',
	function () {
		wpcf7_add_form_tag( array( 'time', 'time*' ), 'driveo_cf7_time_tag', array( 'name-attr' => true ) );
	}
);

function driveo_cf7_time_tag( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = wpcf7_get_validation_error( $tag->name );
	$class            = wpcf7_form_controls_class( $tag->type ) . ( $validation_error ? ' wpcf7-not-valid' : '' );

	$atts = array(
		'class'        => $tag->get_class_option( $class ),
		'id'           => $tag->get_id_option(),
		'tabindex'     => $tag->get_option( 'tabindex', 'signed_int', true ),
		'autocomplete' => $tag->get_autocomplete_option(),
		'type'         => 'time',
		'name'         => $tag->name,
		'value'        => wpcf7_get_hangover( $tag->name, (string) reset( $tag->values ) ),
		'aria-invalid' => $validation_error ? 'true' : 'false',
	);
	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}
	if ( $validation_error ) {
		$atts['aria-describedby'] = wpcf7_get_validation_error_reference( $tag->name );
	}

	return sprintf(
		'<span class="wpcf7-form-control-wrap" data-name="%1$s"><input %2$s />%3$s</span>',
		esc_attr( $tag->name ),
		wpcf7_format_atts( $atts ),
		$validation_error
	);
}

// Same rules on the client (CF7 schema) and the server.
add_action(
	'wpcf7_swv_create_schema',
	function ( $schema, $contact_form ) {
		foreach ( $contact_form->scan_form_tags( array( 'basetype' => array( 'time' ) ) ) as $tag ) {
			if ( $tag->is_required() ) {
				$schema->add_rule(
					wpcf7_swv_create_rule(
						'required',
						array(
							'field' => $tag->name,
							'error' => wpcf7_get_message( 'invalid_required' ),
						)
					)
				);
			}
			$schema->add_rule(
				wpcf7_swv_create_rule(
					'time',
					array(
						'field' => $tag->name,
						'error' => driveo_quote_message( 'dx_invalid_time' ),
					)
				)
			);
		}
	},
	10,
	2
);

/*
 * -------------------------------------------------------------------------
 * Cross-field validation (server side, authoritative)
 * -------------------------------------------------------------------------
 */

/**
 * Destination is required unless the journey type is "Hourly hire".
 */
add_filter(
	'wpcf7_validate_text',
	function ( $result, $tag ) {
		$submission = driveo_quote_submission();
		if ( ! $submission || 'destination' !== $tag->name ) {
			return $result;
		}
		$hourly = DRIVEO_HOURLY === driveo_posted( $submission, 'journey-type' );
		if ( ! $hourly && mb_strlen( driveo_posted( $submission, 'destination' ) ) < 2 ) {
			$result->invalidate( $tag, driveo_quote_message( 'dx_destination_required' ) );
		}
		return $result;
	},
	20,
	2
);

/**
 * Hours are required only for "Hourly hire". Which values are allowed (2–16 hours) comes
 * from the CF7 select itself: CF7's enum rule rejects anything that is not an option.
 */
add_filter(
	'wpcf7_validate_select',
	function ( $result, $tag ) {
		$submission = driveo_quote_submission();
		if ( ! $submission || 'hours' !== $tag->name ) {
			return $result;
		}
		if (
			DRIVEO_HOURLY === driveo_posted( $submission, 'journey-type' ) &&
			! in_array( driveo_posted( $submission, 'hours' ), driveo_quote_form_values( 'hours' ), true )
		) {
			$result->invalidate( $tag, driveo_quote_message( 'dx_hours_required' ) );
		}
		return $result;
	},
	20,
	2
);

/**
 * Passenger and luggage limits depend on the vehicle. The tags carry the absolute
 * ceiling (7 / 8); this narrows it for the chosen vehicle.
 */
add_filter(
	'wpcf7_validate_number*',
	function ( $result, $tag ) {
		$submission = driveo_quote_submission();
		if ( ! $submission || ! in_array( $tag->name, array( 'passengers', 'luggage' ), true ) ) {
			return $result;
		}
		$raw    = driveo_posted( $submission, $tag->name );
		$limits = driveo_limits_for( driveo_posted( $submission, 'vehicle' ) );
		$min    = 'passengers' === $tag->name ? 1 : 0;
		$max    = $limits[ $tag->name ];

		if ( ! preg_match( '/^\d+$/', $raw ) || (int) $raw < $min || (int) $raw > $max ) {
			$result->invalidate(
				$tag,
				driveo_quote_message( 'passengers' === $tag->name ? 'dx_passengers_limit' : 'dx_luggage_limit', array( '{max}' => (string) $max ) )
			);
		}
		return $result;
	},
	20,
	2
);

/*
 * -------------------------------------------------------------------------
 * Spam: honeypot + per-IP throttle, through CF7's own spam flow
 * -------------------------------------------------------------------------
 */

add_filter(
	'wpcf7_spam',
	function ( $spam, $submission ) {
		if ( $spam || ! driveo_is_quote_form( $submission->get_contact_form() ) ) {
			return $spam;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- CF7 has verified the request.
		if ( ! empty( $_POST[ DRIVEO_QUOTE_HONEYPOT ] ) ) {
			$submission->add_spam_log(
				array(
					'agent'  => 'driveo',
					'reason' => 'Honeypot field was filled in.',
				)
			);
			return true;
		}

		$key  = 'dx_quote_' . md5( $submission->get_meta( 'remote_ip' ) . wp_salt( 'nonce' ) );
		$hits = (int) get_transient( $key );
		if ( $hits >= 5 ) {
			$submission->add_spam_log(
				array(
					'agent'  => 'driveo',
					'reason' => 'More than 5 quote requests from this IP in 10 minutes.',
				)
			);
			return true;
		}
		set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );

		return false;
	},
	20,
	2
);

/*
 * -------------------------------------------------------------------------
 * Reference number
 * -------------------------------------------------------------------------
 */

/**
 * The reference of the submission being processed (generated once, server side).
 */
function driveo_quote_reference( bool $create = false ): string {
	static $reference = '';
	if ( '' === $reference && $create ) {
		$reference = 'DX-' . wp_date( 'ymd' ) . '-' . strtoupper( wp_generate_password( 4, false, false ) );
	}
	return $reference;
}

add_action(
	'wpcf7_before_send_mail',
	function ( $contact_form ) {
		if ( driveo_is_quote_form( $contact_form ) ) {
			driveo_quote_reference( true );
		}
	}
);

// [_dx_reference] mail-tag.
add_filter(
	'wpcf7_special_mail_tags',
	function ( $output, $name ) {
		return '_dx_reference' === $name ? driveo_quote_reference() : $output;
	},
	10,
	2
);

// Hand the reference to the page only when the mail really went out.
add_filter(
	'wpcf7_feedback_response',
	function ( $response, $result ) {
		if (
			isset( $response['status'], $response['contact_form_id'] ) &&
			'mail_sent' === $response['status'] &&
			driveo_is_quote_form( (int) $response['contact_form_id'] ) &&
			driveo_quote_reference()
		) {
			$response['dx_reference'] = driveo_quote_reference();
		}
		return $response;
	},
	10,
	2
);

/*
 * -------------------------------------------------------------------------
 * Markup: keep the template's own structure (no automatic <p>/<br>)
 * -------------------------------------------------------------------------
 */

add_filter(
	'wpcf7_autop_or_not',
	function ( $autop ) {
		$form = function_exists( 'wpcf7_get_current_contact_form' ) ? wpcf7_get_current_contact_form() : null;
		return ( $form && driveo_is_quote_form( $form ) ) ? false : $autop;
	}
);

/*
 * -------------------------------------------------------------------------
 * Optional SMTP transport for wp_mail (CF7 sends through wp_mail)
 * Define DRIVEO_SMTP_HOST, DRIVEO_SMTP_PORT, DRIVEO_SMTP_USER, DRIVEO_SMTP_PASS,
 * DRIVEO_SMTP_SECURE ('tls'|'ssl') and DRIVEO_SMTP_FROM in wp-config.php.
 * -------------------------------------------------------------------------
 */

add_action(
	'phpmailer_init',
	function ( $mailer ) {
		if ( ! defined( 'DRIVEO_SMTP_HOST' ) ) {
			return;
		}
		$mailer->isSMTP();
		$mailer->Host       = DRIVEO_SMTP_HOST;
		$mailer->Port       = defined( 'DRIVEO_SMTP_PORT' ) ? (int) DRIVEO_SMTP_PORT : 587;
		$mailer->SMTPSecure = defined( 'DRIVEO_SMTP_SECURE' ) ? DRIVEO_SMTP_SECURE : 'tls';
		if ( defined( 'DRIVEO_SMTP_USER' ) ) {
			$mailer->SMTPAuth = true;
			$mailer->Username = DRIVEO_SMTP_USER;
			$mailer->Password = defined( 'DRIVEO_SMTP_PASS' ) ? DRIVEO_SMTP_PASS : '';
		}
		if ( defined( 'DRIVEO_SMTP_FROM' ) ) {
			$mailer->setFrom( DRIVEO_SMTP_FROM, get_bloginfo( 'name' ) );
		}
	}
);
