<?php
/**
 * Shortcode estratto del contenuto prodotto
 * Da usare dentro un widget Shortcode di Elementor nel template del Loop
 * Grid: prende la descrizione (post_content) del prodotto corrente e la
 * restituisce come testo semplice, troncata a un numero di parole.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [product_content_excerpt] — prime N parole della descrizione del prodotto.
 *
 * Attributi:
 * - words: numero di parole (default 15)
 * - more:  suffisso aggiunto se il testo viene troncato (default "…")
 * - id:    ID prodotto, se usato fuori dal loop (default: prodotto corrente)
 *
 * Il contenuto è letto grezzo, senza passare da the_content: dentro un Loop
 * di Elementor quel filtro può renderizzare di nuovo il template e andare in
 * ricorsione. Se la descrizione è vuota si ripiega sulla descrizione breve.
 */
function child_shortcode_product_content_excerpt( $atts ) {
	$atts = shortcode_atts(
		[
			'id'    => 0,
			'words' => 15,
			'more'  => '…',
		],
		$atts,
		'product_content_excerpt'
	);

	$product_id = $atts['id'] ? absint( $atts['id'] ) : get_the_ID();
	$post       = $product_id ? get_post( $product_id ) : null;
	if ( ! $post ) {
		return '';
	}

	$content = trim( $post->post_content ) ? $post->post_content : $post->post_excerpt;

	// Le descrizioni migrate da PrestaShop sono HTML con entità (&nbsp;, &egrave;…):
	// si tolgono tag e shortcode, si decodificano le entità e si normalizzano gli
	// spazi non separabili, altrimenti wp_trim_words non li conta come separatori.
	$text = wp_strip_all_tags( strip_shortcodes( $content ) );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = str_replace( "\xC2\xA0", ' ', $text );
	$text = wp_trim_words( $text, max( 1, absint( $atts['words'] ) ), $atts['more'] );

	if ( '' === $text ) {
		return '';
	}

	return sprintf( '<p class="product-content-excerpt">%s</p>', esc_html( $text ) );
}
add_shortcode( 'product_content_excerpt', 'child_shortcode_product_content_excerpt' );
