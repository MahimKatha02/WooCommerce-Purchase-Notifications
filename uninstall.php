<?php
/**
 * WooCommerce Purchase Notifications Uninstall
 *
 * @package WooCommercePurchaseNotifications
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete main options.
delete_option( 'wcpn_settings' );

// Delete transients.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wcpn_%' OR option_name LIKE '_transient_timeout_wcpn_%'" );
