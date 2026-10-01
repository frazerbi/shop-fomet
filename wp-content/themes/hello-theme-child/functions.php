<?php
/**
 * Theme functions and definitions
 *
 * Solo bootstrap: la logica vive nei moduli in inc/, uno per funzionalità.
 *
 * @package HelloElementorChild
 */

// ─── Moduli ───────────────────────────────────────────────────────────────────

// Setup: design token (sincronizzati sul Kit Elementor), performance, SVG.
require_once __DIR__ . '/inc/setup/design-tokens.php';
require_once __DIR__ . '/inc/setup/elementor-kit-sync.php';
require_once __DIR__ . '/inc/setup/performance-optimization.php';
require_once __DIR__ . '/inc/setup/svg-upload.php';

// Shortcode per il template del Loop Grid prodotti.
require_once __DIR__ . '/inc/shortcodes/product-category.php';
require_once __DIR__ . '/inc/shortcodes/product-content-excerpt.php';

// Integrazioni con servizi/plugin terzi.
require_once __DIR__ . '/inc/integrations/iubenda.php';
require_once __DIR__ . '/inc/integrations/mailchimp.php';

// WooCommerce: checkout e spedizioni.
require_once __DIR__ . '/inc/woocommerce/checkout-fields.php';
require_once __DIR__ . '/inc/woocommerce/express-checkout-guard.php';
require_once __DIR__ . '/inc/woocommerce/shipping.php';
require_once __DIR__ . '/inc/woocommerce/shipping-zones.php';

// ─── Enqueue ─────────────────────────────────────────────────────────────────

function child_enqueue_scripts() {
	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[ 'hello-elementor-theme-style' ],
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'child_enqueue_scripts', 20 );
