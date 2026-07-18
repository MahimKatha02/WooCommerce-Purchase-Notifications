<?php
namespace AntigravityPurchaseNotifications\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend display class.
 */
class Display {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_footer', [ $this, 'render_container' ] );
	}

	/**
	 * Determine if notifications should display on the current page.
	 *
	 * @return bool
	 */
	public function should_display(): bool {
		$settings = get_option( 'wcpn_settings', [] );

		// 1. Check if plugin is enabled.
		$enabled = isset( $settings['general']['enable_plugin'] ) ? (bool) $settings['general']['enable_plugin'] : true;
		if ( ! $enabled ) {
			return false;
		}

		// 2. Check if we are in admin context.
		if ( is_admin() ) {
			return false;
		}

		// 3. Must be on single WooCommerce product page.
		if ( ! is_product() ) {
			return false;
		}

		// 4. Double check page exclusions (just to be safe against complex page builders).
		if ( is_cart() || is_checkout() || is_account_page() || is_search() || is_home() || is_front_page() ) {
			return false;
		}

		return true;
	}

	/**
	 * Enqueue frontend CSS and JavaScript assets.
	 */
	public function enqueue_assets() {
		if ( ! $this->should_display() ) {
			return;
		}

		// Enqueue Stylesheet.
		wp_enqueue_style(
			'wcpn-frontend',
			WCPN_PLUGIN_URL . 'assets/css/frontend.min.css',
			[],
			WCPN_VERSION
		);

		// Enqueue Script.
		wp_enqueue_script(
			'wcpn-frontend',
			WCPN_PLUGIN_URL . 'assets/js/frontend.min.js',
			[ 'jquery' ],
			WCPN_VERSION,
			true
		);

		// Prepare configuration to localize.
		$settings   = get_option( 'wcpn_settings', [] );
		$product_id = get_the_ID();

		// Convert human-readable settings values to standardized values for JS.
		$position = $settings['general']['notification_position'] ?? 'Bottom Left';
		$position = strtolower( str_replace( ' ', '-', $position ) );

		$animation = $settings['appearance']['animation_type'] ?? 'Slide Up';
		$animation = strtolower( str_replace( ' ', '-', $animation ) );

		$config = [
			'ajax_url'           => admin_url( 'admin-ajax.php' ),
			'nonce'              => wp_create_nonce( 'wcpn-frontend-nonce' ),
			'product_id'         => $product_id,
			'position'           => $position,
			'display_delay'      => ( isset( $settings['general']['display_delay'] ) ? absint( $settings['general']['display_delay'] ) : 5 ) * 1000,
			'rotation_interval'  => ( isset( $settings['general']['rotation_interval'] ) ? absint( $settings['general']['rotation_interval'] ) : 8 ) * 1000,
			'animation_duration' => isset( $settings['general']['animation_speed'] ) ? absint( $settings['general']['animation_speed'] ) : 500,
			'animation_type'     => $animation,
			'pause_on_hover'     => ! isset( $settings['general']['pause_on_hover'] ) || (bool) $settings['general']['pause_on_hover'],
			'dismissible'        => ! isset( $settings['general']['dismiss_notifications'] ) || (bool) $settings['general']['dismiss_notifications'],
			'enable_mobile'      => ! isset( $settings['general']['enable_mobile'] ) || (bool) $settings['general']['enable_mobile'],
			'enable_tablet'      => ! isset( $settings['general']['enable_tablet'] ) || (bool) $settings['general']['enable_tablet'],
			'enable_desktop'     => ! isset( $settings['general']['enable_desktop'] ) || (bool) $settings['general']['enable_desktop'],
		];

		wp_localize_script( 'wcpn-frontend', 'wcpn_config', $config );
	}

	/**
	 * Render notification container and dynamic CSS styles in footer.
	 */
	public function render_container() {
		if ( ! $this->should_display() ) {
			return;
		}

		$settings = get_option( 'wcpn_settings', [] );
		$position = $settings['general']['notification_position'] ?? 'Bottom Left';
		$position = strtolower( str_replace( ' ', '-', $position ) );

		// Render dynamic visual configuration.
		$this->render_dynamic_styles( $settings );

		// Output main container.
		echo '<div id="wcpn-notification-container" class="wcpn-position-' . esc_attr( $position ) . '" aria-live="polite" role="status"></div>';
	}

	/**
	 * Print dynamic CSS style block computed from Admin options.
	 *
	 * @param array $settings
	 */
	private function render_dynamic_styles( array $settings ) {
		$appearance = $settings['appearance'] ?? [];

		// Custom options or defaults.
		$bg_color       = $appearance['background_color'] ?? '#ffffff';
		$text_color     = $appearance['text_color'] ?? '#2c3e50';
		$accent_color   = $appearance['accent_color'] ?? '#3498db';
		$border_radius  = $appearance['border_radius'] ?? '12px';
		$border         = $appearance['border'] ?? '1px solid rgba(0, 0, 0, 0.06)';
		$shadow         = $appearance['shadow'] ?? '0 8px 30px rgba(0, 0, 0, 0.12)';
		$padding        = $appearance['padding'] ?? '14px 18px';
		$spacing        = $appearance['spacing'] ?? '24px';
		$font_family    = $appearance['font_family'] ?? 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
		$font_size      = $appearance['font_size'] ?? '14px';
		$notif_width    = $appearance['notification_width'] ?? '360px';
		$image_size     = $appearance['image_size'] ?? '60px';
		$custom_css     = $appearance['custom_css'] ?? '';

		$mobile_display  = ( ! isset( $settings['general']['enable_mobile'] ) || (bool) $settings['general']['enable_mobile'] ) ? 'flex' : 'none';
		$tablet_display  = ( ! isset( $settings['general']['enable_tablet'] ) || (bool) $settings['general']['enable_tablet'] ) ? 'flex' : 'none';
		$desktop_display = ( ! isset( $settings['general']['enable_desktop'] ) || (bool) $settings['general']['enable_desktop'] ) ? 'flex' : 'none';
		?>
		<style id="wcpn-dynamic-styles">
			:root {
				--wcpn-bg: <?php echo esc_html( $bg_color ); ?>;
				--wcpn-text: <?php echo esc_html( $text_color ); ?>;
				--wcpn-accent: <?php echo esc_html( $accent_color ); ?>;
				--wcpn-radius: <?php echo esc_html( $border_radius ); ?>;
				--wcpn-border: <?php echo esc_html( $border ); ?>;
				--wcpn-shadow: <?php echo esc_html( $shadow ); ?>;
				--wcpn-padding: <?php echo esc_html( $padding ); ?>;
				--wcpn-spacing: <?php echo esc_html( $spacing ); ?>;
				--wcpn-font: <?php echo esc_html( $font_family ); ?>;
				--wcpn-font-size: <?php echo esc_html( $font_size ); ?>;
				--wcpn-width: <?php echo esc_html( $notif_width ); ?>;
				--wcpn-img-size: <?php echo esc_html( $image_size ); ?>;
			}

			#wcpn-notification-container {
				bottom: var(--wcpn-spacing);
				left: var(--wcpn-spacing);
				z-index: 99999;
			}
			#wcpn-notification-container.wcpn-position-bottom-right {
				bottom: var(--wcpn-spacing);
				right: var(--wcpn-spacing);
				left: auto;
			}
			#wcpn-notification-container.wcpn-position-top-left {
				top: var(--wcpn-spacing);
				left: var(--wcpn-spacing);
				bottom: auto;
			}
			#wcpn-notification-container.wcpn-position-top-right {
				top: var(--wcpn-spacing);
				right: var(--wcpn-spacing);
				bottom: auto;
				left: auto;
			}

			/* Device displays */
			@media (max-width: 480px) {
				#wcpn-notification-container .wcpn-card {
					display: <?php echo esc_html( $mobile_display ); ?> !important;
				}
			}
			@media (min-width: 481px) and (max-width: 768px) {
				#wcpn-notification-container .wcpn-card {
					display: <?php echo esc_html( $tablet_display ); ?> !important;
				}
			}
			@media (min-width: 769px) {
				#wcpn-notification-container .wcpn-card {
					display: <?php echo esc_html( $desktop_display ); ?> !important;
				}
			}

			<?php echo wp_strip_all_tags( $custom_css ); ?>
		</style>
		<?php
	}
}
