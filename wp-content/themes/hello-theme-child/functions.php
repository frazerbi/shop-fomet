<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

require_once __DIR__ . '/design-tokens.php';
require_once __DIR__ . '/elementor-kit-sync.php';
require_once __DIR__ . '/performance-optimization.php';
require_once __DIR__ . '/product-category-shortcodes.php';
require_once __DIR__ . '/iubenda.php';

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

// ─── Custom Fonts ─────────────────────────────────────────────────────────────
// Google Fonts: decommentare e aggiornare l'URL con il font scelto.
// Font locali (file in assets/fonts/): usare @font-face in style.scss.

// function child_enqueue_fonts() {
// 	wp_enqueue_style(
// 		'child-theme-font',
// 		'https://fonts.googleapis.com/css2?family=FONT_NAME:wght@400;600;700&display=swap',
// 		[],
// 		null
// 	);
// }
// add_action( 'wp_enqueue_scripts', 'child_enqueue_fonts', 10 );

// ─── Supporto SVG upload ──────────────────────────────────────────────────────
// Necessario per poter caricare logo-fomet.svg da Media Library / Site Identity.

function child_allow_svg( $mimes ) {
	$mimes['svg']  = 'image/svg+xml';
	$mimes['svgz'] = 'image/svg+xml';
	return $mimes;
}
add_filter( 'upload_mimes', 'child_allow_svg' );

// ─── Elementor Kit — Global Colors & Typography ───────────────────────────────
// I valori vengono impostati tramite design-tokens.php e propagati a Elementor
// da elementor-kit-sync.php al momento dell'attivazione del tema.
