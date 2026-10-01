/**
 * Driveo one-page interactions. Vanilla JS, no dependencies.
 *
 * Modules: motion preferences · hero parallax · fleet tilt · anchors & quote hand-off ·
 * mobile menu · hero video · quote form · install prompt.
 */
( function () {
	'use strict';

	const root = document.documentElement;
	root.classList.add( 'dx-js' );

	const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	const finePointer = window.matchMedia( '(hover: hover) and (pointer: fine)' );
	const desktop = window.matchMedia( '(min-width: 1024px)' );

	const motionAllowed = () => ! reducedMotion.matches;
	const pointerEffects = () => motionAllowed() && finePointer.matches;

	/* ------------------------------------------------------------------
	 * Pointer tracking → --mx / --my in the range -1…1, one write per frame.
	 * ------------------------------------------------------------------ */
	function trackPointer( el ) {
		let frame = 0;
		let x = 0;
		let y = 0;

		const write = () => {
			frame = 0;
			el.style.setProperty( '--mx', x.toFixed( 3 ) );
			el.style.setProperty( '--my', y.toFixed( 3 ) );
		};

		el.addEventListener( 'pointermove', ( event ) => {
			if ( event.pointerType !== 'mouse' || ! pointerEffects() ) {
				return;
			}
			const rect = el.getBoundingClientRect();
			x = ( ( event.clientX - rect.left ) / rect.width - 0.5 ) * 2;
			y = ( ( event.clientY - rect.top ) / rect.height - 0.5 ) * 2;
			if ( ! frame ) {
				frame = requestAnimationFrame( write );
			}
		} );

		el.addEventListener( 'pointerleave', ( event ) => {
			if ( event.pointerType !== 'mouse' ) {
				return;
			}
			x = 0;
			y = 0;
			if ( ! frame ) {
				frame = requestAnimationFrame( write );
			}
		} );
	}

	document.querySelectorAll( '.dx-hero, .dx-fleet-card' ).forEach( trackPointer );

	/* ------------------------------------------------------------------
	 * Selected vehicle, shared between the fleet buttons and the quote form.
	 * ------------------------------------------------------------------ */
	const VEHICLE_KEY = 'dx-vehicle';

	function rememberVehicle( value ) {
		root.dataset.dxVehicle = value;
		try {
			sessionStorage.setItem( VEHICLE_KEY, value );
		} catch ( error ) {
			// Storage can be unavailable (private mode); the dataset copy still works.
		}
		document.dispatchEvent( new CustomEvent( 'driveo:vehicle', { detail: { vehicle: value } } ) );
	}

	function recalledVehicle() {
		try {
			return root.dataset.dxVehicle || sessionStorage.getItem( VEHICLE_KEY ) || '';
		} catch ( error ) {
			return root.dataset.dxVehicle || '';
		}
	}

	/* ------------------------------------------------------------------
	 * In-page anchors: smooth scroll (unless reduced motion) and move focus
	 * to the target so keyboard and screen-reader users land there too.
	 * ------------------------------------------------------------------ */
	function goTo( target, { focus = target, updateHash = true } = {} ) {
		target.scrollIntoView( { behavior: motionAllowed() ? 'smooth' : 'auto', block: 'start' } );
		if ( updateHash && target.id ) {
			history.pushState( null, '', '#' + target.id );
		}
		if ( focus ) {
			if ( ! focus.matches( 'a[href], button, input, select, textarea, [tabindex]' ) ) {
				focus.setAttribute( 'tabindex', '-1' );
			}
			focus.focus( { preventScroll: true } );
		}
	}

	document.addEventListener( 'click', ( event ) => {
		const link = event.target.closest( 'a[href*="#"]' );
		if ( ! link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey ) {
			return;
		}
		const url = new URL( link.href, window.location.href );
		if ( url.pathname !== window.location.pathname || url.origin !== window.location.origin || url.hash.length < 2 ) {
			return;
		}
		const target = document.getElementById( decodeURIComponent( url.hash.slice( 1 ) ) );
		if ( ! target ) {
			return;
		}
		event.preventDefault();
		closeMenu();

		// "Quote this car": remember the vehicle (the form may load later), preselect it
		// if the form is on the page, then land on its field.
		const vehicle = link.dataset.vehicle;
		if ( vehicle ) {
			rememberVehicle( vehicle );
		}
		const select = vehicle && document.getElementById( 'dx-vehicle' );
		if ( select ) {
			select.value = vehicle;
			select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			goTo( target, { focus: select } );
			return;
		}
		goTo( target );
	} );

	/* ------------------------------------------------------------------
	 * Mobile menu (disclosure pattern)
	 * ------------------------------------------------------------------ */
	const nav = document.querySelector( '.dx-nav' );
	const toggle = nav && nav.querySelector( '.dx-menu-toggle' );

	function setMenu( open ) {
		if ( ! nav ) {
			return;
		}
		nav.classList.toggle( 'is-open', open );
		toggle.setAttribute( 'aria-expanded', String( open ) );
	}

	function closeMenu() {
		if ( nav && nav.classList.contains( 'is-open' ) ) {
			setMenu( false );
		}
	}

	if ( toggle ) {
		toggle.addEventListener( 'click', () => setMenu( ! nav.classList.contains( 'is-open' ) ) );

		document.addEventListener( 'keydown', ( event ) => {
			if ( event.key === 'Escape' && nav.classList.contains( 'is-open' ) ) {
				setMenu( false );
				toggle.focus();
			}
		} );

		document.addEventListener( 'click', ( event ) => {
			if ( ! nav.contains( event.target ) ) {
				closeMenu();
			}
		} );

		// Close when focus leaves the menu (e.g. Tab past the last link).
		nav.addEventListener( 'focusout', ( event ) => {
			if ( event.relatedTarget && ! nav.contains( event.relatedTarget ) ) {
				closeMenu();
			}
		} );

		desktop.addEventListener( 'change', closeMenu );
	}

	/* ------------------------------------------------------------------
	 * Hero video: attached only when motion is allowed; always pausable.
	 * ------------------------------------------------------------------ */
	const video = document.querySelector( 'video.dx-hero-video[data-src]' );
	const videoToggle = document.querySelector( '.dx-video-toggle' );

	function setVideoState( playing ) {
		if ( ! videoToggle ) {
			return;
		}
		videoToggle.dataset.state = playing ? 'playing' : 'paused';
		videoToggle.setAttribute( 'aria-label', playing ? videoToggle.dataset.labelPause : videoToggle.dataset.labelPlay );
	}

	function playVideo() {
		if ( ! video.getAttribute( 'src' ) ) {
			// Phones get the lighter rendition.
			const small = window.matchMedia( '(max-width: 767px)' ).matches && video.dataset.srcSm;
			video.src = small || video.dataset.src;
		}
		if ( video.ended ) {
			video.currentTime = 0;
		}
		const attempt = video.play();
		if ( attempt ) {
			attempt.catch( () => setVideoState( false ) );
		}
	}

	if ( video ) {
		video.addEventListener( 'play', () => setVideoState( true ) );
		video.addEventListener( 'pause', () => setVideoState( false ) );
		video.addEventListener( 'ended', () => setVideoState( false ) );

		if ( videoToggle ) {
			videoToggle.hidden = false;
			setVideoState( false );
			videoToggle.addEventListener( 'click', () => ( video.paused ? playVideo() : video.pause() ) );
		}

		if ( motionAllowed() ) {
			playVideo();
		}

		reducedMotion.addEventListener( 'change', () => {
			if ( reducedMotion.matches ) {
				video.pause();
			}
		} );
	}

	/* ------------------------------------------------------------------
	 * Quote form (Contact Form 7)
	 *
	 * CF7 owns the fields, validation, submission and messages. This module only
	 * adds behaviour around CF7's markup: hourly-hire field switching, vehicle-based
	 * passenger/luggage steppers, the busy state and the confirmation panel.
	 * Every visible text comes from the CF7 form template, not from this file.
	 * ------------------------------------------------------------------ */
	const quoteForm = document.querySelector( 'form.wpcf7-form.dx-quote-form' );
	if ( quoteForm ) {
		initQuote( quoteForm );
	}

	function initQuote( form ) {
		const config = window.driveoQuote || {};
		// Rules come only from PHP (inc/quote-form.php); no duplicate values here.
		const limits = config.limits || {};
		const hourlyValue = config.hourly || null;
		const formId = Number( ( form.querySelector( 'input[name="_wpcf7"]' ) || {} ).value );

		const field = ( name ) => form.querySelector( '[name="' + name + '"]' );
		const service = field( 'journey-type' );
		const vehicle = field( 'vehicle' );
		const destination = field( 'destination' );
		const hours = field( 'hours' );
		const pointOnly = form.querySelector( '[data-when="point"]' );
		const hourlyOnly = form.querySelector( '[data-when="hourly"]' );
		const fields = form.querySelector( '.dx-quote-fields' );
		const success = form.querySelector( '.dx-quote-success' );
		const submit = form.querySelector( '.dx-quote-submit' );
		const busyLabel = form.querySelector( '.dx-busy-label' );
		const idleText = submit ? submit.value : '';
		let busyTimer = 0;

		if ( ! service || ! vehicle || ! destination || ! hours ) {
			return; // The CF7 template was changed; leave the form as plain CF7.
		}

		/* Hints: link helper text to its field. CF7 rewrites aria-describedby while it
		   shows or clears an error, so this runs again after validation. */
		function addDescribedBy( el, id ) {
			const ids = ( el.getAttribute( 'aria-describedby' ) || '' ).split( /\s+/ ).filter( Boolean );
			if ( ! ids.includes( id ) ) {
				ids.push( id );
				el.setAttribute( 'aria-describedby', ids.join( ' ' ) );
			}
		}

		function linkHints() {
			form.querySelectorAll( '.dx-stepper input' ).forEach( ( input ) => {
				if ( document.getElementById( input.id + '-hint' ) ) {
					addDescribedBy( input, input.id + '-hint' );
				}
			} );
			if ( document.getElementById( 'dx-hours-note' ) ) {
				addDescribedBy( hours, 'dx-hours-note' );
			}
		}

		/* Hourly hire swaps Destination for Hours. The inactive field is disabled, so it
		   is neither validated in the browser nor submitted; the server applies the
		   same rule (inc/quote-form.php). */
		function syncService() {
			const hourly = service.value === hourlyValue;
			pointOnly.hidden = hourly;
			hourlyOnly.hidden = ! hourly;
			destination.disabled = hourly;
			destination.setAttribute( 'aria-required', String( ! hourly ) );
			hours.disabled = ! hourly;
			hours.setAttribute( 'aria-required', String( hourly ) );
		}

		/* Steppers around CF7's number inputs. */
		function syncStepper( input ) {
			const min = Number( input.min );
			const max = Number( input.max );
			let value = Math.round( Number( input.value ) );
			if ( input.value === '' || Number.isNaN( value ) ) {
				value = min;
			}
			input.value = String( Math.min( max, Math.max( min, value ) ) );
			const control = input.closest( '.dx-stepper' );
			control.querySelector( '[data-step="-1"]' ).disabled = Number( input.value ) <= min;
			control.querySelector( '[data-step="1"]' ).disabled = Number( input.value ) >= max;
		}

		function syncVehicle() {
			const limit = limits[ vehicle.value ] || limits.default;
			if ( ! limit ) {
				return; // no rules configured: keep the CF7 field's own min/max
			}
			[ 'passengers', 'luggage' ].forEach( ( name ) => {
				const input = field( name );
				const max = String( limit[ name ] );
				if ( input.max !== max ) {
					input.max = max;
					const shown = form.querySelector( '#' + input.id + '-hint [data-limit]' );
					if ( shown ) {
						shown.textContent = max;
					}
				}
				syncStepper( input );
			} );
		}

		form.addEventListener( 'click', ( event ) => {
			const button = event.target.closest( '.dx-stepper__btn' );
			if ( button ) {
				const input = document.getElementById( button.getAttribute( 'aria-controls' ) );
				input.value = String( Number( input.value ) + Number( button.dataset.step ) );
				syncStepper( input );
				input.dispatchEvent( new Event( 'change', { bubbles: true } ) ); // let CF7 re-validate
				if ( button.disabled ) {
					input.focus(); // keep focus somewhere useful at the limit
				}
				return;
			}
			if ( event.target.closest( '.dx-quote-again' ) ) {
				showForm();
			}
		} );

		form.addEventListener( 'change', ( event ) => {
			const target = event.target;
			if ( target === service ) {
				syncService();
			} else if ( target === vehicle ) {
				syncVehicle();
			} else if ( target.closest( '.dx-stepper' ) && target.tagName === 'INPUT' ) {
				syncStepper( target );
			}
			window.setTimeout( linkHints, 0 );
		} );

		/* Busy state while CF7 sends. CF7 fires wpcf7submit when any response arrives. */
		function setBusy( busy ) {
			window.clearTimeout( busyTimer );
			form.setAttribute( 'aria-busy', String( busy ) );
			if ( submit ) {
				submit.disabled = busy;
				submit.value = busy && busyLabel ? busyLabel.textContent : idleText;
			}
			if ( busy ) {
				busyTimer = window.setTimeout( () => setBusy( false ), 30000 ); // never stay stuck
			}
		}

		form.addEventListener( 'submit', () => {
			if ( form.getAttribute( 'aria-busy' ) !== 'true' ) {
				window.setTimeout( () => setBusy( true ), 0 ); // after CF7 has read the form
			}
		} );

		/* Confirmation panel */
		function showSuccess( reference ) {
			const line = success.querySelector( '.dx-quote-ref-line' );
			const ref = success.querySelector( '.dx-quote-ref' );
			if ( line && ref ) {
				ref.textContent = reference || '';
				line.hidden = ! reference; // only a reference the server issued
			}
			fields.hidden = true;
			success.hidden = false;
			success.focus();
		}

		function showForm() {
			success.hidden = true;
			fields.hidden = false;
			syncService();
			syncVehicle();
			field( 'client-name' ).focus();
		}

		function focusMessage() {
			const output = form.parentElement.querySelector( '.wpcf7-response-output' ) || form.querySelector( '.wpcf7-response-output' );
			if ( output ) {
				output.setAttribute( 'tabindex', '-1' );
				output.focus();
			}
		}

		/* CF7 DOM events (they bubble to document; match this form by its ID). */
		const mine = ( event ) => event.detail && Number( event.detail.contactFormId ) === formId;

		document.addEventListener( 'wpcf7submit', ( event ) => {
			if ( mine( event ) ) {
				setBusy( false );
				window.setTimeout( linkHints, 0 );
			}
		} );

		document.addEventListener( 'wpcf7invalid', ( event ) => {
			if ( ! mine( event ) ) {
				return;
			}
			window.setTimeout( () => {
				const invalid = form.querySelector( '[aria-invalid="true"]:not([disabled])' );
				( invalid || form ).focus();
			}, 0 );
		} );

		[ 'wpcf7spam', 'wpcf7mailfailed' ].forEach( ( type ) => {
			document.addEventListener( type, ( event ) => {
				if ( mine( event ) ) {
					window.setTimeout( focusMessage, 0 );
				}
			} );
		} );

		document.addEventListener( 'wpcf7mailsent', ( event ) => {
			if ( mine( event ) ) {
				const response = event.detail.apiResponse || {};
				showSuccess( response.dx_reference );
			}
		} );

		// CF7 clears the form after a successful send; restore the derived state.
		document.addEventListener( 'wpcf7reset', ( event ) => {
			if ( mine( event ) ) {
				syncService();
				syncVehicle();
			}
		} );

		// A vehicle chosen earlier from the fleet cards.
		const remembered = recalledVehicle();
		if ( remembered && [ ...vehicle.options ].some( ( option ) => option.value === remembered ) ) {
			vehicle.value = remembered;
		}

		linkHints();
		syncService();
		syncVehicle();
	}

	/* ------------------------------------------------------------------
	 * Install prompt placeholder (appears only if the site becomes a PWA).
	 * ------------------------------------------------------------------ */
	const install = document.querySelector( '.dx-install' );
	if ( install ) {
		let deferred = null;
		window.addEventListener( 'beforeinstallprompt', ( event ) => {
			event.preventDefault();
			deferred = event;
			install.hidden = false;
		} );
		install.addEventListener( 'click', async () => {
			if ( ! deferred ) {
				return;
			}
			deferred.prompt();
			await deferred.userChoice;
			deferred = null;
			install.hidden = true;
		} );
	}
} )();
