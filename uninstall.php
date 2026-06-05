<?php
/**
 * Plugin uninstall cleanup.
 *
 * @package PrintOrderConfiguratorRTL
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// MVP data is order/product metadata and should not be deleted automatically.
