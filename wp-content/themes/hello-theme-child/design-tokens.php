<?php
/**
 * Design Tokens — single source of truth for the child theme.
 *
 * Edit values here; elementor-kit-sync.php propagates them to Elementor
 * Global Colors and Global Typography automatically on theme (re)activation.
 *
 * @package HelloElementorChild
 */

function child_design_tokens(): array {
	return [

		// ── Colors ──────────────────────────────────────────────────────────────
		//
		// 'system' keys map to Elementor's four built-in slots (primary, secondary,
		// text, accent).  'custom' keys become additional Global Colors.

		'system_colors' => [
			[
				'_id'   => 'primary',
				'title' => 'Primary',
				'color' => '#000000',   // TODO: set brand primary color
			],
			[
				'_id'   => 'secondary',
				'title' => 'Secondary',
				'color' => '#000000',   // TODO: set brand secondary color
			],
			[
				'_id'   => 'text',
				'title' => 'Text',
				'color' => '#000000',
			],
			[
				'_id'   => 'accent',
				'title' => 'Accent',
				'color' => '#000000',   // TODO: set brand accent color
			],
		],

		'custom_colors' => [
			[
				'_id'   => 'theme_white',
				'title' => 'Bianco',
				'color' => '#ffffff',
			],
			[
				'_id'   => 'theme_black',
				'title' => 'Nero',
				'color' => '#000000',
			],
		],

		// ── Typography ──────────────────────────────────────────────────────────
		//
		// 'system' keys map to Elementor's four built-in typography slots.

		'system_typography' => [
			[
				'_id'                    => 'primary',
				'title'                  => 'Primary',
				'typography_typography'  => 'custom',
				'typography_font_family' => 'sans-serif', // TODO: set font family
				'typography_font_weight' => '700',
			],
			[
				'_id'                    => 'secondary',
				'title'                  => 'Secondary',
				'typography_typography'  => 'custom',
				'typography_font_family' => 'sans-serif',
				'typography_font_weight' => '600',
			],
			[
				'_id'                    => 'text',
				'title'                  => 'Text',
				'typography_typography'  => 'custom',
				'typography_font_family' => 'sans-serif',
				'typography_font_weight' => '400',
			],
			[
				'_id'                    => 'accent',
				'title'                  => 'Accent',
				'typography_typography'  => 'custom',
				'typography_font_family' => 'sans-serif',
				'typography_font_weight' => '500',
			],
		],

		'custom_typography' => [],
	];
}
