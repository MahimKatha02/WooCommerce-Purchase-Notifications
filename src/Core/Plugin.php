<?php
namespace WooCommercePurchaseNotifications\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Bootstrap Class.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Settings instance.
	 *
	 * @var \WooCommercePurchaseNotifications\Admin\Settings
	 */
	public \WooCommercePurchaseNotifications\Admin\Settings $settings;

	/**
	 * Admin Assets instance.
	 *
	 * @var \WooCommercePurchaseNotifications\Admin\Assets
	 */
	public \WooCommercePurchaseNotifications\Admin\Assets $assets;

	/**
	 * Frontend Display instance.
	 *
	 * @var \WooCommercePurchaseNotifications\Frontend\Display
	 */
	public \WooCommercePurchaseNotifications\Frontend\Display $display;

	/**
	 * Frontend Ajax instance.
	 *
	 * @var \WooCommercePurchaseNotifications\Frontend\Ajax
	 */
	public \WooCommercePurchaseNotifications\Frontend\Ajax $ajax;

	/**
	 * Get class instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Class constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin components.
	 */
	private function init() {
		// Load translations.
		add_action( 'init', [ $this, 'load_textdomain' ] );

		// Instantiate modules.
		$this->settings = new \WooCommercePurchaseNotifications\Admin\Settings();
		$this->assets   = new \WooCommercePurchaseNotifications\Admin\Assets();
		$this->display  = new \WooCommercePurchaseNotifications\Frontend\Display();
		$this->ajax     = new \WooCommercePurchaseNotifications\Frontend\Ajax();
	}

	/**
	 * Load translation files.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			WCPN_TEXT_DOMAIN,
			false,
			dirname( plugin_basename( WCPN_PLUGIN_FILE ) ) . '/languages'
		);
	}
}
