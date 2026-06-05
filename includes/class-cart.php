<?php
/**
 * Cart metadata handling.
 *
 * @package PrintOrderConfiguratorRTL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart service.
 */
final class POC_RTL_Cart {
	public const CART_KEY = 'poc_rtl_configurator';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_cart_item_data' ), 10, 2 );
	}

	/**
	 * Add configurator data to cart item.
	 *
	 * @param array<string, mixed> $cart_item_data Cart item data.
	 * @param int                  $product_id Product ID.
	 * @param int                  $variation_id Variation ID.
	 * @return array<string, mixed>
	 */
	public function add_cart_item_data( array $cart_item_data, int $product_id, int $variation_id ): array {
		unset( $variation_id );

		if ( ! POC_RTL_Product_Settings::is_enabled( $product_id ) || ! isset( $_POST['poc_rtl'] ) || ! is_array( $_POST['poc_rtl'] ) ) {
			return $cart_item_data;
		}

		$cart_item_data[ self::CART_KEY ] = $this->sanitize_payload( wp_unslash( $_POST['poc_rtl'] ) );
		$cart_item_data['poc_rtl_unique'] = md5( wp_json_encode( $cart_item_data[ self::CART_KEY ] ) . microtime( true ) );

		return $cart_item_data;
	}

	/**
	 * Display configurator data in cart and checkout.
	 *
	 * @param array<int, array<string, mixed>> $item_data Display rows.
	 * @param array<string, mixed>             $cart_item Cart item.
	 * @return array<int, array<string, mixed>>
	 */
	public function display_cart_item_data( array $item_data, array $cart_item ): array {
		if ( empty( $cart_item[ self::CART_KEY ] ) || ! is_array( $cart_item[ self::CART_KEY ] ) ) {
			return $item_data;
		}

		$data = $cart_item[ self::CART_KEY ];

		$item_data[] = array(
			'key'   => __( 'מצב עיצוב', 'print-order-configurator-rtl' ),
			'value' => 'need_design' === ( $data['design_mode'] ?? '' )
				? __( 'צריך עיצוב מהדפוס', 'print-order-configurator-rtl' )
				: __( 'יש עיצוב מוכן', 'print-order-configurator-rtl' ),
		);

		if ( ! empty( $data['options'] ) && is_array( $data['options'] ) ) {
			foreach ( $this->option_labels() as $field => $label ) {
				if ( empty( $data['options'][ $field ] ) ) {
					continue;
				}

				$item_data[] = array(
					'key'   => $label,
					'value' => wc_clean( (string) $data['options'][ $field ] ),
				);
			}
		}

		if ( ! empty( $data['production_notes'] ) ) {
			$item_data[] = array(
				'key'   => __( 'הערות להפקה', 'print-order-configurator-rtl' ),
				'value' => nl2br( esc_html( (string) $data['production_notes'] ) ),
			);
		}

		if ( 'need_design' === ( $data['design_mode'] ?? '' ) && ! empty( $data['brief'] ) && is_array( $data['brief'] ) ) {
			foreach ( $this->brief_labels() as $field => $label ) {
				if ( empty( $data['brief'][ $field ] ) ) {
					continue;
				}

				$item_data[] = array(
					'key'   => $label,
					'value' => nl2br( esc_html( (string) $data['brief'][ $field ] ) ),
				);
			}
		}

		return $item_data;
	}

	/**
	 * Sanitize posted configurator data.
	 *
	 * @param mixed $raw Raw payload.
	 * @return array<string, mixed>
	 */
	private function sanitize_payload( mixed $raw ): array {
		$payload = is_array( $raw ) ? $raw : array();
		$options = isset( $payload['options'] ) && is_array( $payload['options'] ) ? $payload['options'] : array();
		$brief   = isset( $payload['brief'] ) && is_array( $payload['brief'] ) ? $payload['brief'] : array();

		$data = array(
			'design_mode'      => isset( $payload['design_mode'] ) ? sanitize_key( (string) $payload['design_mode'] ) : 'ready',
			'options'          => array(),
			'production_notes' => isset( $payload['production_notes'] ) ? sanitize_textarea_field( (string) $payload['production_notes'] ) : '',
			'brief'            => array(),
		);

		foreach ( array_keys( $this->option_labels() ) as $field ) {
			$data['options'][ $field ] = isset( $options[ $field ] ) ? sanitize_text_field( (string) $options[ $field ] ) : '';
		}

		foreach ( array_keys( $this->brief_labels() ) as $field ) {
			$data['brief'][ $field ] = isset( $brief[ $field ] ) ? sanitize_textarea_field( (string) $brief[ $field ] ) : '';
		}

		return $data;
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
