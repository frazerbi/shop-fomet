<?php
/**
 * WooCommerce — Iscrizione newsletter Mailchimp sul checkout
 *
 * Il plugin Mailchimp for WooCommerce stampa in coda ai campi di
 * fatturazione la checkbox newsletter e, se l'audience ha i campi GDPR
 * attivi, il blocco #mailchimp-gdpr-fields con un'opzione per ogni
 * "marketing permission". L'etichetta di quelle opzioni arriva così com'è
 * da Mailchimp (qui: "Email"), non passa da gettext: l'unico punto in cui
 * cambiarla dal tema è il filtro `mailchimp_woocommerce_newsletter_field`,
 * che riceve l'HTML completo (verificato su
 * includes/class-mailchimp-woocommerce-newsletter.php, ramo master).
 *
 * Stili in scss/_checkout.scss (sezione newsletter dentro .col-1).
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ritocchi al testo del blocco GDPR:
 *
 * - toglie l'introduzione "Please select all the ways you would like to hear
 *   from us" (ridondante accanto a un consenso esplicito). Il testo passa da
 *   __() con il text domain del plugin, quindi si cerca la stringa già
 *   tradotta; la <p> che la conteneva resta vuota ed è nascosta via CSS
 *   (p:empty);
 * - sostituisce l'etichetta "Email" con la dichiarazione di consenso
 *   completa, link alla Privacy Policy incluso. Classi del link identiche a
 *   quelle del link nel footer, che apre il documento in popup (confermato
 *   sul sito): iubenda-nostyle + no-brand + iubenda-noiframe + iubenda-embed.
 *
 * @param string $html HTML del campo newsletter (checkbox + blocco GDPR).
 * @return string
 */
function child_mailchimp_gdpr_label( $html ) {
	$intro = __( 'Please select all the ways you would like to hear from us', 'mailchimp-for-woocommerce' );

	$label = 'Confermo di aver letto la '
		. '<a href="https://www.iubenda.com/privacy-policy/32935352" class="iubenda-nostyle no-brand iubenda-noiframe iubenda-embed" title="Privacy Policy">Privacy Policy</a>'
		. ' e acconsento a ricevere via email la newsletter Fomet Shop';

	return str_replace(
		[ $intro, '<span>Email</span>' ],
		[ '', '<span>' . $label . '</span>' ],
		$html
	);
}
add_filter( 'mailchimp_woocommerce_newsletter_field', 'child_mailchimp_gdpr_label' );
