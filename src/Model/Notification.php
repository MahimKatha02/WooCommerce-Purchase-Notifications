<?php
namespace AntigravityPurchaseNotifications\Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Model class for single purchase notifications.
 */
class Notification {

	/**
	 * Formats order data for a specific product ID into a notification structure.
	 *
	 * @param \WC_Order $order      The WooCommerce order.
	 * @param int       $product_id The product ID.
	 * @param array     $settings   The plugin settings.
	 * @return array|null           Notification data or null on failure.
	 */
	public static function format_from_order( \WC_Order $order, int $product_id, array $settings ): ?array {
		// Find the product item in the order to get quantity.
		$quantity = 1;
		$found    = false;

		foreach ( $order->get_items() as $item ) {
			$item_product_id   = $item->get_product_id();
			$item_variation_id = $item->get_variation_id();

			if ( $item_product_id === $product_id || $item_variation_id === $product_id ) {
				$quantity = $item->get_quantity();
				$found    = true;
				break;
			}
		}

		// Fallback: If not found directly, it shouldn't happen based on our query, but check anyway.
		if ( ! $found ) {
			return null;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return null;
		}

		// 1. Get customer name based on privacy settings.
		$privacy_mode = $settings['privacy']['customer_name_mode'] ?? 'First Name + Initial';
		if ( isset( $settings['privacy']['anonymous_mode'] ) && $settings['privacy']['anonymous_mode'] ) {
			$privacy_mode = 'Anonymous';
		}
		if ( isset( $settings['privacy']['hide_customer_names'] ) && $settings['privacy']['hide_customer_names'] ) {
			$privacy_mode = 'Hidden';
		}

		$first_name = $order->get_billing_first_name();
		$last_name  = $order->get_billing_last_name();
		if ( empty( $first_name ) ) {
			$first_name = $order->get_shipping_first_name();
			$last_name  = $order->get_shipping_last_name();
		}

		$customer_name = self::anonymize_name( $first_name, $last_name, $privacy_mode );

		// 2. Get customer location.
		$location_source = $settings['notification']['customer_location_source'] ?? 'Billing City';
		if ( isset( $settings['privacy']['hide_locations'] ) && $settings['privacy']['hide_locations'] ) {
			$customer_location = '';
		} else {
			$fallback = $settings['notification']['customer_location_fallback'] ?? __( 'Unknown Location', 'woocommerce-purchase-notifications' );
			$customer_location = self::get_location( $order, $location_source, $fallback );
		}

		// 3. Get relative time.
		$order_date = $order->get_date_created();
		$timestamp  = $order_date ? $order_date->getTimestamp() : time();
		
		if ( isset( $settings['privacy']['hide_purchase_time'] ) && $settings['privacy']['hide_purchase_time'] ) {
			$time_ago = '';
		} else {
			$time_ago = self::get_relative_time( $timestamp );
		}

		// 4. Product Name.
		$product_name = $product->get_name();

		// 5. Product Image.
		$image_url = '';
		if ( ! isset( $settings['display']['show_product_image'] ) || $settings['display']['show_product_image'] ) {
			$image_id = $product->get_image_id();
			if ( $image_id ) {
				$image_data = wp_get_attachment_image_src( $image_id, 'thumbnail' );
				if ( $image_data ) {
					$image_url = $image_data[0];
				}
			}
			// Fallback placeholder image.
			if ( empty( $image_url ) ) {
				$image_url = wc_placeholder_img_src( 'thumbnail' );
			}
		}

		// Build content from template.
		$template = $settings['display']['custom_notification_template'] ?? '';
		if ( empty( $template ) ) {
			$template = '{customer_name} from {customer_location} purchased {product_name} {time_ago}.';
		}

		// If name or location are hidden or empty, clean up the template text representation.
		$replacements = [
			'{customer_name}'     => $customer_name,
			'{customer_location}' => $customer_location,
			'{product_name}'      => $product_name,
			'{time_ago}'          => $time_ago,
			'{quantity}'          => ( isset( $settings['display']['show_quantity'] ) && ! $settings['display']['show_quantity'] ) ? '' : $quantity,
		];

		// Clean up template spacing if keys are hidden.
		$formatted_text = $template;
		foreach ( $replacements as $key => $val ) {
			$formatted_text = str_replace( $key, $val, $formatted_text );
		}

		// Remove double spaces or hanging prepositions in case items are empty.
		$formatted_text = preg_replace( '/\s+/', ' ', $formatted_text );
		$formatted_text = str_replace( [ ' from .', ' from ,', ' from yesterday', ' from Just now' ], [ '.', ',', ' yesterday', ' Just now' ], $formatted_text );
		$formatted_text = trim( $formatted_text );

		return [
			'id'        => $order->get_id(),
			'text'      => esc_html( $formatted_text ),
			'image'     => esc_url( $image_url ),
			'verified'  => (bool) ( $settings['display']['show_verified_badge'] ?? true ),
			'permalink' => esc_url( $product->get_permalink() ),
		];
	}

