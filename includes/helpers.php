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

	return implode( "\n", poc_rtl_option_labels( $value ) );
}

/**
 * Extract an option label from legacy strings or structured rows.
 *
 * @param mixed $option Option value.
 */
function poc_rtl_option_label( mixed $option ): string {
	if ( is_array( $option ) ) {
		return isset( $option['label'] ) ? sanitize_text_field( (string) $option['label'] ) : '';
	}

	return sanitize_text_field( (string) $option );
}

/**
 * Extract labels from an option list.
 *
 * @param mixed $options Option list.
 * @return array<int, string>
 */
function poc_rtl_option_labels( mixed $options ): array {
	if ( ! is_array( $options ) ) {
		return array();
	}

	$labels = array();

	foreach ( $options as $option ) {
		$label = poc_rtl_option_label( $option );

		if ( '' !== $label ) {
			$labels[] = $label;
		}
	}

	return array_values( array_unique( $labels ) );
}

/**
 * Return whether the active site/admin locale is Hebrew.
 *
 * @param string $scope Locale scope: site, user, or auto.
 */
function poc_rtl_is_hebrew_locale( string $scope = 'auto' ): bool {
	$locale = match ( $scope ) {
		'user'  => function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale(),
		'site'  => get_locale(),
		default => is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale(),
	};

	return str_starts_with( strtolower( (string) $locale ), 'he' );
}

/**
 * Locale-aware UI text fallback for untranslated installs.
 *
 * @param string $he Hebrew text.
 * @param string $en English text.
 * @param string $scope Locale scope: site, user, or auto.
 */
function poc_rtl_ui_text( string $he, string $en, string $scope = 'auto' ): string {
	$text = poc_rtl_is_hebrew_locale( $scope ) ? $he : $en;

	return translate( $text, 'print-order-configurator-rtl' );
}

/**
 * Escape locale-aware UI text.
 *
 * @param string $he Hebrew text.
 * @param string $en English text.
 * @param string $scope Locale scope: site, user, or auto.
 */
function poc_rtl_esc_html( string $he, string $en, string $scope = 'auto' ): string {
	return esc_html( poc_rtl_ui_text( $he, $en, $scope ) );
}

/**
 * Escape locale-aware UI text for attributes.
 *
 * @param string $he Hebrew text.
 * @param string $en English text.
 * @param string $scope Locale scope: site, user, or auto.
 */
function poc_rtl_esc_attr( string $he, string $en, string $scope = 'auto' ): string {
	return esc_attr( poc_rtl_ui_text( $he, $en, $scope ) );
}
