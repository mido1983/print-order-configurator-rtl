<?php
/**
 * Internal workflow statuses.
 *
 * @package PrintOrderConfiguratorRTL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Status helper.
 */
final class POC_RTL_Statuses {
	public const ORDER_META_KEY = '_poc_rtl_workflow_status';

	/**
	 * Available internal statuses.
	 *
	 * @return array<string, string>
	 */
	public static function all(): array {
		return array(
			'files_received'            => __( 'קבצים התקבלו', 'print-order-configurator-rtl' ),
			'waiting_for_design'        => __( 'ממתין לעיצוב', 'print-order-configurator-rtl' ),
			'waiting_customer_approval' => __( 'ממתין לאישור לקוח', 'print-order-configurator-rtl' ),
			'ready_for_print'           => __( 'מוכן להדפסה', 'print-order-configurator-rtl' ),
			'sent_to_print'             => __( 'נשלח להדפסה', 'print-order-configurator-rtl' ),
		);
	}

	/**
	 * Sanitize a status value.
	 *
	 * @param string $status Raw status.
	 */
	public static function sanitize( string $status ): string {
		$status = sanitize_key( $status );

		return array_key_exists( $status, self::all() ) ? $status : 'files_received';
	}
}
