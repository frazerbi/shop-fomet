<?php
/**
 * WooCommerce — Campi del checkout (fatturazione/spedizione)
 *
 * Riordina e accorpa i campi indirizzo per ridurre gli attriti in
 * compilazione. Ordine voluto:
 *
 *   nome | cognome
 *   email | telefono
 *   indirizzo (via e numero civico, full width)
 *   città | CAP
 *   provincia | paese
 *
 * Campo rimosso: la seconda riga dell'indirizzo (address_2, "Appartamento,
 * suite, unità"), accorpata in address_1 — sul vecchio store PrestaShop
 * restava sistematicamente vuota. (billing_company non è presente su questo
 * store, ma viene comunque rimosso per sicurezza se un plugin lo reintroduce.)
 * Qualunque altro campo, compresi quelli aggiunti da plugin, passa intatto.
 *
 * Due cose verificate sull'HTML reale del checkout (2026-09-21) che vincolano
 * l'implementazione:
 *
 * 1. Il gateway Stripe sposta billing_email in cima con `priority => 1` e gli
 *    aggiunge la classe `stripe-gateway-checkout-email-field`, su cui il suo JS
 *    fa affidamento. Quindi: filtro agganciato tardissimo (9999) per vincere
 *    sulla sua priorità, e le classi vengono *fuse*, mai sostituite — si
 *    rimuovono solo le `form-row-*` esistenti e si aggiunge quella voluta.
 *
 * 2. La locale italiana rende tutti i campi indirizzo `form-row-wide`. Le
 *    classi di colonna vanno quindi riscritte sia su `woocommerce_checkout_fields`
 *    (checkout) sia su `woocommerce_default_address_fields` (pagina "Indirizzi"
 *    del Mio Account), altrimenti restano a tutta larghezza.
 *
 * L'ordine è espresso via `priority`: WooCommerce ordina i campi con un uasort
 * sulle priorità, non sull'ordine dell'array. Il layout a due colonne vero e
 * proprio è CSS — vedi la griglia su .woocommerce-billing-fields__field-wrapper
 * in scss/woocommerce/checkout/_fields.scss (le classi native sono float al
 * 48%, lì resettate).
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mappa campo => [ priorità, classe di colonna ] per la fatturazione.
 *
 * @return array<string, array{int, string}>
 */
function child_checkout_billing_layout() {
	return [
		'billing_first_name' => [ 10, 'form-row-first' ],
		'billing_last_name'  => [ 20, 'form-row-last' ],
		'billing_email'      => [ 30, 'form-row-first' ],
		'billing_phone'      => [ 40, 'form-row-last' ],
		'billing_address_1'  => [ 50, 'form-row-wide' ],
		'billing_city'       => [ 60, 'form-row-first' ],
		'billing_postcode'   => [ 70, 'form-row-last' ],
		'billing_state'      => [ 80, 'form-row-first' ],
		'billing_country'    => [ 90, 'form-row-last' ],
	];
}

/**
 * Stessa sequenza per la spedizione ("Spedire ad un indirizzo diverso?").
 * Non esiste una email di spedizione, quindi il telefono — che in
 * fatturazione le sta accanto — resta da solo nella colonna di sinistra:
 * così la griglia delle due sezioni resta visivamente allineata.
 *
 * @return array<string, array{int, string}>
 */
function child_checkout_shipping_layout() {
	return [
		'shipping_first_name' => [ 10, 'form-row-first' ],
		'shipping_last_name'  => [ 20, 'form-row-last' ],
		'shipping_phone'      => [ 40, 'form-row-first' ],
		'shipping_address_1'  => [ 50, 'form-row-wide' ],
		'shipping_city'       => [ 60, 'form-row-first' ],
		'shipping_postcode'   => [ 70, 'form-row-last' ],
		'shipping_state'      => [ 80, 'form-row-first' ],
		'shipping_country'    => [ 90, 'form-row-last' ],
	];
}

/**
 * Sostituisce la sola classe di colonna, preservando tutte le altre
 * (validazione, address-field, classi di plugin come quella di Stripe).
 *
 * @param mixed  $classes Classi correnti del campo (array o stringa).
 * @param string $column  Classe di colonna da applicare.
 * @return array
 */
function child_set_field_column_class( $classes, $column ) {
	if ( is_string( $classes ) ) {
		$classes = preg_split( '/\s+/', trim( $classes ), -1, PREG_SPLIT_NO_EMPTY );
	}

	$classes = is_array( $classes ) ? $classes : [];
	$classes = array_filter(
		$classes,
		function ( $class ) {
			return ! in_array( $class, [ 'form-row-first', 'form-row-last', 'form-row-wide' ], true );
		}
	);

	$classes[] = $column;

	return array_values( array_unique( $classes ) );
}

