<?php
/**
 * Supporto upload SVG
 * Necessario per poter caricare logo-fomet.svg da Media Library / Site Identity.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function child_allow_svg( $mimes ) {
	$mimes['svg']  = 'image/svg+xml';
	$mimes['svgz'] = 'image/svg+xml';
	return $mimes;
}
add_filter( 'upload_mimes', 'child_allow_svg' );
