<?php
/**
 * Shared helper functions.
 *
 * @package PrintOrderConfiguratorRTL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return whether WooCommerce is active and loaded.
 */
function poc_rtl_is_woocommerce_active(): bool {
	return class_exists( 'WooCommerce' );
}

/**
 * Sanitize a list of newline-separated option labels.
 *
 * @param string $value Raw textarea value.
 * @return array<int, string>
 */
function poc_rtl_sanitize_option_lines( string $value ): array {
	$lines = preg_split( '/\r\n|\r|\n/', $value );

	if ( false === $lines ) {
		return array();
	}

	$clean = array();

	foreach ( $lines as $line ) {
		$line = sanitize_text_field( wp_unslash( $line ) );

		if ( '' !== $line ) {
			$clean[] = $line;
		}
	}

	return array_values( array_unique( $clean ) );
}

/**
 * Convert stored option arrays to textarea-safe text.
 *
 * @param mixed $value Stored option value.
 */
function poc_rtl_option_lines_to_text( mixed $value ): string {
	if ( ! is_array( $value ) ) {
		return '';
	}

	return implode( "\n", array_map( 'strval', $value ) );
}
