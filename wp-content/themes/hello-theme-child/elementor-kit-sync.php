<?php
/**
 * Elementor Kit Sync — writes design tokens into Elementor Global Colors
 * and Global Typography on theme activation (or manual trigger).
 *
 * Usage — automatic:
 *   Runs on `after_switch_theme`.  Activate / re-activate the child theme
 *   from Appearance > Themes to push tokens.
 *
 * Usage — manual (WP-CLI):
 *   wp eval 'child_sync_elementor_kit();'
 *
 * Usage — manual (admin URL):
 *   /wp-admin/?child_sync_kit=1  (admin users only)
 *
 * @package HelloElementorChild
 */

// ── Hooks ────────────────────────────────────────────────────────────────────

add_action( 'after_switch_theme', 'child_sync_elementor_kit' );
add_action( 'admin_init',         'child_sync_elementor_kit_via_url' );

// ── Public API ────────────────────────────────────────────────────────────────

/**
 * Push design tokens to the active Elementor kit.
 *
 * @return bool  True on success, false if Elementor is not active or no kit found.
 */
function child_sync_elementor_kit(): bool {
	if ( ! did_action( 'elementor/loaded' ) && ! class_exists( '\Elementor\Plugin' ) ) {
		return false;
	}

	$kit_id = (int) get_option( 'elementor_active_kit' );
	if ( ! $kit_id ) {
		return false;
	}

	$tokens   = child_design_tokens();
	$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );

	if ( ! is_array( $settings ) ) {
		$settings = [];
	}

	$settings['system_colors']     = $tokens['system_colors'];
	$settings['custom_colors']     = $tokens['custom_colors'];
	$settings['system_typography'] = $tokens['system_typography'];
	$settings['custom_typography'] = $tokens['custom_typography'];

	update_post_meta( $kit_id, '_elementor_page_settings', $settings );

	child_clear_elementor_cache();

	return true;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Trigger sync when ?child_sync_kit=1 is appended to any admin URL.
 * Restricted to administrators; redirects back after sync.
 */
function child_sync_elementor_kit_via_url(): void {
	if (
		isset( $_GET['child_sync_kit'] ) &&  // phpcs:ignore WordPress.Security.NonceVerification
		current_user_can( 'manage_options' )
	) {
		$success = child_sync_elementor_kit();

		$redirect = add_query_arg(
			[ 'child_synced' => $success ? '1' : '0' ],
			remove_query_arg( 'child_sync_kit' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}
}

/**
 * Clear Elementor's CSS/file cache so new tokens take effect immediately.
 */
function child_clear_elementor_cache(): void {
	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	delete_option( 'elementor_css_print_method' );
}
