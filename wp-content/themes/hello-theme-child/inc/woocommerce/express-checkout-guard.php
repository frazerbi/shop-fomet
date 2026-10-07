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
 * Stili in scss/woocommerce/checkout/_express-guard.scss.
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

/**
 * Sposta i bottoni wallet sotto i metodi di pagamento.
 *
 * Stripe li stampa su `woocommerce_checkout_before_customer_details` a
 * priorità 1, cioè prima che il widget Checkout di Elementor Pro (priorità 5)
 * apra le colonne: finiscono sopra tutto il checkout. Qui vanno su
 * `woocommerce_checkout_order_review` a 30, dopo #payment (stampato a 20):
 * dentro il pannello .e-checkout__order_review-2, sotto "Effettua ordine".
 *
 * Il punto è scelto perché resta *fuori* dai frammenti che WooCommerce
 * sostituisce a ogni `update_checkout` (.woocommerce-checkout-payment e
 * .woocommerce-checkout-review-order-table): dentro #payment il contenitore
 * verrebbe ridisegnato vuoto e i bottoni montati da Stripe sparirebbero.
 * Lo script di Stripe cerca il contenitore per id, ovunque sia in pagina.
 *
 * Stripe registra i suoi hook su `init` (priorità 11), quindi lo spostamento
 * va fatto dopo: `wp`. Se il plugin cambia hook o accessor, has_action()
 * fallisce e i bottoni restano semplicemente dove li mette Stripe.
 */
function child_move_express_checkout_buttons() {
	if ( ! class_exists( 'WC_Stripe' ) ) {
		return;
	}

	$ece = WC_Stripe::get_instance()->express_checkout_configuration ?? null;

	if ( ! $ece ) {
		return;
	}

	$callback = [ $ece, 'display_express_checkout_button_html' ];

	if ( false === has_action( 'woocommerce_checkout_before_customer_details', $callback ) ) {
		return;
	}

	remove_action( 'woocommerce_checkout_before_customer_details', $callback, 1 );
	add_action(
		'woocommerce_checkout_order_review',
		function () use ( $callback ) {
			child_print_express_checkout_separator_first( $callback );
		},
		30
	);
}
add_action( 'wp', 'child_move_express_checkout_buttons' );

/**
 * Stampa i bottoni wallet con il separatore "— Oppure —" *prima* invece che
 * dopo. Stripe stampa contenitore + separatore nello stesso metodo, pensato
 * per i bottoni in cima al form; spostati sotto "Effettua ordine", il
 * separatore va fra il bottone d'ordine e i wallet. Fatto lato server e non
 * nello script del guard, così non dipende dalla cache del JS. Se il markup
 * del separatore cambia e la regex non trova nulla, l'output resta com'è.
 *
 * @param callable $callback WC_Stripe_Express_Checkout_Element::display_express_checkout_button_html.
 */
function child_print_express_checkout_separator_first( $callback ) {
	ob_start();
	call_user_func( $callback );
	$html = ob_get_clean();

	$pattern = '#<p id="wc-stripe-express-checkout-button-separator".*?</p>#s';

	if ( preg_match( $pattern, $html, $match ) ) {
		$html = $match[0] . preg_replace( $pattern, '', $html, 1 );
	}

	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup di Stripe, già escapato dal plugin.
}
