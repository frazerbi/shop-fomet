<?php
/**
 * WooCommerce — Blocco dei wallet digitali sul checkout
 *
 * I bottoni Apple Pay / Google Pay / Link del gateway Stripe ("Express
 * Checkout Element", contenitore #wc-stripe-express-checkout-element) aprono
 * il foglio di pagamento del wallet senza passare dal form: l'utente può
 * pagare lasciando vuoti i campi di fatturazione. Qui i bottoni restano
 * bloccati finché tutti i campi obbligatori visibili del form non sono
 * compilati (e l'email non è valida).
 *
 * Perché lato client e con un overlay: i bottoni vivono dentro un <iframe>
 * di Stripe, e il loro handler di click (build/express-checkout.js) non
 * espone nessun filtro per annullarlo — verificato su Stripe 10.8/11.0.
 * L'unico modo di impedirne l'apertura è non far arrivare il click
 * all'iframe: `inert` sul contenitore (blocca mouse *e* tastiera, anche il
 * Tab dentro l'iframe) più un bottone trasparente sovrapposto che, al click,
 * evidenzia i campi mancanti con la validazione nativa di WooCommerce.
 *
 * Nessun controllo lato server, deliberatamente: nel flusso express Stripe
 * crea l'ordine con nome/email/indirizzo restituiti dal wallet, non con
 * quelli digitati nel form, quindi una validazione PHP sui campi del form
 * non avrebbe nulla di significativo da controllare.
 *
 * Stili in scss/_checkout.scss (sezione "Wallet digitali").
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Carica lo script solo sul checkout vero e proprio (non su "ordine
 * ricevuto" né su "paga ordine", dove il form di fatturazione non c'è).
 */
function child_enqueue_express_checkout_guard() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}

	if ( is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'order-pay' ) ) {
		return;
	}

	$path = '/assets/js/express-checkout-guard.js';

	wp_enqueue_script(
		'child-express-checkout-guard',
		get_stylesheet_directory_uri() . $path,
		[ 'jquery' ],
		filemtime( get_stylesheet_directory() . $path ),
		true
	);

	wp_localize_script(
		'child-express-checkout-guard',
		'childExpressGuard',
		[
			'notice' => __( 'Compila i campi obbligatori del modulo per pagare con Apple Pay, Google Pay o Link.', 'hello-elementor-child' ),
			'label'  => __( 'Compila i campi obbligatori per sbloccare il pagamento rapido', 'hello-elementor-child' ),
		]
	);
}
add_action( 'wp_enqueue_scripts', 'child_enqueue_express_checkout_guard', 20 );
