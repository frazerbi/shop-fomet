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
            └── logo/         # Fomet logo-fomet.svg + favicon-fomet.svg
```

## SCSS workflow

CSS is authored in SCSS and compiled to `style.css`. Never edit `style.css` directly.

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
| `style.scss` | Single SCSS source; compiled to `style.css` |
| `functions.php` | Enqueues `style.css` (version read dynamically via `wp_get_theme()->get('Version')` from the theme header, not hardcoded — bump `Version:` in `style.scss`'s header comment to bust cache); `child_allow_svg` filter enabled (SVG upload support, needed for the logo assets below) |
| `design-tokens.php` | **Single source of truth** for brand colors and typography. Edit here first. Populated with Fomet's brand palette and `Sora` font (mirrors `theme.json`). |
| `elementor-kit-sync.php` | Reads `child_design_tokens()` and writes values into Elementor's active Kit (Global Colors + Global Typography). Also registers `Sora` with Elementor's font picker via `elementor/fonts/additional_fonts` so it isn't relegated to "custom". |
| `performance-optimization.php` | Generic WordPress/Elementor perf tweaks (disables Gutenberg, emoji, oEmbed, XML-RPC, comments, cleans `<head>`) |
| `theme.json` | Block editor / Global Styles config: color palette, gradients, typography (font sizes, self-hosted `Sora` font faces via `assets/fonts/`), spacing scale, layout widths, and default block/element style resets |
| `assets/fonts/` | Self-hosted `Sora` variable font files (`.woff2`, latin + latin-ext subsets), loaded via `theme.json` `fontFace` |
| `assets/icons/` | SVG icon set (social, UI, logos) used by templates/widgets |
| `assets/logo/` | Fomet brand SVGs: `logo-fomet.svg`, `favicon-fomet.svg`. Uploaded manually via Site Identity in wp-admin — no automated sync code for these. |

### Design token flow

`design-tokens.php` → `elementor-kit-sync.php` → Elementor Kit post meta

Tokens are pushed automatically on `after_switch_theme`. To re-push manually:
- **WP-CLI**: `wp eval 'child_sync_elementor_kit();'`
- **Admin URL**: append `?child_sync_kit=1` to any wp-admin URL (admin users only)

### Naming conventions

All PHP functions use the prefix `child_`. When starting a new project, find-and-replace `child_` with a project-specific prefix (e.g. `acme_`) across all `.php` files. The `child_` prefix was kept as-is for Fomet.

CSS custom properties use `--theme-*`. The SCSS source variable `$primary` and `$font` are the two values to set first for any new project.

### Elementor integration notes

- Styles target Elementor's DOM structure (`.elementor > section > .e-con-inner`, `.elementor-widget-*`)
- `performance-optimization.php` dequeues Elementor admin-only assets on the frontend and keeps Heartbeat alive only inside the page editor
- The kit sync touches `_elementor_page_settings` post meta on the active kit post, then calls `files_manager->clear_cache()` to regenerate CSS
- `theme.json`'s gradients and responsive `clamp()` font-size scale are deliberately **not** synced to Elementor's Kit/Global Typography slots (no 1:1 equivalent — Elementor global colors are flat and typography slots don't support `clamp()`); those stay block-editor-only. The `h1`–`h3` rules hardcoded directly in `style.scss` (under `.elementor .elementor-element`) are a separate thing: their `font-size` values are manually kept in sync with `theme.json`'s `extra-large`/`large`/`medium` `clamp()` presets (same formulas, copied by hand — not auto-synced, so if the presets change in `theme.json` these need updating too)

### Design elements ported from fomet.it

`style.scss` includes several elements ported/adapted (2026-07-21) from the fomet.it corporate site's `ficus` theme, for visual consistency across both properties:

- **`html { font-size: 62.5%; }`** (in the Base section) — makes `1rem = 10px`. **Critical**: every rem-based value ported from `ficus` (the `clamp()` scale in `theme.json`, the `h1`–`h3` rules below, the radius scale) was authored assuming this ratio. Without it, everything renders ~60% too large (browser default is `1rem = 16px`). Uses a percentage rather than a fixed `10px` so it still scales with the user's browser font-size/zoom setting.
- **Radius scale** — `$radius-s/m/l/xl` (1.2/2.4/3.6/5.2rem) → `--theme-radius-*` custom props. Not present anywhere in this project before (only colors/spacing/font-size were already mirrored in `theme.json`); values were read from `ficus`'s live compiled CSS.
- **`.btn-arrow`** — standalone class (sliding-arrow hover effect), applicable via Elementor's "CSS Classes" field. Independent of the existing `.elementor-widget-button` styles.
- **Animation keyframes/utilities** — `.animate-fade-in`, `.animate-fade-in-up`, `.animate-scale-in`, etc., plus `.will-animate`/`.is-visible` (the latter needs a JS `IntersectionObserver` to add `.is-visible` on scroll into view — not wired up in this repo yet, so don't use `.will-animate` alone or the element stays invisible).
- **WooCommerce product card styling** — a `.woocommerce ul.products li.product` block targets the native WooCommerce loop markup. **Confirmed (2026-07-21) this is NOT what the actual shop grid uses**: the product cards are built with Elementor Pro's WooCommerce widgets in a Loop Grid (verified via the real "Product Add to Cart" widget HTML — wrapper class `.elementor-widget-woocommerce-product-add-to-cart`, containing `form.cart` with a `.quantity .qty` input and `a.button.add_to_cart_button`). The add-to-cart button/quantity input are styled correctly, scoped to that widget wrapper. Image/title/price are presumably separate Elementor WooCommerce widgets too (Product Images/Title/Price) but **not yet confirmed or restyled** — get their real HTML before touching them; the leftover `ul.products li.product` block is unverified for this grid and may only apply to classic-template contexts elsewhere (e.g. related/upsell products on the single product page).

**Buttons intentionally stay square** (`border-radius: 0 !important`, pre-existing in 3 places, also applied to the add-to-cart button) even though `ficus` uses pill-shaped buttons (`--radius-xl`) — this was a deliberate choice for this project, not an oversight; don't "fix" it to match fomet.it.

**Headings use `font-weight: 300` uniformly** across `h1`–`h6` (changed 2026-07-21 from the earlier per-level weight scale, 900→500) — a deliberate user edit, not a regression to revert.

## PrestaShop migration

`backup-old-db/` holds the old PrestaShop store's full SQL dump (`*.sql`, table prefix `fo_`) plus generated WooCommerce-import CSVs — **gitignored**, contains real customer/order data, never commit it.

- `backup-old-db/export/` — migration CSVs (generated 2026-07-14, last updated 2026-07-16): `categories.csv`, `products.csv` (39 simple products, no variants; `Images` holds direct URLs to the old shop `https://shop.fomet.it/<id>/<slug>.jpg` — the old server must be reachable at import time; categories are split into `Categories`/`Subcategories` columns, which is **not** the native WooCommerce importer format anymore — map columns manually or use e.g. WP All Import), `customers.csv` (194 rows: 100 registered + 94 guest), `orders.csv` (93 orders, one row per order with customer/address data and line items as JSON; the native importer doesn't handle orders — needs a custom script or plugin), `product_images_mapping.csv` (id↔image reference map, kept for reference). See `backup-old-db/export/README.md` for the assumptions made (prices are VAT-exclusive, all products map to a 4% `reduced-rate` tax class, stock comes from `fo_stock_available` not `fo_product.quantity`; customer bcrypt password hashes are included in `customers.csv` as `hashed_password` — verifiable natively by WordPress 6.8+, empty for guests and 2 legacy-MD5 accounts).
- To regenerate: load the dump into a throwaway local MySQL container (`docker run mysql:8.0 ...`), query with `JSON_ARRAYAGG`/`JSON_OBJECT` (plain tab-delimited `mysql` CLI output breaks on the HTML product descriptions, which contain embedded newlines/quotes), then remove the container. For targeted extractions, parsing the dump's `INSERT INTO` statements directly with a small Python MySQL-tuple parser (handling `\'` escapes and `NULL`) also works and skips the container. No migration script is kept in the repo — it's regenerate-on-demand from the dump.
