=== Hello Elementor Child — Fomet Shop ===

License: GNU General Public License v3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Tema child di Hello Elementor per lo shop e-commerce Fomet (WooCommerce +
Elementor Pro). Contiene brand (colori, font Sora, token), skin dei widget
WooCommerce di Elementor Pro e la logica di shop (campi checkout, spedizioni a
peso, integrazioni Iubenda/Mailchimp/Stripe).

== Struttura ==

functions.php   Bootstrap: carica i moduli in inc/ e accoda style.css.
inc/            Moduli PHP per area: setup/, shortcodes/, integrations/,
                woocommerce/.
scss/           Sorgenti SCSS: abstracts/, base/, components/, woocommerce/.
style.scss      Entry point SCSS (intestazione del tema + ordine dei partial).
style.css       Compilato da style.scss — non modificarlo a mano.
theme.json      Palette, tipografia e font self-hosted per il block editor.
assets/         Font, icone, logo e script front-end.

== Build CSS ==

npm install       (una volta)
npm run watch     sviluppo
npm run build     produzione (compresso) — committare style.css compilato

Dopo una modifica agli stili, incrementare "Version:" nell'intestazione di
style.scss per invalidare la cache del browser.

Hello Elementor è distribuito sotto licenza GNU GPL v3 o successiva.
