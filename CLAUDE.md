# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

This repo is a WordPress site root. The theme lives at `wp-content/themes/hello-theme-child/`, a child theme for **Hello Elementor** (Elementor's official base theme). The theme is a starter template designed to be customised per project by replacing placeholder values.

## Repo structure

```
wp-content/
└── themes/
    └── hello-theme-child/   # the child theme (see below)
        └── assets/
            ├── fonts/        # self-hosted webfonts (referenced from theme.json)
            └── icons/        # SVG icons used across the theme
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
| `functions.php` | Enqueues `style.css`; placeholder hooks for fonts and SVG upload |
| `design-tokens.php` | **Single source of truth** for brand colors and typography. Edit here first. |
| `elementor-kit-sync.php` | Reads `child_design_tokens()` and writes values into Elementor's active Kit (Global Colors + Global Typography) |
| `performance-optimization.php` | Generic WordPress/Elementor perf tweaks (disables Gutenberg, emoji, oEmbed, XML-RPC, comments, cleans `<head>`) |
| `theme.json` | Block editor / Global Styles config: color palette, gradients, typography (font sizes, self-hosted `Sora` font faces via `assets/fonts/`), spacing scale, layout widths, and default block/element style resets |
| `assets/fonts/` | Self-hosted `Sora` variable font files (`.woff2`, latin + latin-ext subsets), loaded via `theme.json` `fontFace` |
| `assets/icons/` | SVG icon set (social, UI, logos) used by templates/widgets |

### Design token flow

`design-tokens.php` → `elementor-kit-sync.php` → Elementor Kit post meta

Tokens are pushed automatically on `after_switch_theme`. To re-push manually:
- **WP-CLI**: `wp eval 'child_sync_elementor_kit();'`
- **Admin URL**: append `?child_sync_kit=1` to any wp-admin URL (admin users only)

### Naming conventions

All PHP functions use the prefix `child_`. When starting a new project, find-and-replace `child_` with a project-specific prefix (e.g. `acme_`) across all `.php` files.

CSS custom properties use `--theme-*`. The SCSS source variable `$primary` and `$font` are the two values to set first for any new project.

### Elementor integration notes

- Styles target Elementor's DOM structure (`.elementor > section > .e-con-inner`, `.elementor-widget-*`)
- `performance-optimization.php` dequeues Elementor admin-only assets on the frontend and keeps Heartbeat alive only inside the page editor
- The kit sync touches `_elementor_page_settings` post meta on the active kit post, then calls `files_manager->clear_cache()` to regenerate CSS
