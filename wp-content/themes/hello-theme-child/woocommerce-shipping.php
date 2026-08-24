<?php
/**
 * Spedizioni a scaglioni di peso — metodo di spedizione custom.
 *
 * Replica le tariffe del vecchio shop PrestaShop (corriere "Bartolini", tariffe
 * per peso × zona). Il matching geografico resta a carico delle Shipping Zone
 * native di WooCommerce: qui vive solo il listino.
 *
 * Uso: WooCommerce → Impostazioni → Spedizione → [zona] → Aggiungi metodo →
 * "Spese di spedizione a peso", poi scegliere il listino nelle impostazioni
 * del metodo.
 *
 * @package HelloElementorChild
 */

defined( 'ABSPATH' ) || exit;

// ─── Listini ──────────────────────────────────────────────────────────────────
// Unica fonte di verità per le tariffe. Prezzi IVA ESCLUSA, pesi in kg.
// `a` è il limite superiore incluso: uno scaglione copre (precedente, a].
// L'ultimo scaglione di ogni listino definisce il peso massimo gestito.

function child_shipping_rate_tables() {
	$tables = [
		'italia' => [
			'label' => 'Italia (continente)',
			'rates' => [
				[ 'a' => 1,   'costo' => 10.00 ],
				[ 'a' => 5,   'costo' => 11.63 ],
				[ 'a' => 10,  'costo' => 13.25 ],
				[ 'a' => 20,  'costo' => 15.13 ],
				[ 'a' => 30,  'costo' => 16.75 ],
				[ 'a' => 50,  'costo' => 20.34 ],
				[ 'a' => 100, 'costo' => 22.00 ],
			],
		],
		'isole' => [
			'label' => 'Isole e Calabria',
			'rates' => [
				[ 'a' => 1,   'costo' => 13.00 ],
				[ 'a' => 5,   'costo' => 14.50 ],
				[ 'a' => 10,  'costo' => 15.75 ],
				[ 'a' => 20,  'costo' => 18.63 ],
				[ 'a' => 30,  'costo' => 24.13 ],
				[ 'a' => 50,  'costo' => 29.63 ],
				[ 'a' => 100, 'costo' => 41.38 ],
			],
		],
	];

	return apply_filters( 'child_shipping_rate_tables', $tables );
}

// ─── Registrazione del metodo ─────────────────────────────────────────────────

function child_register_weight_shipping_method( $methods ) {
	$methods['child_weight_shipping'] = 'Child_Weight_Shipping_Method';
	return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'child_register_weight_shipping_method' );

