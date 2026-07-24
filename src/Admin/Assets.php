<?php
namespace WooCommercePurchaseNotifications\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin assets enqueuing controller.
 */
class Assets {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Enqueue stylesheet and script assets only on the plugin settings page.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( string $hook ) {
		// Enqueue exclusively on the WooCommerce-purchase-notifications submenu settings page.
		if ( 'woocommerce_page_wcpn-settings' !== $hook ) {
			return;
		}

		// Enqueue WordPress color picker dependencies.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		// Custom admin panel styling.
		wp_enqueue_style(
			'wcpn-admin',
			WCPN_PLUGIN_URL . 'assets/css/admin.css',
			[],
			WCPN_VERSION
		);

		// Custom admin panel script.
		wp_enqueue_script(
			'wcpn-admin',
			WCPN_PLUGIN_URL . 'assets/js/admin.js',
			[ 'jquery', 'wp-color-picker' ],
			WCPN_VERSION,
			true
		);
	}
}
