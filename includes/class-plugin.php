<?php
/**
 * Main plugin bootstrap.
 *
 * @package PrintOrderConfiguratorRTL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
final class POC_RTL_Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Activation callback.
	 */
	public static function activate(): void {
		if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
			deactivate_plugins( POC_RTL_BASENAME );
			wp_die(
				esc_html__( 'Print Order Configurator RTL requires PHP 8.1 or newer.', 'print-order-configurator-rtl' ),
				esc_html__( 'Plugin activation failed', 'print-order-configurator-rtl' ),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Deactivation callback.
	 */
	public static function deactivate(): void {
		// No runtime state to clear in the initial scaffold.
	}

	/**
	 * Initialize plugin.
	 */
	public function init(): void {
		$this->load_textdomain();

		if ( ! poc_rtl_is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'render_woocommerce_missing_notice' ) );
			return;
		}

		$this->includes();
		$this->register_services();
	}

	/**
	 * Load plugin classes.
	 */
	private function includes(): void {
		require_once POC_RTL_PATH . 'includes/class-product-settings.php';
	}

	/**
	 * Register service hooks.
	 */
	private function register_services(): void {
		( new POC_RTL_Product_Settings() )->init();
	}

	/**
	 * Load translations.
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain(
			'print-order-configurator-rtl',
			false,
			dirname( POC_RTL_BASENAME ) . '/languages'
		);
	}

	/**
	 * Render WooCommerce dependency notice.
	 */
	public function render_woocommerce_missing_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Print Order Configurator RTL requires WooCommerce to be installed and active.', 'print-order-configurator-rtl' )
		);
	}

	/**
	 * Prevent direct construction.
	 */
	private function __construct() {}
}
