<?php
namespace WooCommercePurchaseNotifications\Frontend;

use WooCommercePurchaseNotifications\Database\OrderQuery;
use WooCommercePurchaseNotifications\Model\Notification;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajax request handler for frontend notification loader.
 */
class Ajax {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wcpn_get_notifications', [ $this, 'get_notifications' ] );
		add_action( 'wp_ajax_nopriv_wcpn_get_notifications', [ $this, 'get_notifications' ] );
	}

	/**
	 * AJAX endpoint to retrieve notifications for the currently viewed product.
	 */
	public function get_notifications() {
		// Nonce verification.
		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'wcpn-frontend-nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security verification failed.', 'woocommerce-purchase-notifications' ) ], 403 );
		}

		// Product ID verification.
		$product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
		if ( ! $product_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid product ID.', 'woocommerce-purchase-notifications' ) ], 400 );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( [ 'message' => __( 'Product not found.', 'woocommerce-purchase-notifications' ) ], 404 );
		}

		// Get option settings.
		$settings = get_option( 'wcpn_settings', [] );

		// Check if exclusions apply.
		if ( self::is_product_excluded( $product_id, $product, $settings ) ) {
			wp_send_json_success( [] );
		}

		// Check Transient Cache.
		$cache_key = 'wcpn_notif_prod_' . $product_id;
		$cached_data = get_transient( $cache_key );
		if ( false !== $cached_data ) {
			wp_send_json_success( $cached_data );
		}

		// Get query parameters.
		$limit        = isset( $settings['general']['maximum_notifications'] ) ? absint( $settings['general']['maximum_notifications'] ) : 10;
		$max_age_days = isset( $settings['general']['maximum_purchase_age'] ) ? absint( $settings['general']['maximum_purchase_age'] ) : 30;
		$statuses     = $settings['orders']['order_status_filter'] ?? [ 'completed', 'processing' ];

		// Fetch eligible orders.
		$orders = OrderQuery::get_recent_orders_by_product( $product_id, [
			'statuses'     => $statuses,
			'limit'        => $limit,
			'max_age_days' => $max_age_days,
		] );

		$notifications = [];
		if ( ! empty( $orders ) ) {
			foreach ( $orders as $order ) {
				$formatted = Notification::format_from_order( $order, $product_id, $settings );
				if ( $formatted ) {
					$notifications[] = $formatted;
				}
			}
		}

		// Cache notifications per product (10 minutes default).
		set_transient( $cache_key, $notifications, 10 * MINUTE_IN_SECONDS );

		wp_send_json_success( $notifications );
	}

	/**
	 * Determine if the product meets any exclusion criteria.
	 *
	 * @param int         $product_id
	 * @param \WC_Product $product
	 * @param array       $settings
	 * @return bool
	 */
	public static function is_product_excluded( int $product_id, \WC_Product $product, array $settings ): bool {
		$filters = $settings['filters'] ?? [];

		// Out of stock.
		if ( ! empty( $filters['exclude_out_of_stock'] ) && ! $product->is_in_stock() ) {
			return true;
		}

		// Virtual.
		if ( ! empty( $filters['exclude_virtual'] ) && $product->is_virtual() ) {
			return true;
		}

		// Downloadable.
		if ( ! empty( $filters['exclude_downloadable'] ) && $product->is_downloadable() ) {
			return true;
		}

		// Specific product ID.
		$exclude_products = $filters['exclude_products'] ?? [];
		if ( ! empty( $exclude_products ) && in_array( $product_id, $exclude_products, true ) ) {
			return true;
		}

		// Product Category.
		$exclude_cats = $filters['exclude_categories'] ?? [];
		if ( ! empty( $exclude_cats ) && has_term( $exclude_cats, 'product_cat', $product_id ) ) {
			return true;
		}

		// Product Tag.
		$exclude_tags = $filters['exclude_tags'] ?? [];
		if ( ! empty( $exclude_tags ) && has_term( $exclude_tags, 'product_tag', $product_id ) ) {
			return true;
		}

		// Product Brand (WooCommerce Brands or general brand taxonomies).
		$exclude_brands = $filters['exclude_brands'] ?? [];
		if ( ! empty( $exclude_brands ) ) {
			$brand_taxonomies = [ 'product_brand', 'brand', 'pwb-brand' ];
			foreach ( $brand_taxonomies as $taxonomy ) {
				if ( taxonomy_exists( $taxonomy ) && has_term( $exclude_brands, $taxonomy, $product_id ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