/**
 * Campi nativi tolti dal checkout: seconda riga indirizzo e azienda.
 *
 * Lista esplicita, non "tutto ciò che non è nella mappa": i campi aggiunti da
 * altri plugin (es. codice fiscale, P.IVA, SDI per la fatturazione
 * elettronica) devono restare, con la loro priorità e le loro classi — la
 * griglia di scss/woocommerce/checkout/_fields.scss li mette in coda.
 *
 * @return string[]
 */
function child_checkout_removed_fields() {
	return [
		'billing_company',
		'billing_address_2',
		'shipping_company',
		'shipping_address_2',
	];
}

/**
 * Applica una mappa di layout a un gruppo di campi. I campi non previsti
 * dalla mappa restano intatti; vengono eliminati solo quelli di
 * child_checkout_removed_fields().
 *
 * @param array $group  Gruppo di campi (billing/shipping).
 * @param array $layout Mappa chiave => [ priorità, classe ].
 * @return array
 */
function child_apply_checkout_layout( $group, $layout ) {
	foreach ( $group as $key => $field ) {
		if ( in_array( $key, child_checkout_removed_fields(), true ) ) {
			unset( $group[ $key ] );
			continue;
		}

		if ( ! isset( $layout[ $key ] ) ) {
			continue;
		}

		list( $priority, $column ) = $layout[ $key ];

		$group[ $key ]['priority'] = $priority;
		$group[ $key ]['class']    = child_set_field_column_class(
			isset( $field['class'] ) ? $field['class'] : [],
			$column
		);
	}

	return $group;
}

/**
 * Riordina e accorpa i campi del checkout.
 *
 * @param array $fields Campi del checkout.
 * @return array
 */
function child_checkout_fields( $fields ) {
	if ( isset( $fields['billing'] ) ) {
		$fields['billing'] = child_apply_checkout_layout( $fields['billing'], child_checkout_billing_layout() );
	}

	if ( isset( $fields['shipping'] ) ) {
		$fields['shipping'] = child_apply_checkout_layout( $fields['shipping'], child_checkout_shipping_layout() );
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'child_checkout_fields', 9999 );

/**
 * Stesse priorità e stesse colonne sul set di campi indirizzo condiviso
 * (checkout, ma anche "Indirizzi" nel Mio Account), dove address_2 viene
 * rimosso del tutto.
 *
 * @param array $fields Campi indirizzo di default.
 * @return array
 */
function child_default_address_fields( $fields ) {
	$layout = [
		'address_1' => [ 50, 'form-row-wide' ],
		'city'      => [ 60, 'form-row-first' ],
		'postcode'  => [ 70, 'form-row-last' ],
		'state'     => [ 80, 'form-row-first' ],
		'country'   => [ 90, 'form-row-last' ],
	];

	foreach ( $layout as $key => $config ) {
		if ( ! isset( $fields[ $key ] ) ) {
			continue;
		}

		list( $priority, $column ) = $config;

		$fields[ $key ]['priority'] = $priority;
		$fields[ $key ]['class']    = child_set_field_column_class(
			isset( $fields[ $key ]['class'] ) ? $fields[ $key ]['class'] : [],
			$column
		);
	}

	unset( $fields['address_2'], $fields['company'] );

	return $fields;
}
add_filter( 'woocommerce_default_address_fields', 'child_default_address_fields', 9999 );

/**
 * Rete di sicurezza sulla classe di colonna, applicata al momento del render
 * del singolo campo.
 *
 * `woocommerce_checkout_fields` viene filtrato una volta sola, in anticipo:
 * qualunque plugin agganciato più tardi del nostro 9999 (Stripe riscrive
 * billing_email, e la locale rimette form-row-wide sui campi indirizzo) può
 * ancora rimandare un campo a tutta larghezza. `woocommerce_form_field_args`
 * invece gira su ogni campo all'atto di stamparlo, quindi è l'ultima parola
 * sulle classi.
 *
 * Non tocca la `priority` (già risolta a monte, nell'ordinamento dei campi):
 * serve solo a garantire le larghezze.
 *
 * @param array  $args Argomenti del campo.
 * @param string $key  Chiave del campo (es. billing_email).
 * @return array
 */
function child_checkout_field_args( $args, $key ) {
	$layout = child_checkout_billing_layout() + child_checkout_shipping_layout();

	if ( ! isset( $layout[ $key ] ) ) {
		return $args;
	}

	$args['class'] = child_set_field_column_class(
		isset( $args['class'] ) ? $args['class'] : [],
		$layout[ $key ][1]
	);

	return $args;
}
add_filter( 'woocommerce_form_field_args', 'child_checkout_field_args', 9999, 2 );
