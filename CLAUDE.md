# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

This repo is a WordPress site root for **Fomet**, a real company — this repo is the **e-commerce site** being built for them. Only the child theme is tracked in this repo (`wp-content/plugins/` isn't present here), so any store/checkout logic (e.g. WooCommerce) lives outside what's checked in — verify before assuming a specific commerce plugin. The theme itself is at `wp-content/themes/hello-theme-child/`, a child theme for **Hello Elementor** (Elementor's official base theme). It started as a generic starter template; brand values (colors, font, logo) have since been filled in for this project — see below.

> **Note on fomet.it**: the live [fomet.it](https://fomet.it/) domain currently serves Fomet's existing **corporate site**, built on a separate custom theme (`ficus`, Gutenberg block-based) — this is a different codebase from the shop tracked here. It's a useful **design reference** (same brand palette/spacing/font-size scale, already mirrored in `design-tokens.php`/`theme.json`), but don't assume this shop deploys to that exact domain — the production URL for the shop hasn't been confirmed in this repo (possibly a subdomain, e.g. `shop.fomet.it`, matching the old PrestaShop image URLs in the migration CSVs).

**Commerce stack & migration context**: the new shop is built on **WordPress + WooCommerce**, with the storefront built in **Elementor**. It replaces an existing **PrestaShop** store — this is a data migration (products, categories, customers, orders), not a from-scratch catalog build. See "PrestaShop migration" below.

## Repo structure

```
wp-content/
└── themes/
    └── hello-theme-child/   # the child theme (see below)
        └── assets/
            ├── fonts/        # self-hosted webfonts (referenced from theme.json)
            ├── icons/        # SVG icons used across the theme
            ├── js/           # front-end scripts (express-checkout-guard.js)
            └── logo/         # Fomet logo-fomet.svg + favicon-fomet.svg
```

## SCSS workflow

CSS is authored in SCSS and compiled to `style.css`. Never edit `style.css` directly.

`style.scss` is just an **entry point** (theme header comment + an ordered list of `@use "scss/...";` statements) — the actual rules live in `scss/*.scss` partials, one file per concern:

| Partial | Contents |
|---|---|
| `scss/_variables.scss` | `$primary`, `$radius-*`, `$anim-*`/easing — no CSS output on its own |
| `scss/_fonts.scss` | `@font-face` (Sora-fallback) |
| `scss/_mixins.scss` | `heading-color`, `text-color`, `theme-font`, `btn-solid` (currently unused, kept for future use), `arrow-decoration` (animated arrow via `::before`/`::after` on a flex host — shared by the Button widget variants in `_buttons.scss` and the standalone `.btn-arrow` class), `shipping-totals-rows` (totals-table layout + shipping-method list + tax rows — shared by `_cart.scss` and `_checkout.scss`, see "Shipping rows in cart/checkout" below), `account-form-fields` (label/input/password-toggle/submit-button skin shared by the logged-out My Account forms — login, register, lost/reset password, all inside `_my-account.scss`), `notice-panel` (`.woocommerce-error`/`-message`/`-info` as a white panel with a coloured left bar — `--theme-primary`, `--theme-accent` for errors — shared by `_cart.scss`, `_checkout.scss` and `_my-account.scss`; deliberately not scoped to `.woocommerce-notices-wrapper`, since checkout validation errors land in `.woocommerce-NoticeGroup-checkout`) |
| `scss/_tokens.scss` | `:root { --theme-*; --e-global-color-*; }` (Design Tokens block) |
| `scss/_base.scss` | structural resets only — `main { overflow-x: clip; }` + `.elementor .elementor-element` color reset |
| `scss/_spacing.scss` | `.elementor > section > .e-con-inner` responsive padding scale + `--widgets-spacing*` custom-prop resets |
| `scss/_typography.scss` | `body`/`.elementor-widget-container` font-family/smoothing + `.elementor .elementor-element` heading (h1–h6) and `p` rules |
| `scss/_buttons.scss` | `.elementor-widget-button` (default solid + `btn-outlined`/`btn-outlined-white`/`btn-outlined-gray` variants) + `.btn-arrow` |
| `scss/_animations.scss` | keyframes + `.animate-*`/`.will-animate` utilities |
| `scss/_product-card.scss` | product grid card (`ul.products li.product`) + widget "Product Add to Cart" (Loop Grid) |
| `scss/_product-category-badges.scss` | `.product-category-parent`/`.product-category-child` — visual styling for the `[product_parent_category]`/`[product_child_category]` shortcodes (see `product-category-shortcodes.php` below) |
| `scss/_hero.scss` | `.hero-overlay` — dark gradient overlay class for internal-page hero sections/containers with a background image |
| `scss/_image.scss` | Elementor "Image" widget (`.elementor-widget-image img`) — border-radius only |
| `scss/_product-single.scss` | single product page (gallery, title, content, price, additional info, related) |
| `scss/_cart.scss` | Cart page widget |
| `scss/_menu-cart.scss` | Menu (header) mini-cart widget — side-cart panel, distinct from `_cart.scss` |
| `scss/_checkout.scss` | Checkout widget |
| `scss/_my-account.scss` | My Account widget |

Any partial using `$variables` must `@use "variables" as *;` at its top (Dart Sass `@use` scoping is per-file, not transitive) — mirror that when adding a new partial. To add a new area, create `scss/_name.scss` and append `@use "scss/name";` to `style.scss`.

```bash
cd wp-content/themes/hello-theme-child

# Watch mode (development)
npm run watch

# Single build (production — compressed, no source map)
npm run build
```

`npm install` is required once before running these commands (`node_modules/` is gitignored).

## Architecture

### File responsibilities

| File | Role |
|---|---|
| `style.scss` | SCSS entry point: theme header comment + `@use` composition of `scss/*.scss` partials (see "SCSS workflow" below). Compiled to `style.css`. |
| `scss/*.scss` | Actual SCSS source, one partial per concern (variables, tokens, base, buttons, animations, product card, product category badges, product single, cart, checkout, my-account) |
| `functions.php` | Enqueues `style.css` (version read dynamically via `wp_get_theme()->get('Version')` from the theme header, not hardcoded — bump `Version:` in `style.scss`'s header comment to bust cache, or style changes silently won't show in a browser that already cached the old `?ver=`); `child_allow_svg` filter enabled (SVG upload support, needed for the logo assets below) |
| `design-tokens.php` | **Single source of truth** for brand colors and typography. Edit here first. Populated with Fomet's brand palette and `Sora` font (mirrors `theme.json`). |
| `elementor-kit-sync.php` | Reads `child_design_tokens()` and writes values into Elementor's active Kit (Global Colors + Global Typography). Also registers `Sora` with Elementor's font picker via `elementor/fonts/additional_fonts` so it isn't relegated to "custom". |
| `performance-optimization.php` | Generic WordPress/Elementor perf tweaks (disables Gutenberg, emoji, oEmbed, XML-RPC, comments, cleans `<head>`) |
| `product-category-shortcodes.php` | `[product_parent_category]` / `[product_child_category]` shortcodes — resolve the current Loop item's `product_cat` term(s) (via `get_the_ID()`, so no params needed inside an Elementor Loop Grid item) and print a linked badge. Parent walks up `get_ancestors()` to the top-level term if only a child term is directly assigned; child shortcode prints nothing if the product only has a top-level category. Styling lives in `scss/_product-category-badges.scss`; positioning inside the Loop Grid template is done by hand in Elementor, not by this code. |
| `iubenda.php` | Iubenda Cookie Solution integration (cookie consent banner). `wp_head` prints `_iub.csConfiguration`/`csLangConfiguration` + the account's `cs.iubenda.com/sync/<siteId>.js`, `gpp/stub.js`, `iubenda_cs.js`; `wp_footer` prints the standard onload-deferred loader for `iubenda.js` (needed for any in-page popup embed links, see "Iubenda cookie/privacy integration" below). Required via `functions.php`. |
| `woocommerce-express-checkout-guard.php` + `assets/js/express-checkout-guard.js` | Blocks Stripe's Express Checkout wallets (Apple Pay / Google Pay / Link, `#wc-stripe-express-checkout-element`) on the checkout until every visible `.validate-required` field is filled (email also format-checked). Client-side only, by necessity: the buttons live in a Stripe iframe and the plugin's click handler has no veto filter (checked on Stripe 10.8/11.0), so the container gets `inert` + a transparent overlay button that triggers WooCommerce's native `validate` event and scrolls to the first missing field. No server-side check on purpose — in the express flow the order is created from the wallet's name/email/address, not the form's. Styles at the end of `scss/_checkout.scss`. Confirmed working on the real checkout (2026-09-29), so the `#wc-stripe-express-checkout-element` selector matches this store's Stripe version. Required via `functions.php`. |
| `woocommerce-shipping.php` | Custom WooCommerce shipping method `child_weight_shipping` ("Spese di spedizione a peso") — weight-tier rate tables ported from the old PrestaShop store. `child_shipping_rate_tables()` is the single source of truth for the rates (filterable via `child_shipping_rate_tables`); the class only sums the package weight, picks the tier and calls `add_rate()`. Geographic matching is left entirely to WooCommerce's native Shipping Zones. Shipping VAT is deliberately **not** overridden — see the Shipping section below. Required via `functions.php`. |
| `woocommerce-shipping-zones.php` | One-shot idempotent provisioning of the two Shipping Zones + their methods (weight method with the right listino, plus native `local_pickup` at 0). Never runs on its own — trigger with `wp eval 'child_sync_shipping_zones();'` or `?child_sync_shipping=1` on a wp-admin URL. Zones already present (matched by name) are skipped, not overwritten. |
| `theme.json` | Block editor / Global Styles config: color palette, gradients, typography (font sizes, self-hosted `Sora` font faces via `assets/fonts/`), spacing scale, layout widths, and default block/element style resets |
| `assets/fonts/` | Self-hosted `Sora` variable font files (`.woff2`, latin + latin-ext subsets), loaded via `theme.json` `fontFace` |
| `assets/js/` | Front-end scripts, each enqueued by its own PHP file (not bundled/compiled, plain jQuery, versioned by `filemtime()` rather than the theme `Version:`) — currently only `express-checkout-guard.js`. |
| `assets/icons/` | SVG icon set (social, UI, logos) used by templates/widgets |
| `assets/logo/` | Fomet brand SVGs: `logo-fomet.svg`, `favicon-fomet.svg`. Uploaded manually via Site Identity in wp-admin — no automated sync code for these. |

### Design token flow

`design-tokens.php` → `elementor-kit-sync.php` → Elementor Kit post meta

Tokens are pushed automatically on `after_switch_theme`. To re-push manually:
- **WP-CLI**: `wp eval 'child_sync_elementor_kit();'`
- **Admin URL**: append `?child_sync_kit=1` to any wp-admin URL (admin users only)

### Naming conventions

All PHP functions use the prefix `child_`. When starting a new project, find-and-replace `child_` with a project-specific prefix (e.g. `acme_`) across all `.php` files. The `child_` prefix was kept as-is for Fomet.

CSS custom properties use `--theme-*`. The SCSS source variables `$primary` and `$font` (in `scss/_variables.scss`) are the two values to set first for any new project.

### Elementor integration notes

- Styles target Elementor's DOM structure (`.elementor > section > .e-con-inner`, `.elementor-widget-*`)
- `performance-optimization.php` dequeues Elementor admin-only assets on the frontend and keeps Heartbeat alive only inside the page editor
- The kit sync touches `_elementor_page_settings` post meta on the active kit post, then calls `files_manager->clear_cache()` to regenerate CSS
- `theme.json`'s gradients and responsive `clamp()` font-size scale are deliberately **not** synced to Elementor's Kit/Global Typography slots (no 1:1 equivalent — Elementor global colors are flat and typography slots don't support `clamp()`); those stay block-editor-only. The `h1`–`h3` rules hardcoded directly in `scss/_typography.scss` (under `.elementor .elementor-element`) are a separate thing: their `font-size` values are manually kept in sync with `theme.json`'s `extra-large`/`large`/`medium` `clamp()` presets (same formulas, copied by hand — not auto-synced, so if the presets change in `theme.json` these need updating too)

### Design elements ported from fomet.it

The SCSS partials (see "SCSS workflow" above; this section predates the split into `scss/*.scss` but the content still applies, just spread across `scss/_variables.scss`, `scss/_base.scss`, `scss/_spacing.scss`, `scss/_typography.scss`, `scss/_buttons.scss`, `scss/_animations.scss`, `scss/_product-card.scss`) include several elements ported/adapted (2026-07-21) from the fomet.it corporate site's `ficus` theme, for visual consistency across both properties:

- **No `html { font-size: 62.5% }` override** — unlike `ficus` (which uses that to get `1rem = 10px`), this shop keeps the browser default (`1rem = 16px`). An earlier pass did copy `ficus`'s `62.5%` approach, but it was deliberately reverted per user preference (doesn't want a global rem-scaling override affecting site-wide accessibility zoom). **Instead**, every rem literal ported/derived from `ficus` (the `clamp()` scale references, `h1`–`h3` rules below, the radius scale, WooCommerce widget spacing/font-sizes) is pre-multiplied by **0.625** (=10/16) so the rendered pixel output still matches `ficus`'s values without touching the root font-size. When porting any *new* rem-based value from `ficus`/fomet.it, apply the same ×0.625 conversion — do not reintroduce `html{font-size:62.5%}`. Note: `theme.json`'s own `fontSizes` array still holds the raw unscaled `ficus`-scale values (block-editor-only, not reconciled with this ×0.625 approach).
- **Radius scale** — `$radius-s/m/l/xl` (0.75/1.5/2.25/3.25rem, already ×0.625-adjusted) → `--theme-radius-*` custom props. Not present anywhere in this project before (only colors/spacing/font-size were already mirrored in `theme.json`); values were read from `ficus`'s live compiled CSS then scaled per the point above.
- **`.btn-arrow`** — standalone class (sliding-arrow hover effect), applicable via Elementor's "CSS Classes" field. Independent of the existing `.elementor-widget-button` styles.
- **Animation keyframes/utilities** — `.animate-fade-in`, `.animate-fade-in-up`, `.animate-scale-in`, etc., plus `.will-animate`/`.is-visible` (the latter needs a JS `IntersectionObserver` to add `.is-visible` on scroll into view — not wired up in this repo yet, so don't use `.will-animate` alone or the element stays invisible).
- **WooCommerce product card styling** — a `.woocommerce ul.products li.product` block targets the native WooCommerce loop markup. **Confirmed (2026-07-21) this is NOT what the actual shop grid uses**: the product cards are built with Elementor Pro's WooCommerce widgets in a Loop Grid (verified via the real "Product Add to Cart" widget HTML — wrapper class `.elementor-widget-woocommerce-product-add-to-cart`, containing `form.cart` with a `.quantity .qty` input and `a.button.add_to_cart_button`). The add-to-cart button/quantity input are styled correctly, scoped to that widget wrapper. Image/title/price are presumably separate Elementor WooCommerce widgets too (Product Images/Title/Price) but **not yet confirmed or restyled** — get their real HTML before touching them; the leftover `ul.products li.product` block is unverified for this grid and may only apply to classic-template contexts elsewhere (e.g. related/upsell products on the single product page).

**Buttons: every button is a pill (`--theme-radius-xl`), since 2026-09-29.** History: until then the shop mixed shapes — the `.elementor-widget-button` family was rounded with `--theme-radius-m` (2026-07-28), while every WooCommerce widget button (cart/checkout/product-card/my-account/menu-cart, add-to-cart included) stayed square (`border-radius: 0`). The user then explicitly asked that no button be square and that all use a theme radius. Chosen token: `--theme-radius-xl` (52px) for **all** buttons, Elementor and WooCommerce alike — at the buttons' real heights (~37–52px) it always renders a true pill, matching `ficus`/fomet.it; `--theme-radius-m` (24px) would leave the taller full-width CTAs (~50px: `#place_order`, "Procedi al checkout", account submit) with visibly flat ends, and `-s`/`-l` would create a second, inconsistent shape. Hierarchy is carried by colour/fill, not shape: solid `--theme-primary-dark` = primary action, outlined = secondary, both pill. Form fields got the companion token the same day: text inputs/textarea, quantity inputs, Select2 selections, coupon inputs, the Stripe hidden style input (so the card fields inside Stripe's iframe inherit the same radius), shipping-method option rows and the password-strength bar all use `--theme-radius-s` (12px) — still recognisably a field, but no longer a square box next to a pill button. The only `radius: 0` left are resets of nested containers (`.shipping_address`, `#payment`, `form.woocommerce-form`), which are intentional. New button rules must use `var(--theme-radius-xl)` (with `!important` where the surrounding rule already needs it against Elementor Pro defaults).

All three `.elementor-widget-button` variants (default solid, `btn-outlined`, `btn-outlined-white`) plus a fourth added the same day, `btn-outlined-gray` (dark-gray border/text, for use on light/white backgrounds where `btn-outlined`'s primary-color border is too strong), now share the same padding (`.5rem 1.8rem`), `--theme-radius-xl` radius (was `-m` until 2026-09-29, visually identical at this height), and the animated arrow decoration — pulled out into the `arrow-decoration` mixin (`scss/_mixins.scss`) so `.btn-arrow` (standalone, for non-Button-widget elements) reuses the identical animation instead of duplicating it. Only colors (background/border/text, and hover state) differ between variants.

**Brand green (changed 2026-09-29)**: `$primary` is now the logo green `#179D38` ("Fomet Green", replacing the old Dark Teal `#5A8A60`); the former `bright-green` token (same hex) was removed as a duplicate. `#179D38` is only 3.55:1 on white, so a second token `$primary-dark`/`--theme-primary-dark` = `#038037` (also from the logo, 5.06:1 — was `#047734` until 2026-09-30, swapped per user preference for the other logo green) is used for **every green text colour, and for every filled background carrying white text** (buttons, badges, password-strength bar — plus the border paired with them, and `btn-outlined`'s border); `--theme-primary` stays only on text-less accents: notice/nav bars, focus/selected borders, `accent-color`. Follow the same split for new rules. `$secondary` is now a light tint `#D1EBD7` (was Muted Teal `#A8C6AC`); `theme.json` slugs `dark-teal`/`muted-teal`/`teal-fade` were kept for compatibility, only names/values changed.

**Headings use `font-weight: 300` uniformly** across `h1`–`h6` (changed 2026-07-21 from the earlier per-level weight scale, 900→500) — a deliberate user edit, not a regression to revert.

### WooCommerce Elementor Pro widget overrides (single product, cart, checkout, my account)

The single product page, cart, checkout, and my-account pages are all built with dedicated **Elementor Pro WooCommerce widgets**, not WooCommerce's classic shortcode/template markup — confirmed via real rendered HTML (2026-07-22/24), same situation as the product grid above. Wrapper classes:

- **Single product page** (`scss/_product-single.scss`) — `.elementor-widget-woocommerce-product-images` (gallery), `.elementor-widget-woocommerce-product-title`, `.elementor-widget-woocommerce-product-content` (short description), `.elementor-widget-woocommerce-product-price`, `.elementor-widget-woocommerce-product-add-to-cart` (same widget class as the Loop Grid's, in `scss/_product-card.scss` — but here the add-to-cart control is a real `<button class="single_add_to_cart_button button alt">`, not the Loop Grid's `<a class="add_to_cart_button">` AJAX link; both selectors are styled together in `scss/_product-card.scss`), `.elementor-widget-woocommerce-product-additional-information` (attributes table), `.elementor-widget-woocommerce-product-related` (reuses the native `ul.products li.product` markup already styled for the shop grid — no separate styling needed).
- **Cart** (`scss/_cart.scss`) — `.elementor-widget-woocommerce-cart` (`woocommerce-cart.default`): two-column layout, `.e-cart__column-start` (product table + coupon), `.e-cart__column-end` (`.cart_totals` panel).
- **Menu cart** (`scss/_menu-cart.scss`) — `.elementor-widget-woocommerce-menu-cart` (`woocommerce-menu-cart.default`, side-cart variant): header toggle button (`.elementor-menu-cart__toggle_button` + `.elementor-button-icon-qty` bubble badge), slide-out panel (`.elementor-menu-cart__container` > `.elementor-menu-cart__main`), product rows (`.elementor-menu-cart__product`, `-image`/`-name`/`-price`/`-remove`), `.elementor-menu-cart__subtotal`, `.elementor-menu-cart__footer-buttons` (`--view-cart`/`--checkout`). Distinct widget from the Cart page above — confirmed via real HTML (2026-07-24).
- **Checkout** (`scss/_checkout.scss`) — `.elementor-widget-woocommerce-checkout-page` (`woocommerce-checkout-page.default`): two-column, `.e-checkout__column-start` → `#customer_details.col2-set` with `.col-1` (billing fields) and `.col-2` (shipping [empty on this store] + order-note textarea), each wrapped in its own `--theme-white-smoke`/`--theme-radius-m` panel (mirrors the Cart's product-table panel); `.e-checkout__column-end` → `.e-checkout__column-inner.e-sticky-right-column` containing `.e-checkout__order_review` (order table) and `.e-checkout__order_review-2` (payment methods + `#place_order`), each its own panel. The payment block (`#payment.woocommerce-checkout-payment`, styled 2026-08-24 from real HTML) has its native background/padding/radius zeroed since it already sits inside the `-2` panel; `ul.payment_methods` also hosts WooCommerce's "no payment methods available" notice as a `.woocommerce-info` inside an `<li>` — that `li` is neutralized via `:has()` (no row padding/separator, it isn't a method) and the notice is skinned as a white panel with a `--theme-primary` left bar. `.woocommerce-terms-and-conditions-wrapper` and `.woocommerce-privacy-policy-text` are both empty on this store and are hidden (`:not(:has(*:not(:empty)))` / `:empty`) so they don't leave dead space above the order button. **`#place_order` needs `!important` on `background-color`/`border`/`border-radius`/`padding` and on its hover/focus/active states** — Elementor Pro's own "Purchase Button" widget control emits id-scoped CSS (`.elementor-element-xxxx #place_order`, its default blue); an earlier version had `!important` only on `color`, which is why the label was white but the background stayed blue. The **Mailchimp for WooCommerce** newsletter opt-in (added 2026-09-29 from real HTML) lands at the end of `.woocommerce-billing-fields` inside `.col-1`, outside the fields grid: `p.mailchimp-newsletter` is skinned as a highlighted `--theme-secondary` box with enlarged checkboxes; the optional `#mailchimp-gdpr-fields` block is a separate sibling (toggled by the plugin), so it's styled as indented sub-options *outside* the box rather than sharing one panel. Its English labels come from the plugin/Mailchimp audience settings, not the theme.
- **My Account** (`scss/_my-account.scss`) — `.elementor-widget-woocommerce-my-account` (`woocommerce-my-account.default`, `e-my-account-tabs-vertical` modifier). Elementor Pro's widget CSS reads almost every colour from custom properties with light-blue/gray fallbacks (`var(--links-normal-color, #5bc0de)`, `--tabs-*`, `--general-text-color`, `--tables-*`…) behind high-specificity selectors (`.e-my-account-tab:not(.e-my-account-tab__dashboard--custom) .woocommerce a` = 0,4,1) — so colours are fixed by **redefining those variables on the widget wrapper** (top of the partial, applies to every tab incl. unverified ones), not by out-specifying each rule. Only the hardcoded nav props (background, weight, size, `border-width: 0`) need a stronger selector (`.e-my-account-tab .woocommerce` ancestors). Two distinct situations inside the same widget:
  - **Logged in**: only the "Bacheca" (dashboard) tab's nav (`.woocommerce-MyAccount-navigation`) + content (`.woocommerce-MyAccount-content`) are confirmed and styled. The Ordini/Downloads/Indirizzi/Dettagli Account tabs share the same nav but render different content — **not yet verified with real HTML, not styled**; get that HTML before touching them.
  - **Logged out** (styled 2026-09-21 from real HTML): the widget renders **native WooCommerce templates**, not Elementor sub-widgets — `#customer_login.col2-set` (login in `.col-1`, register in `.col-2`), `form.woocommerce-ResetPassword.lost_reset_password` (used for *both* the "lost password" request form and the "set a new password" form — same class, the latter just has two fields), and the post-request confirmation screen (a `.woocommerce-message` plus a bare `<p>`). Each form gets the same `--theme-white-smoke`/`--theme-radius-m` panel as the checkout columns; the shared field/button skin lives in the `account-form-fields` mixin (`scss/_mixins.scss`). Gotchas: the reset forms use the native two-column row classes (`form-row-first`/`form-row-last`, 48% + `float`) which must be reset to full width and `.clear` hidden, or fields render half-width; the form is capped at `max-width: 32rem` since it sits alone on a full-width page; the confirmation `.woocommerce-message` is **not** inside `.woocommerce-notices-wrapper`, so the notice selectors are written without that ancestor (they were scoped to it before and silently missed this screen). Notices are skinned as a white panel with a coloured left bar (`--theme-primary`, `--theme-accent` for errors), mirroring the checkout payment notice; the JS-injected `.woocommerce-password-strength` meter is colour-coded short/bad → `--theme-muted-red`, good → `--theme-gray-dark`, strong → `--theme-primary`.

Two gotchas hit while building the Cart/Checkout overrides (2026-07-24), worth checking for on any future Elementor Pro WooCommerce widget work:
- **Combined column classes, not parent/child**: `.e-cart__column`/`.e-checkout__column` and their `-start`/`-end` variant are two classes on the *same* div (`class="e-cart__column e-cart__column-start"`), not nested elements. Write them as one compound selector (`.e-cart__column-start { ... }`) — never `.e-cart__column { .e-cart__column-start { ... } }`, which SCSS turns into a descendant-combinator selector that matches nothing (silently killing every rule nested inside it — this shipped once in `_cart.scss` and made the whole cart page unstyled until caught).
- **Elementor Pro's own defaults can be id-scoped**: some widget sub-parts ship default CSS scoped with a real DOM id present in the markup (e.g. checkout fields via `.woocommerce #customer_details .form-row .input-text`). No selector built only from classes — however deeply nested — beats an id-based selector on specificity. Project convention: don't mirror the id into our own selector to win the specificity fight; add `!important` on just the conflicting properties instead (explicit user preference, not just a technical default).

Pattern followed for all of these (same as the product-add-to-cart widget): scope every rule to the widget's own wrapper class, skin only — fonts, colors, borders, `--theme-radius-*`, buttons — never rebuild the flex/grid layout, since that's already handled by Elementor Pro's own CSS (e.g. `custom-pro-widget-woocommerce-cart.min.css`), which lives in the plugin (untracked here) and must never be edited directly. Pill buttons (`--theme-radius-xl`) and a `--theme-white-smoke` / `--theme-radius-m` panel for order-summary/totals/gallery sections carry through from the product grid styling for visual consistency; Select2 country/state dropdowns on checkout are re-skinned to match the plain text inputs.

## Iubenda cookie/privacy integration

`iubenda.php` (added 2026-07-28) wires up the shop's **own** Iubenda account — `siteId` 1381751, `cookiePolicyId` 32935352 — distinct from fomet.it/`ficus`'s account (which used `siteId` 259110, `cookiePolicyId` 310856; that config was fully replaced, not extended). No WPML integration: the shop is single-language (Italian only), so `csLangConfiguration` only has an `"it"` entry and there's no dynamic `ICL_LANGUAGE_CODE` → Iubenda-lang mapping (the original file, copied from a WPML-aware source, had one — it was deliberately stripped as dead weight for this project).

The banner's accept/reject button colours are set inline in `_iub.csConfiguration` (not in the Iubenda dashboard) and use `--theme-primary-dark` `#038037` with white text (was Iubenda's default blue `#0073CE` until 2026-09-30) — keep them in sync if the brand green changes.

When adding any in-page link to an Iubenda-hosted document (privacy policy, cookie policy, terms) that should open as an **on-site popup/lightbox** instead of navigating away, the anchor needs both `iubenda-embed` **and** `iub-body-embed` classes:

```html
<a href="https://www.iubenda.com/privacy-policy/32935352" class="iubenda-embed iub-body-embed" title="Privacy Policy">Privacy Policy</a>
```

- `iub-body-embed` is load-bearing, not cosmetic — confirmed by testing (2026-07-28): with only `iubenda-embed`, the script (`iubenda.js`, loaded fine, no console errors) still let the click navigate away instead of opening the popup. Iubenda's own "standard embedding" snippet (the one documented to open a modal) always pairs the two classes together.
- `iubenda-noiframe` does the opposite — it forces a plain full-page navigation instead of a popup. Don't add it if a popup is wanted.
- `iubenda-white`/`iubenda-nostyle`/`no-brand` are purely cosmetic (Iubenda's own badge styling) and can be dropped so the link inherits the theme's normal link styles instead.

## Shipping (WooCommerce)

Rates are ported 1:1 from the old PrestaShop store — see `backup-old-db/export/shipping.md` for the full extraction and the SQL evidence behind every number. Summary of the model:

- **Weight × geographic zone only.** No distance-based calculation, no price-based tiers, no free-shipping threshold (`PS_SHIPPING_FREE_PRICE`/`FREE_WEIGHT` were both 0 on the old store), no handling fee.
- **Two zones, not four.** PrestaShop split Italy into 4 zones (Europe / Calabria / Sicilia / Sardegna) but the last three share identical prices, so they collapse into one. **Zone order is load-bearing**: "Isole e Calabria" must sort *before* "Italia", since WooCommerce applies the first matching zone.
- **Two methods per zone**: the weight method + native Local pickup at 0. Method order inside the zone decides which one is preselected at checkout — shipping first, pickup second. (Note: the old store only offered pickup on zone 1, i.e. not to the islands/Calabria — deliberately widened to both zones here.)
- **Shipping VAT follows the cart, and must not be overridden.** Transport charged by the seller is an accessory supply (art. 12 DPR 633/72): it has no rate of its own, it takes the rate of the principal goods. The old store hardcoded 22% on every order (`fo_carrier.id_tax_rules_group = 1`) while 34 of 39 products are at 4% — that was a bug, not a decision, and is **not** replicated. The catalog is mixed: 34 products at 4%, 4 at 22% (Cappellini, Magliette, Pluviometro, Spandiconcime — the same four that have `weight = 0`), 1 at 10% (BIOKELP®). So the method leaves tax to `add_rate()`, and WooCommerce → Settings → Tax → **"Shipping tax class" must stay on the default "based on cart items"**: a fertiliser-only cart then pays 4%, a cart containing any standard-rate gadget pays 22%. Forcing a flat rate in either direction is wrong.
- **Prices in the tables are VAT-exclusive**, matching the migration CSVs' convention.
- **Over 100 kg the old store had no rule at all** (`range_behavior=1` → carrier simply disappeared). The method makes this configurable per instance (`oltre_max`): `preventivo` (default — a 0 € "Spedizione da concordare" rate so checkout still completes), `nascondi` (exact PrestaShop behaviour), or `ultimo` (apply the last tier — heavily undercharges a pallet, avoid). Also worth noting the old tier delimiters had **coverage gaps** (1.00001–2.00002 kg and 20.00001–21.00002 kg) where no carrier was offered at all; the WooCommerce tiers were rebuilt contiguous.
- 4 of the 39 migrated products have `weight = 0` in the PrestaShop dump — they'd silently land in the 0–1 kg tier. Fix before go-live.

### Shipping rows in cart/checkout (SCSS)

Styled 2026-08-24 from the real rendered HTML of both widgets. The markup is **identical in the two places** (`ul#shipping_method` with one `<li>` per method + `tr.tax-rate` rows), so the skin lives in a shared mixin, `shipping-totals-rows` (`scss/_mixins.scss`), `@include`d inside `table.shop_table` in both `scss/_cart.scss` (`.cart_totals`) and `scss/_checkout.scss` (`.e-checkout__order_review`). Only the cart also has `.woocommerce-shipping-destination` and the `.woocommerce-shipping-calculator` form (country/state via Select2, city/postcode text inputs, "Aggiorna" button) — those stay in `_cart.scss`.

**The totals table is no longer laid out as a table**, and that's deliberate: the shipping row has to span the full width (long method labels like "Ritiro presso la sede di S. Pietro di Morubio (VR)" otherwise widen the amounts column and squash everything against the right edge), and a `<td>` can't span both columns without `colspan` in the markup. Things that were tried and **don't** work — don't reintroduce them:

- `display: block` on just the `<th>`/`<td>` while the `<tr>` stays `table-row`: the block child gets wrapped in an **anonymous table-cell bound to the first column**, so the method list renders narrow and left-stuck.
- `width: 1%`/`25%` on the `<th>` to shrink the label column: keeps both cells on one line, so the problem comes back on narrow viewports.

What's actually in the mixin: `table`/`thead`/`tbody`/`tfoot` → `display: block`, every `tr` → `display: flex` + `space-between` (label left, amount right — visually identical to the table, and inherently mobile-safe since no columns are shared), and `tr.woocommerce-shipping-totals` alone → `flex-direction: column` so the label sits above the full-width method list. The `flex` on the generic `tr` is **structural, not cosmetic**: with the table at `display: block`, any row left as `table-row` would form its own anonymous table and lose the amounts alignment. Other details that are load-bearing:

- **Row separator on the `tr`, not on the cells** — two `border-bottom`s with `space-between` between them leave a visible gap in the middle of the line.
- **`td { min-width: fit-content }`** (not `0`) + `td.product-total { flex: 0 0 auto; white-space: nowrap }` — keeps checkout product names on one line instead of wrapping to make room for the amounts column.
- **`max-width: unset` on the shipping `<td>`** — Elementor Pro/WooCommerce cap the summary cells' width, so without the reset the row doesn't reach the full width even as a block.

If a real `<table>` is ever wanted back for the other rows, the clean route is a WooCommerce template override (`woocommerce/cart/cart-totals.php` + `woocommerce/checkout/review-order.php` in the child theme) adding `colspan="2"` to the shipping cell — not more CSS.

## PrestaShop migration

`backup-old-db/` holds the old PrestaShop store's full SQL dump (`*.sql`, table prefix `fo_`) plus generated WooCommerce-import CSVs — **gitignored**, contains real customer/order data, never commit it.

- `backup-old-db/export/` — migration CSVs (generated 2026-07-14, last updated 2026-07-16): `categories.csv`, `products.csv` (39 simple products, no variants; `Images` holds direct URLs to the old shop `https://shop.fomet.it/<id>/<slug>.jpg` — the old server must be reachable at import time; categories are split into `Categories`/`Subcategories` columns, which is **not** the native WooCommerce importer format anymore — map columns manually or use e.g. WP All Import), `customers.csv` (194 rows: 100 registered + 94 guest), `orders.csv` (93 orders, one row per order with customer/address data and line items as JSON; the native importer doesn't handle orders — needs a custom script or plugin), `product_images_mapping.csv` (id↔image reference map, kept for reference). See `backup-old-db/export/README.md` for the assumptions made (prices are VAT-exclusive, all products map to a 4% `reduced-rate` tax class, stock comes from `fo_stock_available` not `fo_product.quantity`; customer bcrypt password hashes are included in `customers.csv` as `hashed_password` — verifiable natively by WordPress 6.8+, empty for guests and 2 legacy-MD5 accounts).
- To regenerate: load the dump into a throwaway local MySQL container (`docker run mysql:8.0 ...`), query with `JSON_ARRAYAGG`/`JSON_OBJECT` (plain tab-delimited `mysql` CLI output breaks on the HTML product descriptions, which contain embedded newlines/quotes), then remove the container. For targeted extractions, parsing the dump's `INSERT INTO` statements directly with a small Python MySQL-tuple parser (handling `\'` escapes and `NULL`) also works and skips the container. No migration script is kept in the repo — it's regenerate-on-demand from the dump.