function child_init_weight_shipping_method() {
	if ( ! class_exists( 'WC_Shipping_Method' ) || class_exists( 'Child_Weight_Shipping_Method' ) ) {
		return;
	}

	class Child_Weight_Shipping_Method extends WC_Shipping_Method {

		public function __construct( $instance_id = 0 ) {
			$this->id                 = 'child_weight_shipping';
			$this->instance_id        = absint( $instance_id );
			$this->method_title       = __( 'Spese di spedizione a peso', 'hello-elementor-child' );
			$this->method_description = __( 'Tariffe a scaglioni di peso, definite nel tema child (woocommerce-shipping.php).', 'hello-elementor-child' );
			$this->supports           = [
				'shipping-zones',
				'instance-settings',
				'instance-settings-modal',
			];

			$this->init();
		}

		public function init() {
			$this->init_form_fields();
			$this->init_settings();

			$this->title = $this->get_option( 'title' );
			$this->enabled = $this->get_option( 'enabled', 'yes' );

			add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
		}

		public function init_form_fields() {
			$options = [];
			foreach ( child_shipping_rate_tables() as $key => $table ) {
				$options[ $key ] = $table['label'];
			}

			$this->instance_form_fields = [
				'title' => [
					'title'       => __( 'Titolo', 'hello-elementor-child' ),
					'type'        => 'text',
					'description' => __( 'Etichetta mostrata al cliente in carrello e checkout.', 'hello-elementor-child' ),
					'default'     => __( 'Spedizione', 'hello-elementor-child' ),
					'desc_tip'    => true,
				],
				'listino' => [
					'title'       => __( 'Listino', 'hello-elementor-child' ),
					'type'        => 'select',
					'class'       => 'wc-enhanced-select',
					'options'     => $options,
					'default'     => 'italia',
					'description' => $this->rates_preview(),
				],
				'oltre_max' => [
					'title'       => __( 'Oltre il peso massimo', 'hello-elementor-child' ),
					'type'        => 'select',
					'class'       => 'wc-enhanced-select',
					'options'     => [
						'nascondi' => __( 'Non offrire la spedizione (come il vecchio PrestaShop)', 'hello-elementor-child' ),
						'ultimo'   => __( 'Applica la tariffa dell\'ultimo scaglione', 'hello-elementor-child' ),
						'preventivo' => __( 'Mostra "Spedizione da concordare" a costo 0', 'hello-elementor-child' ),
					],
					'default'     => 'preventivo',
					'description' => __( 'Comportamento quando il carrello supera il peso massimo del listino (100 kg).', 'hello-elementor-child' ),
					'desc_tip'    => true,
				],
				'titolo_preventivo' => [
					'title'       => __( 'Etichetta "da concordare"', 'hello-elementor-child' ),
					'type'        => 'text',
					'default'     => __( 'Spedizione da concordare — vi contatteremo', 'hello-elementor-child' ),
					'description' => __( 'Usata solo con l\'opzione "Mostra Spedizione da concordare".', 'hello-elementor-child' ),
					'desc_tip'    => true,
				],
			];
		}

		/**
		 * Tabella riepilogativa dei listini, mostrata sotto il select in admin
		 * così le tariffe restano leggibili senza aprire il file PHP.
		 */
		private function rates_preview() {
			$out = '<table class="widefat striped" style="max-width:420px;margin-top:8px"><thead><tr><th>Listino</th><th>Fino a (kg)</th><th>Costo (IVA escl.)</th></tr></thead><tbody>';

			foreach ( child_shipping_rate_tables() as $table ) {
				foreach ( $table['rates'] as $i => $rate ) {
					$out .= '<tr>';
					$out .= 0 === $i
						? '<td rowspan="' . count( $table['rates'] ) . '"><strong>' . esc_html( $table['label'] ) . '</strong></td>'
						: '';
					$out .= '<td>' . esc_html( $rate['a'] ) . '</td>';
					$out .= '<td>' . esc_html( number_format_i18n( $rate['costo'], 2 ) ) . ' &euro;</td>';
					$out .= '</tr>';
				}
			}

			return $out . '</tbody></table>';
		}

		public function calculate_shipping( $package = [] ) {
			$tables  = child_shipping_rate_tables();
			$listino = $this->get_option( 'listino', 'italia' );

			if ( ! isset( $tables[ $listino ] ) ) {
				return;
			}

			$rates  = $tables[ $listino ]['rates'];
			$peso   = $this->package_weight( $package );
			$costo  = null;
			$label  = $this->title;

			foreach ( $rates as $rate ) {
				if ( $peso <= $rate['a'] ) {
					$costo = $rate['costo'];
					break;
				}
			}

			// Fuori scala: il carrello pesa più dell'ultimo scaglione.
			if ( null === $costo ) {
				switch ( $this->get_option( 'oltre_max', 'preventivo' ) ) {
					case 'ultimo':
						$costo = end( $rates )['costo'];
						break;

					case 'preventivo':
						$costo = 0.0;
						$label = $this->get_option( 'titolo_preventivo' );
						break;

					default: // 'nascondi'
						return;
				}
			}

			$this->add_rate( [
				'id'        => $this->get_rate_id(),
				'label'     => $label,
				'cost'      => $costo,
				'taxes'     => $this->shipping_taxes( $costo ),
				'package'   => $package,
				'meta_data' => [ __( 'Peso spedizione', 'hello-elementor-child' ) => $peso . ' kg' ],
			] );
		}

		/**
		 * Peso totale del pacco in kg (i prodotti virtuali non arrivano qui:
		 * WooCommerce li esclude a monte dal package).
		 */
		private function package_weight( $package ) {
			$peso = 0.0;

			foreach ( $package['contents'] as $item ) {
				$product = $item['data'];

				if ( ! $product || ! $product->needs_shipping() ) {
					continue;
				}

				$peso += (float) wc_get_weight( (float) $product->get_weight(), 'kg' ) * (int) $item['quantity'];
			}

			return round( $peso, 3 );
		}

		/**
		 * IVA sulla spedizione forzata su aliquota ORDINARIA (22%).
		 * L'impostazione nativa di WooCommerce ("classe fiscale spedizione:
		 * eredita dagli articoli") applicherebbe il 4% dei prodotti, mentre il
		 * vecchio shop tassava la spedizione al 22% (fo_carrier.id_tax_rules_group = 1).
		 */
		private function shipping_taxes( $costo ) {
			if ( ! wc_tax_enabled() || $costo <= 0 ) {
				return [];
			}

			return WC_Tax::calc_shipping_tax( $costo, WC_Tax::get_shipping_tax_rates( '' ) );
		}
	}
}
add_action( 'woocommerce_shipping_init', 'child_init_weight_shipping_method' );
