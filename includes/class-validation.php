<?php
/**
 * Add-to-cart validation.
 *
 * @package PrintOrderConfiguratorRTL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validation service.
 */
final class POC_RTL_Validation {
	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 10, 3 );
	}

	/**
	 * Validate configurator payload before adding to cart.
	 *
	 * @param bool $passed Existing validation state.
	 * @param int  $product_id Product ID.
	 * @param int  $quantity Quantity.
	 */
	public function validate_add_to_cart( bool $passed, int $product_id, int $quantity ): bool {
		unset( $quantity );

		if ( ! $passed || ! POC_RTL_Product_Settings::is_enabled( $product_id ) ) {
			return $passed;
		}

		if ( ! $this->has_valid_nonce() ) {
			wc_add_notice( __( 'אירעה שגיאה באימות הטופס. רעננו את העמוד ונסו שוב.', 'print-order-configurator-rtl' ), 'error' );
			return false;
		}

		$payload = $this->posted_payload();

		if ( empty( $payload ) ) {
			wc_add_notice( __( 'יש למלא את פרטי הזמנת ההדפסה.', 'print-order-configurator-rtl' ), 'error' );
			return false;
		}

		$config = POC_RTL_Product_Settings::get_product_config( $product_id );

		if ( ! $this->validate_options( $payload, $config ) ) {
			return false;
		}

		$design_mode = isset( $payload['design_mode'] ) ? sanitize_key( (string) $payload['design_mode'] ) : '';

		if ( ! in_array( $design_mode, array( 'ready', 'need_design' ), true ) ) {
			wc_add_notice( __( 'יש לבחור אם יש לכם עיצוב מוכן או שאתם צריכים עיצוב מהדפוס.', 'print-order-configurator-rtl' ), 'error' );
			return false;
		}

		if ( 'need_design' === $design_mode && ( ! POC_RTL_Settings::enabled( 'pocrtl_design_service_enabled_global' ) || empty( $config['design_service_enabled'] ) ) ) {
			wc_add_notice( __( 'שירות עיצוב אינו זמין למוצר הזה.', 'print-order-configurator-rtl' ), 'error' );
			return false;
		}

		if ( 'ready' === $design_mode && 0 === $this->count_uploaded_files( 'poc_rtl_ready_files' ) ) {
			wc_add_notice( __( 'בחרתם שיש לכם עיצוב מוכן, לכן יש להעלות לפחות קובץ אחד.', 'print-order-configurator-rtl' ), 'error' );
			return false;
		}

		if ( ! POC_RTL_Upload_Handler::validate_file_field( 'poc_rtl_ready_files', $config )
			|| ! POC_RTL_Upload_Handler::validate_file_field( 'poc_rtl_brief_files', $config )
		) {
			return false;
		}

		if ( 'need_design' === $design_mode && ! $this->validate_design_brief( $payload ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check nonce.
	 */
	private function has_valid_nonce(): bool {
		$nonce = isset( $_POST['poc_rtl_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['poc_rtl_nonce'] ) ) : '';

		return '' !== $nonce && wp_verify_nonce( $nonce, 'poc_rtl_add_to_cart' );
	}

	/**
	 * Return posted configurator payload.
	 *
	 * @return array<string, mixed>
	 */
	private function posted_payload(): array {
		if ( ! isset( $_POST['poc_rtl'] ) || ! is_array( $_POST['poc_rtl'] ) ) {
			return array();
		}

		return wp_unslash( $_POST['poc_rtl'] );
	}

	/**
	 * Validate selected product options.
	 *
	 * @param array<string, mixed> $payload Posted payload.
	 * @param array<string, mixed> $config Product config.
	 */
	private function validate_options( array $payload, array $config ): bool {
		$options = isset( $payload['options'] ) && is_array( $payload['options'] ) ? $payload['options'] : array();

		foreach ( $this->option_fields() as $field => $label ) {
			$allowed = $config[ $field ] ?? array();

			if ( ! is_array( $allowed ) || empty( $allowed ) ) {
				continue;
			}

			$value = isset( $options[ $field ] ) ? sanitize_text_field( (string) $options[ $field ] ) : '';

			if ( '' === $value ) {
				wc_add_notice(
					sprintf(
						/* translators: %s: option label */
						__( 'יש לבחור %s.', 'print-order-configurator-rtl' ),
						$label
					),
					'error'
				);
				return false;
			}

			if ( ! in_array( $value, array_map( 'strval', $allowed ), true ) ) {
				wc_add_notice( __( 'נבחרה אפשרות שאינה זמינה למוצר הזה.', 'print-order-configurator-rtl' ), 'error' );
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate design brief required fields.
	 *
	 * @param array<string, mixed> $payload Posted payload.
	 */
	private function validate_design_brief( array $payload ): bool {
		$brief = isset( $payload['brief'] ) && is_array( $payload['brief'] ) ? $payload['brief'] : array();

		$required = array(
			'business_name' => __( 'שם העסק', 'print-order-configurator-rtl' ),
			'phone'         => __( 'טלפון', 'print-order-configurator-rtl' ),
			'design_text'   => __( 'טקסט לעיצוב', 'print-order-configurator-rtl' ),
		);

		foreach ( $required as $field => $label ) {
			$value = isset( $brief[ $field ] ) ? trim( sanitize_textarea_field( (string) $brief[ $field ] ) ) : '';

			if ( '' === $value ) {
				wc_add_notice(
					sprintf(
						/* translators: %s: field label */
						__( 'יש למלא %s כדי לבקש עיצוב מהדפוס.', 'print-order-configurator-rtl' ),
						$label
					),
					'error'
				);
				return false;
			}
		}

		return true;
	}

	/**
	 * Count uploaded files for a field.
	 *
	 * @param string $field File field name.
	 */
	private function count_uploaded_files( string $field ): int {
		if ( ! isset( $_FILES[ $field ]['name'] ) ) {
			return 0;
		}

		$names = $_FILES[ $field ]['name'];

		if ( is_array( $names ) ) {
			return count(
				array_filter(
					$names,
					static fn( $name ): bool => '' !== (string) $name
				)
			);
		}

		return '' === (string) $names ? 0 : 1;
	}

	/**
	 * Product option field labels.
	 *
	 * @return array<string, string>
	 */
	private function option_fields(): array {
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
}
