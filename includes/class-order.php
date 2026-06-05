<?php
/**
 * Order metadata handling.
 *
 * @package PrintOrderConfiguratorRTL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order service.
 */
final class POC_RTL_Order {
	public const ORDER_ITEM_META = '_poc_rtl_configurator';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_order_item_meta' ), 10, 4 );
	}

	/**
	 * Copy configurator data from cart to order line item.
	 *
	 * @param WC_Order_Item_Product $item Order item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array<string, mixed>  $values Cart item values.
	 * @param WC_Order              $order Order object.
	 */
	public function add_order_item_meta( WC_Order_Item_Product $item, string $cart_item_key, array $values, WC_Order $order ): void {
		unset( $cart_item_key, $order );

		if ( empty( $values[ POC_RTL_Cart::CART_KEY ] ) || ! is_array( $values[ POC_RTL_Cart::CART_KEY ] ) ) {
			return;
		}

		$data = $values[ POC_RTL_Cart::CART_KEY ];

		$item->add_meta_data( self::ORDER_ITEM_META, $data, true );
		$item->add_meta_data(
			__( 'מצב עיצוב', 'print-order-configurator-rtl' ),
			'need_design' === ( $data['design_mode'] ?? '' )
				? __( 'צריך עיצוב מהדפוס', 'print-order-configurator-rtl' )
				: __( 'יש עיצוב מוכן', 'print-order-configurator-rtl' ),
			true
		);

		if ( ! empty( $data['options'] ) && is_array( $data['options'] ) ) {
			foreach ( $this->option_labels() as $field => $label ) {
				if ( empty( $data['options'][ $field ] ) ) {
					continue;
				}

				$item->add_meta_data( $label, wc_clean( (string) $data['options'][ $field ] ), true );
			}
		}

		if ( ! empty( $data['production_notes'] ) ) {
			$item->add_meta_data( __( 'הערות להפקה', 'print-order-configurator-rtl' ), wc_clean( (string) $data['production_notes'] ), true );
		}

		if ( 'need_design' === ( $data['design_mode'] ?? '' ) && ! empty( $data['brief'] ) && is_array( $data['brief'] ) ) {
			foreach ( $this->brief_labels() as $field => $label ) {
				if ( empty( $data['brief'][ $field ] ) ) {
					continue;
				}

				$item->add_meta_data( $label, wc_clean( (string) $data['brief'][ $field ] ), true );
			}
		}
	}

	/**
	 * Product option labels.
	 *
	 * @return array<string, string>
	 */
	private function option_labels(): array {
		return array(
			'sizes'             => __( 'גודל', 'print-order-configurator-rtl' ),
			'quantities'        => __( 'כמות', 'print-order-configurator-rtl' ),
			'paper_types'       => __( 'סוג נייר', 'print-order-configurator-rtl' ),
			'paper_weights'     => __( 'משקל נייר', 'print-order-configurator-rtl' ),
			'print_sides'       => __( 'צדדי הדפסה', 'print-order-configurator-rtl' ),
			'lamination'        => __( 'למינציה', 'print-order-configurator-rtl' ),
			'corners'           => __( 'פינות', 'print-order-configurator-rtl' ),
			'finishing_options' => __( 'גימור', 'print-order-configurator-rtl' ),
		);
	}

	/**
	 * Design brief labels.
	 *
	 * @return array<string, string>
	 */
	private function brief_labels(): array {
		return array(
			'business_name'     => __( 'שם העסק', 'print-order-configurator-rtl' ),
			'phone'             => __( 'טלפון', 'print-order-configurator-rtl' ),
			'email'             => __( 'אימייל', 'print-order-configurator-rtl' ),
			'address'           => __( 'כתובת', 'print-order-configurator-rtl' ),
			'preferred_colors'  => __( 'צבעים מועדפים', 'print-order-configurator-rtl' ),
			'style'             => __( 'סגנון', 'print-order-configurator-rtl' ),
			'design_text'       => __( 'טקסט לעיצוב', 'print-order-configurator-rtl' ),
			'references'        => __( 'רפרנסים והשראה', 'print-order-configurator-rtl' ),
			'notes'             => __( 'הערות נוספות', 'print-order-configurator-rtl' ),
		);
	}
}
