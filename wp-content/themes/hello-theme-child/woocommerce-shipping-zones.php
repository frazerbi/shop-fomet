<?php
/**
 * Provisioning delle Shipping Zone WooCommerce.
 *
 * Crea le due zone del vecchio shop PrestaShop e ci aggancia i metodi:
 * il metodo a peso definito in woocommerce-shipping.php + il Ritiro in sede
 * (Local pickup nativo, 0 €).
 *
 * Non gira mai da solo: va lanciato a mano, una volta.
 *   WP-CLI:    wp eval 'child_sync_shipping_zones();'
 *   Admin URL: aggiungere ?child_sync_shipping=1 a un URL di wp-admin
 *
 * È idempotente: le zone già esistenti (match sul nome) vengono saltate, non
 * duplicate né sovrascritte. Per rigenerarne una, cancellarla prima da
 * WooCommerce → Impostazioni → Spedizione.
 *
 * @package HelloElementorChild
 */

defined( 'ABSPATH' ) || exit;

// ─── Definizione delle zone ───────────────────────────────────────────────────
// L'ordine dell'array è l'ordine delle zone: WooCommerce applica al cliente la
// PRIMA zona che fa match, quindi "Isole e Calabria" deve precedere "Italia",
// altrimenti la zona generica se la mangia.

function child_shipping_zones_blueprint() {
	$zones = [
		[
			'nome'     => 'Isole e Calabria',
			'province' => [
				// Calabria
				'CZ', 'CS', 'KR', 'RC', 'VV',
				// Sicilia
				'AG', 'CL', 'CT', 'EN', 'ME', 'PA', 'RG', 'SR', 'TP',
				// Sardegna (SU = Sud Sardegna; CI/OG/OT/VS sono le province
				// soppresse nel 2016, incluse solo se WooCommerce le espone ancora)
				'CA', 'NU', 'OR', 'SS', 'SU', 'CI', 'OG', 'OT', 'VS',
			],
			'listino'  => 'isole',
		],
		[
			'nome'    => 'Italia',
			'paese'   => 'IT',
			'listino' => 'italia',
		],
	];

	return apply_filters( 'child_shipping_zones_blueprint', $zones );
}

// ─── Sync ─────────────────────────────────────────────────────────────────────

function child_sync_shipping_zones() {
	if ( ! class_exists( 'WC_Shipping_Zone' ) ) {
		return new WP_Error( 'no_woocommerce', 'WooCommerce non è attivo.' );
	}

	$esistenti = [];
	foreach ( WC_Shipping_Zones::get_zones() as $zona ) {
		$esistenti[] = $zona['zone_name'];
	}

	$province_valide = child_shipping_known_states();
	$log             = [];

	foreach ( child_shipping_zones_blueprint() as $ordine => $blueprint ) {
		if ( in_array( $blueprint['nome'], $esistenti, true ) ) {
			$log[] = sprintf( 'Zona "%s" già presente, saltata.', $blueprint['nome'] );
			continue;
		}

		$zona = new WC_Shipping_Zone();
		$zona->set_zone_name( $blueprint['nome'] );
		$zona->set_zone_order( $ordine );

		if ( ! empty( $blueprint['paese'] ) ) {
			$zona->add_location( $blueprint['paese'], 'country' );
		}

		$ignorate = [];
		foreach ( $blueprint['province'] ?? [] as $provincia ) {
			// Le province soppresse non esistono più nella lista di WooCommerce:
			// aggiungerle produrrebbe una location che non fa match con nulla.
			if ( $province_valide && ! isset( $province_valide[ $provincia ] ) ) {
				$ignorate[] = $provincia;
				continue;
			}

			$zona->add_location( 'IT:' . $provincia, 'state' );
		}

		$zona->save();

		// L'ordine dei metodi dentro la zona è l'ordine di inserimento, ed è
		// anche quello che determina la preselezione al checkout: prima la
		// spedizione, poi il ritiro.
		$id_peso = $zona->add_shipping_method( 'child_weight_shipping' );
		update_option(
			'woocommerce_child_weight_shipping_' . $id_peso . '_settings',
			[
				'title'             => 'Spedizione',
				'tax_status'        => 'taxable',
				'listino'           => $blueprint['listino'],
				'oltre_max'         => 'preventivo',
				'titolo_preventivo' => 'Spedizione da concordare — vi contatteremo',
			]
		);

		$id_ritiro = $zona->add_shipping_method( 'local_pickup' );
		update_option(
			'woocommerce_local_pickup_' . $id_ritiro . '_settings',
			[
				'title'      => 'Ritiro presso la sede di S. Pietro di Morubio (VR)',
				'tax_status' => 'taxable',
				'cost'       => '',
			]
		);

		$log[] = sprintf(
			'Zona "%s" creata (id %d) con listino "%s" + ritiro in sede.%s',
			$blueprint['nome'],
			$zona->get_id(),
			$blueprint['listino'],
			$ignorate ? ' Province ignorate (non più in WooCommerce): ' . implode( ', ', $ignorate ) . '.' : ''
		);
	}

	return $log;
}

/**
 * Province italiane note a WooCommerce, per scartare i codici soppressi.
 * Ritorna [] se la lista non è disponibile: in quel caso non filtriamo nulla.
 */
function child_shipping_known_states() {
	if ( ! function_exists( 'WC' ) || ! WC()->countries ) {
		return [];
	}

	$states = WC()->countries->get_states( 'IT' );

	return is_array( $states ) ? $states : [];
}

// ─── Trigger da wp-admin ──────────────────────────────────────────────────────

function child_maybe_sync_shipping_zones() {
	if ( empty( $_GET['child_sync_shipping'] ) || ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$esito = child_sync_shipping_zones();

	add_action(
		'admin_notices',
		function () use ( $esito ) {
			if ( is_wp_error( $esito ) ) {
				printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $esito->get_error_message() ) );
				return;
			}

			echo '<div class="notice notice-success"><p><strong>Shipping zones</strong></p><ul style="list-style:disc;margin-left:20px">';
			foreach ( $esito as $riga ) {
				printf( '<li>%s</li>', esc_html( $riga ) );
			}
			echo '</ul></div>';
		}
	);
}
add_action( 'admin_init', 'child_maybe_sync_shipping_zones' );
