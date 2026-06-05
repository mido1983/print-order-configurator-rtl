<?php
/**
 * Design service pricing.
 *
 * @package PrintOrderConfiguratorRTL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pricing service.
 */
final class POC_RTL_Pricing {
	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'snapshot_design_fee' ), 30, 3 );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_design_service_fee' ) );
	}

	/**
	 * Save design fee at add-to-cart time to avoid later product setting drift.
	 *
	 * @param array<string, mixed> $cart_item_data Cart item data.
	 * @param int                  $product_id Product ID.
	 * @param int                  $variation_id Variation ID.
	 * @return array<string, mixed>
	 */
	public function snapshot_design_fee( array $cart_item_data, int $product_id, int $variation_id ): array {
		unset( $variation_id );

		if ( empty( $cart_item_data[ POC_RTL_Cart::CART_KEY ] ) || ! is_array( $cart_item_data[ POC_RTL_Cart::CART_KEY ] ) ) {
			return $cart_item_data;
		}

		if ( 'need_design' !== ( $cart_item_data[ POC_RTL_Cart::CART_KEY ]['design_mode'] ?? '' ) ) {
			return $cart_item_data;
		}

		$config = POC_RTL_Product_Settings::get_product_config( $product_id );
		$fee    = ! empty( $config['design_service_fee'] ) ? (float) $config['design_service_fee'] : 0.0;

		$cart_item_data[ POC_RTL_Cart::CART_KEY ]['design_service_fee'] = max( 0, $fee );

		return $cart_item_data;
	}

	/**
	 * Add one aggregated design service fee to the cart.
	 *
	 * @param WC_Cart $cart Cart object.
	 */
	public function add_design_service_fee( WC_Cart $cart ): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		$total = 0.0;

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item[ POC_RTL_Cart::CART_KEY ] ) || ! is_array( $cart_item[ POC_RTL_Cart::CART_KEY ] ) ) {
				continue;
			}

			$data = $cart_item[ POC_RTL_Cart::CART_KEY ];

			if ( 'need_design' !== ( $data['design_mode'] ?? '' ) ) {
				continue;
			}

			$total += isset( $data['design_service_fee'] ) ? (float) $data['design_service_fee'] : 0.0;
		}

		if ( $total <= 0 ) {
			return;
		}

		$cart->add_fee( __( 'שירות עיצוב מהדפוס', 'print-order-configurator-rtl' ), $total, false );
	}
}
