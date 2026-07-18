<?php
/**
 * Plugin Name: Antigravity Purchase Notifications for WooCommerce
 * Plugin URI:  https://github.com/example/antigravity-purchase-notifications
 * Description: Increases conversions by displaying authentic recent purchase notifications on product pages using WooCommerce order data.
 * Version:     1.0.0
 * Author:      Antigravity
 * Author URI:  https://github.com/example
 * Text Domain: antigravity-purchase-notifications
 * Domain Path: /languages
 * Requires PHP: 8.0
 * Requires at least: 5.8
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 * License:     GPL-2.0+
 *
 * @package AntigravityPurchaseNotifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define core constants.
define( 'WCPN_VERSION', '1.0.0' );
define( 'WCPN_PLUGIN_FILE', __FILE__ );
define( 'WCPN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCPN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCPN_TEXT_DOMAIN', 'antigravity-purchase-notifications' );

/**
 * Register PSR-4 Autoloader.
 */
spl_autoload_register( function ( $class ) {
	$prefix = 'WooCommercePurchaseNotifications\\';
	$base_dir = WCPN_PLUGIN_DIR . 'src/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

/**
 * Check environments and initialize the plugin.
 */
function wcpn_initialize() {
	// Check PHP version requirement.
	if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
		add_action( 'admin_notices', function () {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'antigravity-purchase-notifications' requires PHP 8.0 or higher to function properly. Please upgrade your PHP version.', 'antigravity-purchase-notifications' ); ?></p>
			</div>
			<?php
		} );
		return;
	}

	// Check if WooCommerce is installed and active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			?>
			<div class="notice notice-warning is-dismissible">
				<p><?php esc_html_e( 'antigravity-purchase-notifications requires WooCommerce to be active.', 'antigravity-purchase-notifications' ); ?></p>
			</div>
			<?php
		} );
		return;
	}

	// Bootstrap the plugin.
	WooCommercePurchaseNotifications\Core\Plugin::instance();
}
add_action( 'plugins_loaded', 'wcpn_initialize' );
