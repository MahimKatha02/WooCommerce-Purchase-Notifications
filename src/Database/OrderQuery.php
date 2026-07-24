<?php
namespace WooCommercePurchaseNotifications\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DB Order Query Manager.
 */
class OrderQuery {

	/**
	 * Check if High Performance Order Storage (HPOS) is active.
	 *
	 * @return bool
	 */
	public static function is_hpos_enabled(): bool {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && method_exists( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' ) ) {
			return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		}
		return false;
	}

	/**
	 * Get recent order IDs for a specific product ID.
	 *
	 * @param int   $product_id The WC product ID.
	 * @param array $args       Query configuration parameters.
	 * @return array            Array of WC_Order objects or empty array.
	 */
	public static function get_recent_orders_by_product( int $product_id, array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'statuses'     => [ 'completed', 'processing' ],
			'limit'        => 10,
			'max_age_days' => 30,
		];

		$params = wp_parse_args( $args, $defaults );

		// Normalize statuses to include wc- prefix.
		$normalized_statuses = array_map( function ( $status ) {
			return 'wc-' . ltrim( $status, 'wc-' );
		}, $params['statuses'] );

		// Determine HPOS vs. Custom Post Type.
		$is_hpos = self::is_hpos_enabled();
		if ( $is_hpos ) {
			$orders_table = $wpdb->prefix . 'wc_orders';
			$id_col       = 'id';
			$status_col   = 'status';
			$date_col     = 'date_created';
		} else {
			$orders_table = $wpdb->posts;
			$id_col       = 'ID';
			$status_col   = 'post_status';
			$date_col     = 'post_date';
		}

		// Base SQL structure.
		$status_placeholders = implode( ',', array_fill( 0, count( $normalized_statuses ), '%s' ) );
		$sql = "SELECT DISTINCT items.order_id 
				FROM {$wpdb->prefix}woocommerce_order_items as items 
				INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta as itemmeta ON items.order_item_id = itemmeta.order_item_id 
				INNER JOIN {$orders_table} as orders ON items.order_id = orders.{$id_col} 
				WHERE itemmeta.meta_key = '_product_id' 
				AND itemmeta.meta_value = %d 
				AND orders.{$status_col} IN ($status_placeholders)";

		$query_args = array_merge( [ $product_id ], $normalized_statuses );

		// Add age filter if configured.
		if ( $params['max_age_days'] > 0 ) {
			$max_age_seconds = $params['max_age_days'] * DAY_IN_SECONDS;
			$date_limit      = date( 'Y-m-d H:i:s', time() - $max_age_seconds );
			$sql            .= " AND orders.{$date_col} >= %s";
			$query_args[]    = $date_limit;
		}

		// Add ordering and limit.
		$sql          .= " ORDER BY orders.{$date_col} DESC LIMIT %d";
		$query_args[]  = $params['limit'];

		// Prepare and run the query.
		$prepared_query = $wpdb->prepare( $sql, $query_args );
		$order_ids      = $wpdb->get_col( $prepared_query );

		if ( empty( $order_ids ) ) {
			return [];
		}

		$orders = [];
		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$orders[] = $order;
			}
		}

		return $orders;
	}
}