	/**
	 * Anonymize customer name based on privacy settings.
	 *
	 * @param string $first_name
	 * @param string $last_name
	 * @param string $mode
	 * @return string
	 */
	public static function anonymize_name( string $first_name, string $last_name, string $mode ): string {
		$first_name = trim( $first_name );
		$last_name  = trim( $last_name );

		if ( empty( $first_name ) ) {
			return __( 'Someone', 'woocommerce-purchase-notifications' );
		}

		switch ( $mode ) {
			case 'Full First Name':
				return $first_name;

			case 'First Name + Initial':
				if ( ! empty( $last_name ) ) {
					$initial = mb_substr( $last_name, 0, 1, 'UTF-8' );
					return $first_name . ' ' . $initial . '.';
				}
				return $first_name;

			case 'Initial Only':
				return mb_substr( $first_name, 0, 1, 'UTF-8' ) . '.';

			case 'Anonymous':
				return __( 'Someone', 'woocommerce-purchase-notifications' );

			case 'Hidden':
			default:
				return '';
		}
	}

	/**
	 * Extract location string from order based on configuration.
	 *
	 * @param \WC_Order $order
	 * @param string    $source
	 * @param string    $fallback
	 * @return string
	 */
	public static function get_location( \WC_Order $order, string $source, string $fallback ): string {
		$location = '';
		if ( 'Billing City' === $source ) {
			$location = $order->get_billing_city();
		} elseif ( 'Shipping City' === $source ) {
			$location = $order->get_shipping_city();
		}

		$location = trim( $location );
		return ! empty( $location ) ? $location : $fallback;
	}

	/**
	 * Format relative time (e.g., "5 minutes ago").
	 *
	 * @param int $timestamp
	 * @return string
	 */
	public static function get_relative_time( int $timestamp ): string {
		$difference = time() - $timestamp;

		if ( $difference < 60 ) {
			return __( 'Just now', 'woocommerce-purchase-notifications' );
		}

		$minutes = round( $difference / MINUTE_IN_SECONDS );
		if ( $minutes < 60 ) {
			return sprintf(
				/* translators: %d: number of minutes */
				_n( '%d minute ago', '%d minutes ago', $minutes, 'woocommerce-purchase-notifications' ),
				$minutes
			);
		}

		$hours = round( $difference / HOUR_IN_SECONDS );
		if ( $hours < 24 ) {
			return sprintf(
				/* translators: %d: number of hours */
				_n( '%d hour ago', '%d hours ago', $hours, 'woocommerce-purchase-notifications' ),
				$hours
			);
		}

		$days = round( $difference / DAY_IN_SECONDS );
		if ( 1 === (int) $days ) {
			return __( 'Yesterday', 'woocommerce-purchase-notifications' );
		}

		if ( $days < 7 ) {
			return sprintf(
				/* translators: %d: number of days */
				_n( '%d day ago', '%d days ago', $days, 'woocommerce-purchase-notifications' ),
				$days
			);
		}

		$weeks = round( $difference / ( 7 * DAY_IN_SECONDS ) );
		if ( 1 === (int) $weeks ) {
			return __( 'Last week', 'woocommerce-purchase-notifications' );
		}

		if ( $weeks < 4 ) {
			return sprintf(
				/* translators: %d: number of weeks */
				_n( '%d week ago', '%d weeks ago', $weeks, 'woocommerce-purchase-notifications' ),
				$weeks
			);
		}

		$months = round( $difference / ( 30 * DAY_IN_SECONDS ) );
		return sprintf(
			/* translators: %d: number of months */
			_n( '%d month ago', '%d months ago', $months, 'woocommerce-purchase-notifications' ),
			$months
		);
	}
}
