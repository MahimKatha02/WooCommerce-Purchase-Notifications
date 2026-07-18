<?php
namespace WooCommercePurchaseNotifications\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings page manager.
 */
class Settings {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_filter( 'option_wcpn_settings', [ $this, 'merge_default_settings' ] );
	}

	/**
	 * Retrieve plugin default configurations.
	 *
	 * @return array
	 */
	public static function get_default_settings(): array {
		return [
			'general' => [
				'enable_plugin'         => 1,
				'enable_mobile'         => 1,
				'enable_tablet'         => 1,
				'enable_desktop'        => 1,
				'notification_position' => 'Bottom Left',
				'display_delay'         => 5,
				'rotation_interval'     => 8,
				'animation_speed'       => 500,
				'maximum_notifications' => 10,
				'maximum_purchase_age'  => 30,
				'pause_on_hover'        => 1,
				'dismiss_notifications' => 1,
			],
			'display' => [
				'show_product_image'            => 1,
				'show_customer_name'            => 1,
				'show_customer_location'        => 1,
				'show_quantity'                 => 1,
				'show_purchase_time'            => 1,
				'show_verified_badge'           => 1,
				'custom_notification_template'  => '{customer_name} from {customer_location} purchased {quantity}x {product_name} {time_ago}.',
			],
			'privacy' => [
				'customer_name_mode'        => 'First Name + Initial',
				'anonymous_mode'            => 0,
				'hide_customer_names'       => 0,
				'hide_locations'            => 0,
				'hide_quantity'             => 0,
				'hide_purchase_time'        => 0,
				'gdpr_mode'                 => 1,
				'auto_name_anonymize'       => 1,
			],
			'filters' => [
				'exclude_products'          => '',
				'exclude_categories'        => '',
				'exclude_tags'              => '',
				'exclude_brands'            => '',
				'exclude_out_of_stock'      => 0,
				'exclude_virtual'           => 0,
				'exclude_downloadable'      => 0,
			],
			'orders' => [
				'order_status_filter'       => [ 'completed', 'processing' ],
				'minimum_completed_orders'  => 0,
				'ignore_refunded_orders'    => 1,
				'ignore_failed_orders'      => 1,
				'ignore_cancelled_orders'   => 1,
			],
			'appearance' => [
				'background_color'          => '#ffffff',
				'text_color'                => '#2c3e50',
				'accent_color'              => '#3498db',
				'border_radius'             => '12px',
				'border'                    => '1px solid rgba(0, 0, 0, 0.06)',
				'shadow'                    => '0 8px 30px rgba(0, 0, 0, 0.12)',
				'padding'                   => '14px 18px',
				'spacing'                   => '24px',
				'font_family'               => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
				'font_size'                 => '14px',
				'notification_width'        => '360px',
				'image_size'                => '60px',
				'animation_type'            => 'Slide Up',
				'custom_css'                => '',
			]
		];
	}

	/**
	 * Hook filter to merge default values when option is loaded.
	 *
	 * @param mixed $value
	 * @return array
	 */
	public function merge_default_settings( $value ): array {
		if ( ! is_array( $value ) ) {
			$value = [];
		}
		return array_replace_recursive( self::get_default_settings(), $value );
	}

	/**
	 * Register submenu settings page under WooCommerce.
	 */
	public function add_settings_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Purchase Notifications', 'woocommerce-purchase-notifications' ),
			__( 'Purchase Notifications', 'woocommerce-purchase-notifications' ),
			'manage_woocommerce',
			'wcpn-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Register settings option and sanitize values.
	 */
	public function register_settings() {
		register_setting(
			'wcpn_settings_group',
			'wcpn_settings',
			[
				'sanitize_callback' => [ $this, 'sanitize_settings' ]
			]
		);
	}

	/**
	 * Sanitizes user inputs before database storage.
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_settings( array $input ): array {
		$output = self::get_default_settings();

		// 1. General Settings.
		if ( isset( $input['general'] ) ) {
			$output['general']['enable_plugin']         = isset( $input['general']['enable_plugin'] ) ? 1 : 0;
			$output['general']['enable_mobile']         = isset( $input['general']['enable_mobile'] ) ? 1 : 0;
			$output['general']['enable_tablet']         = isset( $input['general']['enable_tablet'] ) ? 1 : 0;
			$output['general']['enable_desktop']        = isset( $input['general']['enable_desktop'] ) ? 1 : 0;
			$output['general']['pause_on_hover']        = isset( $input['general']['pause_on_hover'] ) ? 1 : 0;
			$output['general']['dismiss_notifications'] = isset( $input['general']['dismiss_notifications'] ) ? 1 : 0;

			if ( isset( $input['general']['notification_position'] ) ) {
				$output['general']['notification_position'] = sanitize_text_field( $input['general']['notification_position'] );
			}
			$output['general']['display_delay']         = max( 0, absint( $input['general']['display_delay'] ?? 5 ) );
			$output['general']['rotation_interval']     = max( 1, absint( $input['general']['rotation_interval'] ?? 8 ) );
			$output['general']['animation_speed']       = max( 100, absint( $input['general']['animation_speed'] ?? 500 ) );
			$output['general']['maximum_notifications'] = max( 1, absint( $input['general']['maximum_notifications'] ?? 10 ) );
			$output['general']['maximum_purchase_age']  = absint( $input['general']['maximum_purchase_age'] ?? 30 );
		}

		// 2. Display Settings.
		if ( isset( $input['display'] ) ) {
			$output['display']['show_product_image']   = isset( $input['display']['show_product_image'] ) ? 1 : 0;
			$output['display']['show_customer_name']   = isset( $input['display']['show_customer_name'] ) ? 1 : 0;
			$output['display']['show_customer_location'] = isset( $input['display']['show_customer_location'] ) ? 1 : 0;
			$output['display']['show_quantity']        = isset( $input['display']['show_quantity'] ) ? 1 : 0;
			$output['display']['show_purchase_time']   = isset( $input['display']['show_purchase_time'] ) ? 1 : 0;
			$output['display']['show_verified_badge']  = isset( $input['display']['show_verified_badge'] ) ? 1 : 0;
			$output['display']['custom_notification_template'] = sanitize_textarea_field( $input['display']['custom_notification_template'] );
		}

		// 3. Privacy Settings.
		if ( isset( $input['privacy'] ) ) {
			$output['privacy']['anonymous_mode']      = isset( $input['privacy']['anonymous_mode'] ) ? 1 : 0;
			$output['privacy']['hide_customer_names'] = isset( $input['privacy']['hide_customer_names'] ) ? 1 : 0;
			$output['privacy']['hide_locations']      = isset( $input['privacy']['hide_locations'] ) ? 1 : 0;
			$output['privacy']['hide_quantity']       = isset( $input['privacy']['hide_quantity'] ) ? 1 : 0;
			$output['privacy']['hide_purchase_time']  = isset( $input['privacy']['hide_purchase_time'] ) ? 1 : 0;
			$output['privacy']['gdpr_mode']           = isset( $input['privacy']['gdpr_mode'] ) ? 1 : 0;
			$output['privacy']['auto_name_anonymize'] = isset( $input['privacy']['auto_name_anonymize'] ) ? 1 : 0;

			if ( isset( $input['privacy']['customer_name_mode'] ) ) {
				$output['privacy']['customer_name_mode'] = sanitize_text_field( $input['privacy']['customer_name_mode'] );
			}
		}

		// 4. Filters Settings.
		if ( isset( $input['filters'] ) ) {
			$output['filters']['exclude_out_of_stock'] = isset( $input['filters']['exclude_out_of_stock'] ) ? 1 : 0;
			$output['filters']['exclude_virtual']      = isset( $input['filters']['exclude_virtual'] ) ? 1 : 0;
			$output['filters']['exclude_downloadable'] = isset( $input['filters']['exclude_downloadable'] ) ? 1 : 0;

			$output['filters']['exclude_products']     = sanitize_text_field( $input['filters']['exclude_products'] );
			$output['filters']['exclude_categories']   = sanitize_text_field( $input['filters']['exclude_categories'] );
			$output['filters']['exclude_tags']         = sanitize_text_field( $input['filters']['exclude_tags'] );
			$output['filters']['exclude_brands']       = sanitize_text_field( $input['filters']['exclude_brands'] );
		}

		// 5. Orders Settings.
		if ( isset( $input['orders'] ) ) {
			$output['orders']['ignore_refunded_orders']   = isset( $input['orders']['ignore_refunded_orders'] ) ? 1 : 0;
			$output['orders']['ignore_failed_orders']     = isset( $input['orders']['ignore_failed_orders'] ) ? 1 : 0;
			$output['orders']['ignore_cancelled_orders']  = isset( $input['orders']['ignore_cancelled_orders'] ) ? 1 : 0;
			$output['orders']['minimum_completed_orders'] = absint( $input['orders']['minimum_completed_orders'] );

			$statuses = $input['orders']['order_status_filter'] ?? [];
			$output['orders']['order_status_filter'] = array_map( 'sanitize_text_field', (array) $statuses );
		}

		// 6. Appearance Settings.
		if ( isset( $input['appearance'] ) ) {
			$output['appearance']['background_color']   = sanitize_hex_color( $input['appearance']['background_color'] );
			$output['appearance']['text_color']         = sanitize_hex_color( $input['appearance']['text_color'] );
			$output['appearance']['accent_color']       = sanitize_hex_color( $input['appearance']['accent_color'] );
			$output['appearance']['border_radius']      = sanitize_text_field( $input['appearance']['border_radius'] );
			$output['appearance']['border']             = sanitize_text_field( $input['appearance']['border'] );
			$output['appearance']['shadow']             = sanitize_text_field( $input['appearance']['shadow'] );
			$output['appearance']['padding']            = sanitize_text_field( $input['appearance']['padding'] );
			$output['appearance']['spacing']            = sanitize_text_field( $input['appearance']['spacing'] );
			$output['appearance']['font_family']        = sanitize_text_field( $input['appearance']['font_family'] );
			$output['appearance']['font_size']          = sanitize_text_field( $input['appearance']['font_size'] );
			$output['appearance']['notification_width'] = sanitize_text_field( $input['appearance']['notification_width'] );
			$output['appearance']['image_size']         = sanitize_text_field( $input['appearance']['image_size'] );
			$output['appearance']['animation_type']     = sanitize_text_field( $input['appearance']['animation_type'] );
			$output['appearance']['custom_css']         = wp_strip_all_tags( $input['appearance']['custom_css'] );
		}

		// Flush transits cache on settings save.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wcpn_%' OR option_name LIKE '_transient_timeout_wcpn_%'" );

		return $output;
	}

	/**
	 * Render the premium admin settings dashboard layout.
	 */
	public function render_settings_page() {
		$settings = get_option( 'wcpn_settings' );
		?>
		<div class="wrap wcpn-admin-wrap">
			<div class="wcpn-dashboard-header">
				<div class="wcpn-brand">
					<h1><?php esc_html_e( 'Purchase Notifications', 'woocommerce-purchase-notifications' ); ?></h1>
					<span class="wcpn-version">v<?php echo esc_html( WCPN_VERSION ); ?></span>
				</div>
				<p class="wcpn-description"><?php esc_html_e( 'Display real-time recent order notifications to improve conversions using authentic shop data.', 'woocommerce-purchase-notifications' ); ?></p>
			</div>

			<form method="post" action="options.php" class="wcpn-settings-form">
				<?php settings_fields( 'wcpn_settings_group' ); ?>
				
				<div class="wcpn-dashboard-layout">
					<!-- Sidebar Tabs Navigation -->
					<div class="wcpn-sidebar">
						<ul class="wcpn-tabs">
							<li class="wcpn-tab-item active" data-tab="general">
								<span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'General Settings', 'woocommerce-purchase-notifications' ); ?>
							</li>
							<li class="wcpn-tab-item" data-tab="display">
								<span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Display Rules', 'woocommerce-purchase-notifications' ); ?>
							</li>
							<li class="wcpn-tab-item" data-tab="privacy">
								<span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Privacy & GDPR', 'woocommerce-purchase-notifications' ); ?>
							</li>
							<li class="wcpn-tab-item" data-tab="filters">
								<span class="dashicons dashicons-filter"></span> <?php esc_html_e( 'Product Filters', 'woocommerce-purchase-notifications' ); ?>
							</li>
							<li class="wcpn-tab-item" data-tab="orders">
								<span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'Order Filters', 'woocommerce-purchase-notifications' ); ?>
							</li>
							<li class="wcpn-tab-item" data-tab="appearance">
								<span class="dashicons dashicons-art"></span> <?php esc_html_e( 'Appearance', 'woocommerce-purchase-notifications' ); ?>
							</li>
						</ul>
						
						<div class="wcpn-actions">
							<?php submit_button( __( 'Save Settings', 'woocommerce-purchase-notifications' ), 'primary wcpn-submit-btn' ); ?>
						</div>
					</div>

					<!-- Central Options Area -->
					<div class="wcpn-content">
						
						<!-- TAB 1: GENERAL -->
						<div class="wcpn-tab-content active" id="tab-general">
							<div class="wcpn-card">
								<h2><?php esc_html_e( 'Core Configuration', 'woocommerce-purchase-notifications' ); ?></h2>
								
								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Enable Plugin', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<label class="wcpn-switch">
											<input type="checkbox" name="wcpn_settings[general][enable_plugin]" value="1" <?php checked( $settings['general']['enable_plugin'], 1 ); ?>>
											<span class="wcpn-slider"></span>
										</label>
										<span class="description"><?php esc_html_e( 'Activate purchase notifications across product pages.', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Device Compatibility', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper wcpn-checkbox-grid">
										<label>
											<input type="checkbox" name="wcpn_settings[general][enable_desktop]" value="1" <?php checked( $settings['general']['enable_desktop'], 1 ); ?>>
											<?php esc_html_e( 'Desktop', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[general][enable_tablet]" value="1" <?php checked( $settings['general']['enable_tablet'], 1 ); ?>>
											<?php esc_html_e( 'Tablet', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[general][enable_mobile]" value="1" <?php checked( $settings['general']['enable_mobile'], 1 ); ?>>
											<?php esc_html_e( 'Mobile', 'woocommerce-purchase-notifications' ); ?>
										</label>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Notification Position', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<select name="wcpn_settings[general][notification_position]">
											<?php
											$positions = [ 'Bottom Left', 'Bottom Right', 'Top Left', 'Top Right' ];
											foreach ( $positions as $pos ) {
												echo '<option value="' . esc_attr( $pos ) . '" ' . selected( $settings['general']['notification_position'], $pos, false ) . '>' . esc_html( $pos ) . '</option>';
											}
											?>
										</select>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Display Delay', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="number" class="small-text" name="wcpn_settings[general][display_delay]" value="<?php echo esc_attr( $settings['general']['display_delay'] ); ?>" min="0">
										<span class="description"><?php esc_html_e( 'Seconds to wait before showing the first notification card.', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Rotation Interval', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="number" class="small-text" name="wcpn_settings[general][rotation_interval]" value="<?php echo esc_attr( $settings['general']['rotation_interval'] ); ?>" min="1">
										<span class="description"><?php esc_html_e( 'Seconds to show each notification card before shifting to the next.', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Animation Speed', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="number" class="small-text" name="wcpn_settings[general][animation_speed]" value="<?php echo esc_attr( $settings['general']['animation_speed'] ); ?>" min="100"> ms
										<span class="description"><?php esc_html_e( 'Transition duration for entry and exit animations (default 500ms).', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Maximum Notifications', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="number" class="small-text" name="wcpn_settings[general][maximum_notifications]" value="<?php echo esc_attr( $settings['general']['maximum_notifications'] ); ?>" min="1">
										<span class="description"><?php esc_html_e( 'Maximum purchase cards to display per single product load.', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Maximum Purchase Age', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="number" class="small-text" name="wcpn_settings[general][maximum_purchase_age]" value="<?php echo esc_attr( $settings['general']['maximum_purchase_age'] ); ?>" min="0">
										<span class="description"><?php esc_html_e( 'Ignore orders older than this amount of days (use 0 for no age restrictions).', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Interactive Behaviors', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper wcpn-checkbox-list">
										<label>
											<input type="checkbox" name="wcpn_settings[general][pause_on_hover]" value="1" <?php checked( $settings['general']['pause_on_hover'], 1 ); ?>>
											<?php esc_html_e( 'Pause display interval when customer hovers over notification card', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[general][dismiss_notifications]" value="1" <?php checked( $settings['general']['dismiss_notifications'], 1 ); ?>>
											<?php esc_html_e( 'Allow customer to dismiss notification list (respects dismissals during browser session)', 'woocommerce-purchase-notifications' ); ?>
										</label>
									</div>
								</div>
							</div>
						</div>

						<!-- TAB 2: DISPLAY FIELDS -->
						<div class="wcpn-tab-content" id="tab-display">
							<div class="wcpn-card">
								<h2><?php esc_html_e( 'Visible Fields & Custom Templates', 'woocommerce-purchase-notifications' ); ?></h2>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Card Content Fields', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper wcpn-checkbox-list">
										<label>
											<input type="checkbox" name="wcpn_settings[display][show_product_image]" value="1" <?php checked( $settings['display']['show_product_image'], 1 ); ?>>
											<?php esc_html_e( 'Show Product Thumbnail', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[display][show_customer_name]" value="1" <?php checked( $settings['display']['show_customer_name'], 1 ); ?>>
											<?php esc_html_e( 'Show Customer Name', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[display][show_customer_location]" value="1" <?php checked( $settings['display']['show_customer_location'], 1 ); ?>>
											<?php esc_html_e( 'Show Customer Location', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[display][show_quantity]" value="1" <?php checked( $settings['display']['show_quantity'], 1 ); ?>>
											<?php esc_html_e( 'Show Order Item Quantity', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[display][show_purchase_time]" value="1" <?php checked( $settings['display']['show_purchase_time'], 1 ); ?>>
											<?php esc_html_e( 'Show Relative Time Ago', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[display][show_verified_badge]" value="1" <?php checked( $settings['display']['show_verified_badge'], 1 ); ?>>
											<?php esc_html_e( 'Show Verified Purchase Badge', 'woocommerce-purchase-notifications' ); ?>
										</label>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Notification Template', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<textarea name="wcpn_settings[display][custom_notification_template]" rows="3" class="large-text"><?php echo esc_textarea( $settings['display']['custom_notification_template'] ); ?></textarea>
										<span class="description">
											<?php esc_html_e( 'Customize notification wording. Placeholders: ', 'woocommerce-purchase-notifications' ); ?><br>
											<code>{customer_name}</code>, <code>{customer_location}</code>, <code>{quantity}</code>, <code>{product_name}</code>, <code>{time_ago}</code>
										</span>
									</div>
								</div>
							</div>
						</div>

						<!-- TAB 3: PRIVACY & GDPR -->
						<div class="wcpn-tab-content" id="tab-privacy">
							<div class="wcpn-card">
								<h2><?php esc_html_e( 'GDPR Compliance & Data Obfuscation', 'woocommerce-purchase-notifications' ); ?></h2>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'GDPR Anonymization Mode', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<label class="wcpn-switch">
											<input type="checkbox" name="wcpn_settings[privacy][gdpr_mode]" value="1" <?php checked( $settings['privacy']['gdpr_mode'], 1 ); ?>>
											<span class="wcpn-slider"></span>
										</label>
										<span class="description"><?php esc_html_e( 'Strict GDPR compliance: all name anonymization and location processing is computed strictly server-side. No raw customer billing parameters are ever sent to the browser DOM.', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Customer Name Privacy', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<select name="wcpn_settings[privacy][customer_name_mode]" id="wcpn-customer-name-mode">
											<?php
											$modes = [ 'Full First Name', 'First Name + Initial', 'Initial Only', 'Anonymous', 'Hidden' ];
											foreach ( $modes as $mode ) {
												echo '<option value="' . esc_attr( $mode ) . '" ' . selected( $settings['privacy']['customer_name_mode'], $mode, false ) . '>' . esc_html( $mode ) . '</option>';
											}
											?>
										</select>
										<span class="description"><?php esc_html_e( 'Choose how customer names are presented to website visitors.', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Location Settings', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<select name="wcpn_settings[notification][customer_location_source]">
											<?php
											$sources = [ 'Billing City', 'Shipping City' ];
											foreach ( $sources as $src ) {
												echo '<option value="' . esc_attr( $src ) . '" ' . selected( $settings['notification']['customer_location_source'], $src, false ) . '>' . esc_html( $src ) . '</option>';
											}
											?>
										</select>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Location Fallback text', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" name="wcpn_settings[notification][customer_location_fallback]" value="<?php echo esc_attr( $settings['notification']['customer_location_fallback'] ?? __( 'Unknown Location', 'woocommerce-purchase-notifications' ) ); ?>">
										<span class="description"><?php esc_html_e( 'Placeholder used if the customer location is missing.', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Global Privacy Overrides', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper wcpn-checkbox-list">
										<label>
											<input type="checkbox" name="wcpn_settings[privacy][anonymous_mode]" value="1" <?php checked( $settings['privacy']['anonymous_mode'], 1 ); ?>>
											<?php esc_html_e( 'Anonymous Mode (Force "Someone" for all customer names)', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[privacy][hide_customer_names]" value="1" <?php checked( $settings['privacy']['hide_customer_names'], 1 ); ?>>
											<?php esc_html_e( 'Hide Customer Names entirely', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[privacy][hide_locations]" value="1" <?php checked( $settings['privacy']['hide_locations'], 1 ); ?>>
											<?php esc_html_e( 'Hide Locations entirely', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[privacy][hide_quantity]" value="1" <?php checked( $settings['privacy']['hide_quantity'], 1 ); ?>>
											<?php esc_html_e( 'Hide Purchase Quantities', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[privacy][hide_purchase_time]" value="1" <?php checked( $settings['privacy']['hide_purchase_time'], 1 ); ?>>
											<?php esc_html_e( 'Hide Purchase Relative Time', 'woocommerce-purchase-notifications' ); ?>
										</label>
									</div>
								</div>
							</div>
						</div>

						<!-- TAB 4: PRODUCT FILTERS -->
						<div class="wcpn-tab-content" id="tab-filters">
							<div class="wcpn-card">
								<h2><?php esc_html_e( 'Product Exclusions', 'woocommerce-purchase-notifications' ); ?></h2>
								<p class="section-desc"><?php esc_html_e( 'Configure rules to block purchase popups on specific product pages.', 'woocommerce-purchase-notifications' ); ?></p>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Exclude Product IDs', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="regular-text" name="wcpn_settings[filters][exclude_products]" value="<?php echo esc_attr( $settings['filters']['exclude_products'] ); ?>">
										<span class="description"><?php esc_html_e( 'Comma-separated list of WC Product IDs (e.g. 102, 154).', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Exclude Categories', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="regular-text" name="wcpn_settings[filters][exclude_categories]" value="<?php echo esc_attr( $settings['filters']['exclude_categories'] ); ?>">
										<span class="description"><?php esc_html_e( 'Comma-separated list of category slugs or IDs (e.g. shoes, shirts).', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Exclude Product Tags', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="regular-text" name="wcpn_settings[filters][exclude_tags]" value="<?php echo esc_attr( $settings['filters']['exclude_tags'] ); ?>">
										<span class="description"><?php esc_html_e( 'Comma-separated list of tag slugs or IDs.', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Exclude Brands', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="regular-text" name="wcpn_settings[filters][exclude_brands]" value="<?php echo esc_attr( $settings['filters']['exclude_brands'] ); ?>">
										<span class="description"><?php esc_html_e( 'Comma-separated list of brand slugs or IDs.', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Product Type Exclusions', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper wcpn-checkbox-list">
										<label>
											<input type="checkbox" name="wcpn_settings[filters][exclude_out_of_stock]" value="1" <?php checked( $settings['filters']['exclude_out_of_stock'], 1 ); ?>>
											<?php esc_html_e( 'Exclude Out of Stock Products', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[filters][exclude_virtual]" value="1" <?php checked( $settings['filters']['exclude_virtual'], 1 ); ?>>
											<?php esc_html_e( 'Exclude Virtual Products', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[filters][exclude_downloadable]" value="1" <?php checked( $settings['filters']['exclude_downloadable'], 1 ); ?>>
											<?php esc_html_e( 'Exclude Downloadable Products', 'woocommerce-purchase-notifications' ); ?>
										</label>
									</div>
								</div>
							</div>
						</div>

						<!-- TAB 5: ORDER FILTERS -->
						<div class="wcpn-tab-content" id="tab-orders">
							<div class="wcpn-card">
								<h2><?php esc_html_e( 'Order Eligibility Filters', 'woocommerce-purchase-notifications' ); ?></h2>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Eligible Order Statuses', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper wcpn-checkbox-list">
										<?php
										$wc_statuses = wc_get_order_statuses();
										$selected_statuses = $settings['orders']['order_status_filter'] ?? [];
										foreach ( $wc_statuses as $status_key => $status_label ) {
											// Strip wc- prefix for saving.
											$clean_key = ltrim( $status_key, 'wc-' );
											$is_checked = in_array( $clean_key, $selected_statuses, true );
											echo '<label><input type="checkbox" name="wcpn_settings[orders][order_status_filter][]" value="' . esc_attr( $clean_key ) . '" ' . checked( $is_checked, true, false ) . '> ' . esc_html( $status_label ) . '</label>';
										}
										?>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Minimum Completed Orders', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="number" class="small-text" name="wcpn_settings[orders][minimum_completed_orders]" value="<?php echo esc_attr( $settings['orders']['minimum_completed_orders'] ); ?>" min="0">
										<span class="description"><?php esc_html_e( 'Only display purchase notifications if the store has at least X total orders.', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Safety Filter Overrides', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper wcpn-checkbox-list">
										<label>
											<input type="checkbox" name="wcpn_settings[orders][ignore_refunded_orders]" value="1" <?php checked( $settings['orders']['ignore_refunded_orders'], 1 ); ?>>
											<?php esc_html_e( 'Ignore Refunded Orders (Recommended)', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[orders][ignore_failed_orders]" value="1" <?php checked( $settings['orders']['ignore_failed_orders'], 1 ); ?>>
											<?php esc_html_e( 'Ignore Failed Orders (Recommended)', 'woocommerce-purchase-notifications' ); ?>
										</label>
										<label>
											<input type="checkbox" name="wcpn_settings[orders][ignore_cancelled_orders]" value="1" <?php checked( $settings['orders']['ignore_cancelled_orders'], 1 ); ?>>
											<?php esc_html_e( 'Ignore Cancelled Orders (Recommended)', 'woocommerce-purchase-notifications' ); ?>
										</label>
									</div>
								</div>
							</div>
						</div>

						<!-- TAB 6: APPEARANCE -->
						<div class="wcpn-tab-content" id="tab-appearance">
							<div class="wcpn-card">
								<h2><?php esc_html_e( 'Appearance & Styles Customizer', 'woocommerce-purchase-notifications' ); ?></h2>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Animation Type', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<select name="wcpn_settings[appearance][animation_type]" id="wcpn-setting-animation-type">
											<?php
											$animations = [ 'Fade', 'Slide Up', 'Slide Down', 'Slide Left', 'Slide Right', 'Scale', 'Zoom', 'Bounce' ];
											foreach ( $animations as $anim ) {
												echo '<option value="' . esc_attr( $anim ) . '" ' . selected( $settings['appearance']['animation_type'], $anim, false ) . '>' . esc_html( $anim ) . '</option>';
											}
											?>
										</select>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Background Color', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="wcpn-color-picker" name="wcpn_settings[appearance][background_color]" value="<?php echo esc_attr( $settings['appearance']['background_color'] ); ?>">
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Text Color', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="wcpn-color-picker" name="wcpn_settings[appearance][text_color]" value="<?php echo esc_attr( $settings['appearance']['text_color'] ); ?>">
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Accent / Link Color', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="wcpn-color-picker" name="wcpn_settings[appearance][accent_color]" value="<?php echo esc_attr( $settings['appearance']['accent_color'] ); ?>">
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Border Radius', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="regular-text small-text" name="wcpn_settings[appearance][border_radius]" id="wcpn-setting-border-radius" value="<?php echo esc_attr( $settings['appearance']['border_radius'] ); ?>">
										<span class="description"><?php esc_html_e( 'e.g. 12px or 50%', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Border Style', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="regular-text" name="wcpn_settings[appearance][border]" id="wcpn-setting-border" value="<?php echo esc_attr( $settings['appearance']['border'] ); ?>">
										<span class="description"><?php esc_html_e( 'e.g. 1px solid rgba(0,0,0,0.08) or none', 'woocommerce-purchase-notifications' ); ?></span>
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Shadow', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="regular-text" name="wcpn_settings[appearance][shadow]" id="wcpn-setting-shadow" value="<?php echo esc_attr( $settings['appearance']['shadow'] ); ?>">
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Padding', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="regular-text small-text" name="wcpn_settings[appearance][padding]" id="wcpn-setting-padding" value="<?php echo esc_attr( $settings['appearance']['padding'] ); ?>">
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Screen Margin Spacing', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="regular-text small-text" name="wcpn_settings[appearance][spacing]" id="wcpn-setting-spacing" value="<?php echo esc_attr( $settings['appearance']['spacing'] ); ?>">
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Typography Font Family', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="regular-text" name="wcpn_settings[appearance][font_family]" id="wcpn-setting-font" value="<?php echo esc_attr( $settings['appearance']['font_family'] ); ?>">
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Font Size', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="regular-text small-text" name="wcpn_settings[appearance][font_size]" id="wcpn-setting-font-size" value="<?php echo esc_attr( $settings['appearance']['font_size'] ); ?>">
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Notification Width', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="regular-text small-text" name="wcpn_settings[appearance][notification_width]" id="wcpn-setting-width" value="<?php echo esc_attr( $settings['appearance']['notification_width'] ); ?>">
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Thumbnail Size', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<input type="text" class="regular-text small-text" name="wcpn_settings[appearance][image_size]" id="wcpn-setting-img-size" value="<?php echo esc_attr( $settings['appearance']['image_size'] ); ?>">
									</div>
								</div>

								<div class="wcpn-field-row">
									<label class="wcpn-label"><?php esc_html_e( 'Custom CSS', 'woocommerce-purchase-notifications' ); ?></label>
									<div class="wcpn-input-wrapper">
										<textarea name="wcpn_settings[appearance][custom_css]" rows="5" class="large-text"><?php echo esc_textarea( $settings['appearance']['custom_css'] ); ?></textarea>
									</div>
								</div>
							</div>
						</div>

					</div>

					<!-- Right Column: Interactive Live Notification Preview -->
					<div class="wcpn-preview-column">
						<div class="wcpn-sticky-preview-wrapper">
							<h3><?php esc_html_e( 'Live Cards Preview', 'woocommerce-purchase-notifications' ); ?></h3>
							<p class="preview-desc"><?php esc_html_e( 'Shows styling customizations in real-time as configurations change.', 'woocommerce-purchase-notifications' ); ?></p>
							
							<div class="wcpn-preview-stage">
								<!-- The preview card wrapper -->
								<div class="wcpn-card-preview" id="wcpn-card-preview-element">
									<div class="wcpn-preview-image-wrapper">
										<img src="<?php echo esc_url( wc_placeholder_img_src( 'thumbnail' ) ); ?>" class="wcpn-preview-image" alt="Product thumbnail placeholder">
									</div>
									<div class="wcpn-preview-body">
										<p class="wcpn-preview-text">
											<span class="wcpn-preview-name-span">John D.</span> 
											from 
											<span class="wcpn-preview-loc-span">Chicago</span> 
											purchased 
											<span class="wcpn-preview-qty-span">1x</span> 
											<span class="wcpn-preview-prod-span">Premium Leather Wallet</span> 
											<span class="wcpn-preview-time-span">5 minutes ago</span>.
										</p>
										<div class="wcpn-preview-verified-badge" id="wcpn-preview-verified-element">
											<svg class="wcpn-verified-svg" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
												<polyline points="20 6 9 17 4 12"></polyline>
											</svg>
											<span><?php esc_html_e( 'Verified Purchase', 'woocommerce-purchase-notifications' ); ?></span>
										</div>
									</div>
									<button type="button" class="wcpn-preview-close" aria-label="Dismiss">&times;</button>
								</div>
							</div>
							
							<div class="wcpn-preview-controls">
								<button type="button" class="button button-secondary" id="wcpn-btn-animate-preview"><?php esc_html_e( 'Test Animation', 'woocommerce-purchase-notifications' ); ?></button>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
		<?php
	}
}
