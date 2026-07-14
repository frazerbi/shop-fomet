<?php
/**
 * Design Tokens — single source of truth for the child theme.
 *
 * Values here mirror the palette and font family defined in theme.json, so
 * Elementor's Global Colors/Fonts and the block editor stay in sync.
 * elementor-kit-sync.php propagates them to Elementor Global Colors and
 * Global Typography automatically on theme (re)activation.
 *
 * @package HelloElementorChild
 */

function child_design_tokens(): array {
	return [

		// ── Colors ──────────────────────────────────────────────────────────────
		//
		// 'system' keys map to Elementor's four built-in slots (primary, secondary,
		// text, accent).  'custom' keys become additional Global Colors.
		// Source palette: theme.json settings.color.palette.

		'system_colors' => [
			[
				'_id'   => 'primary',
				'title' => 'Primary',
				'color' => '#5A8A60',   // Dark Teal
			],
			[
				'_id'   => 'secondary',
				'title' => 'Secondary',
				'color' => '#A8C6AC',   // Muted Teal
			],
			[
				'_id'   => 'text',
				'title' => 'Text',
				'color' => '#282828',   // Shadow Gray
			],
			[
				'_id'   => 'accent',
				'title' => 'Accent',
				'color' => '#D21D22',   // Flag Red
			],
		],

		'custom_colors' => [
			[
				'_id'   => 'gray_dark',
				'title' => 'Gray dark',
				'color' => '#4A4A4A',
			],
			[
				'_id'   => 'gray_medium',
				'title' => 'Gray medium',
				'color' => '#898989',
			],
			[
				'_id'   => 'gray_light',
				'title' => 'Gray light',
				'color' => '#C8C8C8',
			],
			[
				'_id'   => 'gray_pale',
				'title' => 'Gray pale',
				'color' => '#EDEDED',
			],
			[
				'_id'   => 'white_smoke',
				'title' => 'White smoke',
				'color' => '#F4F4F4',
			],
			[
				'_id'   => 'alabaster_gray',
				'title' => 'Alabaster Gray',
				'color' => '#F2F0E6',
			],
			[
				'_id'   => 'theme_white',
				'title' => 'White',
				'color' => '#FFFFFF',
			],
			[
				'_id'   => 'bright_green',
				'title' => 'Bright Green',
				'color' => '#179D38',
			],
			[
				'_id'   => 'muted_red',
				'title' => 'Muted Red',
				'color' => '#B85450',
			],
		],

		// ── Typography ──────────────────────────────────────────────────────────
		//
		// 'system' keys map to Elementor's four built-in typography slots.
		// Font family matches theme.json settings.typography.fontFamilies (Sora,
		// self-hosted via assets/fonts/ — see theme.json for the @font-face rules).

		'system_typography' => [
			[
				'_id'                    => 'primary',
				'title'                  => 'Primary',
				'typography_typography'  => 'custom',
				'typography_font_family' => 'Sora',
				'typography_font_weight' => '700',
			],
			[
				'_id'                    => 'secondary',
				'title'                  => 'Secondary',
				'typography_typography'  => 'custom',
				'typography_font_family' => 'Sora',
				'typography_font_weight' => '600',
			],
			[
				'_id'                    => 'text',
				'title'                  => 'Text',
				'typography_typography'  => 'custom',
				'typography_font_family' => 'Sora',
				'typography_font_weight' => '400',
			],
			[
				'_id'                    => 'accent',
				'title'                  => 'Accent',
				'typography_typography'  => 'custom',
				'typography_font_family' => 'Sora',
				'typography_font_weight' => '500',
			],
		],

		'custom_typography' => [],
	];
}
