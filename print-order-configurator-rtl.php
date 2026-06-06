<?php
/**
 * Plugin Name: Print Order Configurator RTL for WooCommerce
 * Plugin URI: https://github.com/mido1983/print-order-configurator-rtl
 * Description: RTL-first structured print order workflow configurator for WooCommerce.
 * Version: 0.1.5
 * Author: Print Order Configurator RTL Contributors
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: print-order-configurator-rtl
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.5
 * WC requires at least: 8.0
 * WC tested up to: 10.0
 *
 * @package PrintOrderConfiguratorRTL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'POC_RTL_VERSION', '0.1.5' );
define( 'POC_RTL_FILE', __FILE__ );
define( 'POC_RTL_PATH', plugin_dir_path( __FILE__ ) );
define( 'POC_RTL_URL', plugin_dir_url( __FILE__ ) );
define( 'POC_RTL_BASENAME', plugin_basename( __FILE__ ) );

define( 'POCRTL_VERSION', POC_RTL_VERSION );
define( 'POCRTL_PLUGIN_FILE', __FILE__ );
define( 'POCRTL_GITHUB_REPO', 'mido1983/print-order-configurator-rtl' );
define( 'POCRTL_GITHUB_API_URL', 'https://api.github.com/repos/mido1983/print-order-configurator-rtl/releases/latest' );

require_once POC_RTL_PATH . 'includes/helpers.php';
require_once POC_RTL_PATH . 'includes/class-plugin.php';

add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	}
);

register_activation_hook( __FILE__, array( 'POC_RTL_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'POC_RTL_Plugin', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		POC_RTL_Plugin::instance()->init();
	}
);
