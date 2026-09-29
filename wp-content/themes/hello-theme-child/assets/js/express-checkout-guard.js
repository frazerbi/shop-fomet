/**
 * Blocca i wallet digitali (Stripe Express Checkout) finché i campi
 * obbligatori del checkout non sono compilati.
 * Contesto e motivazioni in woocommerce-express-checkout-guard.php.
 */
( function ( $ ) {
	'use strict';

	var CONTAINER = '#wc-stripe-express-checkout-element';
	var EMAIL_RE  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	var i18n      = window.childExpressGuard || {};

	/**
	 * Un campo obbligatorio conta solo se la sua riga è visibile: così
	 * restano fuori i campi di spedizione quando "Spedire a un indirizzo
	 * diverso?" è deselezionato e la provincia per i paesi che non ne hanno.
	 * Si guarda la riga e non l'input perché le select Select2 (paese,
	 * provincia) sono nascoste anche quando il campo è in pagina.
	 */
	function missingRows( $form ) {
		return $form.find( '.form-row.validate-required' ).filter( function () {
			var $row = $( this );

			if ( ! $row.is( ':visible' ) ) {
				return false;
			}

			var $input = $row.find( 'input, select, textarea' ).not( '[type="hidden"]' ).first();

			if ( ! $input.length ) {
				return false;
			}

			if ( $input.is( ':checkbox' ) ) {
				return ! $input.is( ':checked' );
			}

			var value = $.trim( $input.val() || '' );

			if ( ! value ) {
				return true;
			}

			return $row.hasClass( 'validate-email' ) && ! EMAIL_RE.test( value );
		} );
	}

	function init() {
		var $form      = $( 'form.checkout' );
		var $container = $( CONTAINER );

		if ( ! $form.length || ! $container.length ) {
			return;
		}

		// Doppio wrapper attorno al contenitore di Stripe: overlay e avviso
		// stanno *fuori* dal contenitore, perché Stripe ne gestisce il
		// contenuto (vi monta i bottoni) e la visibilità. L'overlay sta nel
		// wrapper interno, che contiene solo i bottoni, così ne copre
		// esattamente l'area senza coprire l'avviso.
		$container.wrap( '<div class="child-express-guard"><div class="child-express-guard__buttons"></div></div>' );

		var $buttons = $container.parent();
		var $wrap    = $buttons.parent();
		var $overlay = $( '<button type="button" class="child-express-guard__overlay"></button>' )
			.attr( 'aria-label', i18n.label || '' );
		var $notice  = $( '<p class="child-express-guard__notice" role="status"></p>' )
			.text( i18n.notice || '' );

		$wrap.prepend( $notice );
		$buttons.append( $overlay );

		function update() {
			var locked = missingRows( $form ).length > 0;

			// I bottoni compaiono solo se il browser ha un wallet disponibile:
			// finché Stripe tiene il contenitore nascosto, niente avviso.
			$wrap.toggleClass( 'is-active', $container.is( ':visible' ) );
			$wrap.toggleClass( 'is-locked', locked );
			$container.prop( 'inert', locked );
		}

		// Click sull'overlay: stessa validazione che WooCommerce fa all'invio
		// del form (evento `validate` gestito da checkout.js, aggiunge
		// .woocommerce-invalid alle righe), poi porta l'utente al primo campo
		// mancante.
		$overlay.on( 'click', function () {
			var $missing = missingRows( $form );

			$missing.find( 'input, select, textarea' ).not( '[type="hidden"]' ).trigger( 'validate' );

			var $first = $missing.first();

			if ( ! $first.length ) {
				update();
				return;
			}

			$( 'html, body' ).animate( { scrollTop: $first.offset().top - 120 }, 300 );
			$first.find( 'input, select, textarea' ).not( '[type="hidden"]' ).first().trigger( 'focus' );
		} );

		// `input` copre la digitazione, `change` Select2 / checkbox / autofill;
		// `updated_checkout` e `country_to_state_changed` i campi che
		// WooCommerce ridisegna al cambio di paese.
		$form.on( 'input change', 'input, select, textarea', update );
		$( document.body ).on( 'updated_checkout country_to_state_changed', update );

		// Stripe rende visibile il contenitore in modo asincrono, quando il
		// wallet risulta disponibile: si osserva il suo attributo style.
		if ( window.MutationObserver ) {
			new MutationObserver( update ).observe( $container[ 0 ], {
				attributes: true,
				attributeFilter: [ 'style' ],
			} );
		}

		update();
	}

	$( init );
} )( jQuery );
