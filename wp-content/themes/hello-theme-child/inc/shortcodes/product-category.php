<?php
/**
 * Shortcode categoria prodotto (genitore / figlia)
 * Da usare dentro un widget Testo/Shortcode di Elementor nel template del
 * Loop Grid dell'archivio prodotti. Il posizionamento è a cura dell'utente.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Recupera i termini product_cat assegnati al prodotto, nell'ordine
 * restituito da WordPress (di solito ordine di assegnazione/menu_order).
 */
function child_get_product_cat_terms( $product_id ) {
	$terms = get_the_terms( $product_id, 'product_cat' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return [];
	}
	return $terms;
}

/**
 * Renderizza il badge di categoria, con link opzionale all'archivio categoria.
 */
function child_render_product_cat_badge( $term, $linked, $css_class ) {
	$label = esc_html( $term->name );

	if ( 'yes' === $linked ) {
		$url = get_term_link( $term, 'product_cat' );
		if ( ! is_wp_error( $url ) ) {
			return sprintf(
				'<a href="%1$s" class="%2$s">%3$s</a>',
				esc_url( $url ),
				esc_attr( $css_class ),
				$label
			);
		}
	}

	return sprintf( '<span class="%1$s">%2$s</span>', esc_attr( $css_class ), $label );
}

/**
 * [product_parent_category] — categoria genitore (livello top) del prodotto.
 * Se il prodotto è assegnato solo a una sottocategoria, risale l'albero fino
 * alla categoria di primo livello.
 */
function child_shortcode_product_parent_category( $atts ) {
	$atts = shortcode_atts(
		[
			'id'   => 0,
			'link' => 'yes',
		],
		$atts,
		'product_parent_category'
	);

	$product_id = $atts['id'] ? absint( $atts['id'] ) : get_the_ID();
	if ( ! $product_id ) {
		return '';
	}

	$terms = child_get_product_cat_terms( $product_id );
	if ( empty( $terms ) ) {
		return '';
	}

	// Preferisci un termine già assegnato direttamente a livello top.
	$parent_term = null;
	foreach ( $terms as $term ) {
		if ( 0 === (int) $term->parent ) {
			$parent_term = $term;
			break;
		}
	}

	// Altrimenti risali dalla prima sottocategoria assegnata fino al genitore top-level.
	if ( ! $parent_term ) {
		$ancestors = get_ancestors( $terms[0]->term_id, 'product_cat' );
		if ( ! empty( $ancestors ) ) {
			$parent_term = get_term( end( $ancestors ), 'product_cat' );
		}
	}

	if ( ! $parent_term || is_wp_error( $parent_term ) ) {
		return '';
	}

	return child_render_product_cat_badge( $parent_term, $atts['link'], 'product-category-parent' );
}
add_shortcode( 'product_parent_category', 'child_shortcode_product_parent_category' );

/**
 * [product_child_category] — sottocategoria (figlia) del prodotto, se presente.
 * Se il prodotto è assegnato solo a una categoria di primo livello, non
 * restituisce nulla (niente sottocategoria da mostrare).
 */
function child_shortcode_product_child_category( $atts ) {
	$atts = shortcode_atts(
		[
			'id'   => 0,
			'link' => 'yes',
		],
		$atts,
		'product_child_category'
	);

	$product_id = $atts['id'] ? absint( $atts['id'] ) : get_the_ID();
	if ( ! $product_id ) {
		return '';
	}

	$terms = child_get_product_cat_terms( $product_id );
	if ( empty( $terms ) ) {
		return '';
	}

	$child_term = null;
	foreach ( $terms as $term ) {
		if ( 0 !== (int) $term->parent ) {
			$child_term = $term;
			break;
		}
	}

	if ( ! $child_term ) {
		return '';
	}

	return child_render_product_cat_badge( $child_term, $atts['link'], 'product-category-child' );
}
add_shortcode( 'product_child_category', 'child_shortcode_product_child_category' );
