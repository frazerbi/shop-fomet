<?php
/**
 * Performance optimizations for Hello Elementor Child Theme
 * Disabilita funzionalità inutili di WordPress per siti Elementor.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/* ----------------------------------------------------------
 * 1. DISABILITA GUTENBERG / BLOCK EDITOR
 * ---------------------------------------------------------- */
add_filter( 'use_block_editor_for_post',      '__return_false' );
add_filter( 'use_block_editor_for_post_type', '__return_false' );


/* ----------------------------------------------------------
 * 2. RIMUOVI ASSET DI GUTENBERG (CSS inutili sul frontend)
 * ---------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}, 100 );


/* ----------------------------------------------------------
 * 3. RIMUOVI SCRIPT E STILE EMOJI
 * ---------------------------------------------------------- */
remove_action( 'wp_head',             'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles',     'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles',  'print_emoji_styles' );
add_filter(    'emoji_svg_url',       '__return_false' );


/* ----------------------------------------------------------
 * 4. DISABILITA oEMBED
 * ---------------------------------------------------------- */
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'wp_oembed_add_host_js' );
add_filter( 'embed_oembed_discover', '__return_false' );


/* ----------------------------------------------------------
 * 5. PULIZIA <head>
 * ---------------------------------------------------------- */
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
remove_action( 'wp_head', 'feed_links_extra', 3 );
remove_action( 'wp_head', 'feed_links', 2 );


/* ----------------------------------------------------------
 * 6. DISABILITA XML-RPC
 * ---------------------------------------------------------- */
add_filter( 'xmlrpc_enabled', '__return_false' );


/* ----------------------------------------------------------
 * 7. DISABILITA COMMENTI
 * ---------------------------------------------------------- */
add_filter( 'comments_open',  '__return_false', 20, 2 );
add_filter( 'pings_open',     '__return_false', 20, 2 );
add_filter( 'comments_array', '__return_empty_array', 10, 2 );

add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_script( 'comment-reply' );
}, 100 );

// Nasconde i commenti dal menu admin
add_action( 'admin_menu', function () {
	remove_menu_page( 'edit-comments.php' );
} );
add_action( 'admin_bar_menu', function ( $wp_admin_bar ) {
	$wp_admin_bar->remove_node( 'comments' );
}, 999 );


/* ----------------------------------------------------------
 * 8. RIMUOVI QUERY STRING DAGLI ASSET STATICI
 *    DISABILITATO: rimuovere ?ver= impedisce al browser di rilevare
 *    gli aggiornamenti dei file (cache busting). Causa problemi con
 *    le modifiche da Elementor e gli aggiornamenti dei temi/plugin.
 * ---------------------------------------------------------- */
// add_filter( 'script_loader_src', 'wpperf_remove_query_strings', 15 );
// add_filter( 'style_loader_src',  'wpperf_remove_query_strings', 15 );
// function wpperf_remove_query_strings( $src ) {
// 	if ( strpos( $src, '?ver=' ) !== false ) {
// 		$parts = explode( '?ver', $src );
// 		return $parts[0];
// 	}
// 	return $src;
// }


/* ----------------------------------------------------------
 * 9. HEARTBEAT API — 1 richiesta al minuto, solo in admin
 *    In admin serve ovunque, non solo nell'editor: popup di
 *    "sessione scaduta" (wp-auth-check dipende da heartbeat) e
 *    blocco di modifica concorrente degli ordini HPOS
 *    (admin.php?page=wc-orders). Sul frontend non serve.
 * ---------------------------------------------------------- */
add_filter( 'heartbeat_settings', function ( $settings ) {
	$settings['interval'] = 60;
	return $settings;
} );

add_action( 'init', function () {
	if ( is_admin() ) {
		return;
	}
	wp_deregister_script( 'heartbeat' );
} );


/* ----------------------------------------------------------
 * 10. DISABILITA SELF-PING
 * ---------------------------------------------------------- */
add_action( 'pre_ping', function ( &$links ) {
	$home = get_option( 'home' );
	foreach ( $links as $key => $link ) {
		if ( str_starts_with( $link, $home ) ) {
			unset( $links[ $key ] );
		}
	}
} );


/* ----------------------------------------------------------
 * 11. LIMITA LE REVISIONI E L'AUTOSAVE
 * ---------------------------------------------------------- */
add_filter( 'wp_revisions_to_keep', function ( $num, $post ) {
	return 3;
}, 10, 2 );

// L'intervallo di autosave non si può cambiare da qui: WordPress definisce
// AUTOSAVE_INTERVAL (60 s) in wp_functionality_constants(), prima di caricare
// il functions.php del tema. Se serve, va in wp-config.php:
//   define( 'AUTOSAVE_INTERVAL', 180 );


/* ----------------------------------------------------------
 * 12. ELEMENTOR — rimuovi asset inutili sul frontend
 * ---------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'jquery-ui' );
	wp_dequeue_script( 'sharing' );

	// Rimuove le variabili CSS dell'interfaccia admin di Elementor (--e-a-*)
	// Sono usate solo nell'editor, non servono mai sul frontend.
	$is_elementor_preview = defined( 'ELEMENTOR_VERSION' )
		&& isset( \Elementor\Plugin::$instance->preview )
		&& \Elementor\Plugin::$instance->preview->is_preview_mode();

	if ( ! $is_elementor_preview ) {
		wp_dequeue_style( 'e-theme-ui-light' );          // variabili CSS admin (--e-a-*)
		wp_dequeue_style( 'hello-elementor-header-footer' ); // header/footer gestiti da Elementor Theme Builder
	}

	// Stili admin bar di Elementor: servono solo se l'admin bar è visibile
	if ( ! $is_elementor_preview && ! is_admin_bar_showing() ) {
		wp_dequeue_style( 'elementor-wp-admin-bar' );
	}

	// Note di Elementor Pro: solo per utenti loggati
	if ( ! is_user_logged_in() ) {
		wp_dequeue_style( 'elementor-pro-notes-frontend' );
	}
}, 100 );


/* ----------------------------------------------------------
 * 13. CHIUDE LE ROUTE REST DEL CORE AGLI UTENTI NON LOGGATI
 *     Solo l'indice (/) e il namespace /wp/v2 (utenti, post,
 *     media…): le route dei plugin restano aperte perché il
 *     frontend le usa anche da ospite — Stripe crea l'ordine dei
 *     wallet (Apple Pay / Google Pay / Link) via Store API
 *     (/wc/store/v1/checkout), Mailchimp riceve i webhook su
 *     /mailchimp-for-woocommerce/v1, il filtro tassonomia di
 *     Elementor Pro usa /elementor-pro/v1/refresh-loop.
 *     Ognuna ha il proprio permission_callback.
 * ---------------------------------------------------------- */
add_filter( 'rest_authentication_errors', function ( $result ) {
	if ( ! empty( $result ) ) {
		return $result;
	}

	$route = isset( $GLOBALS['wp']->query_vars['rest_route'] )
		? '/' . ltrim( (string) $GLOBALS['wp']->query_vars['rest_route'], '/' )
		: '/';

	if ( '/' !== $route && ! str_starts_with( $route, '/wp/v2' ) ) {
		return $result;
	}

	if ( ! is_user_logged_in() ) {
		return new WP_Error(
			'rest_not_logged_in',
			__( 'API REST disponibile solo per utenti autenticati.', 'hello-elementor-child' ),
			[ 'status' => 401 ]
		);
	}
	return $result;
} );
