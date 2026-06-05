<?php
/**
 * Secure upload handling.
 *
 * @package PrintOrderConfiguratorRTL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upload handler service.
 */
final class POC_RTL_Upload_Handler {
	private const UPLOAD_SUBDIR = 'poc-rtl-orders';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'attach_uploads_to_cart_item' ), 20, 3 );
		add_action( 'admin_post_poc_rtl_download_upload', array( $this, 'download_upload' ) );
	}

	/**
	 * Validate uploaded files before add-to-cart continues.
	 *
	 * @param string               $field File field name.
	 * @param array<string, mixed> $config Product config.
	 */
	public static function validate_file_field( string $field, array $config ): bool {
		$files = self::normalize_files( $field );

		if ( empty( $files ) ) {
			return true;
		}

		$max_files = isset( $config['max_files'] ) ? max( 1, (int) $config['max_files'] ) : 20;

		if ( count( $files ) > $max_files ) {
			wc_add_notice(
				sprintf(
					/* translators: %d: max file count */
					__( 'ניתן להעלות עד %d קבצים להזמנה.', 'print-order-configurator-rtl' ),
					$max_files
				),
				'error'
			);
			return false;
		}

		foreach ( $files as $file ) {
			$result = self::validate_single_file( $file, $config );

			if ( is_wp_error( $result ) ) {
				wc_add_notice( $result->get_error_message(), 'error' );
				return false;
			}
		}

		return true;
	}

	/**
	 * Add uploaded file metadata to the cart item.
	 *
	 * @param array<string, mixed> $cart_item_data Cart item data.
	 * @param int                  $product_id Product ID.
	 * @param int                  $variation_id Variation ID.
	 * @return array<string, mixed>
	 */
	public function attach_uploads_to_cart_item( array $cart_item_data, int $product_id, int $variation_id ): array {
		unset( $variation_id );

		if ( ! POC_RTL_Product_Settings::is_enabled( $product_id ) ) {
			return $cart_item_data;
		}

		$config = POC_RTL_Product_Settings::get_product_config( $product_id );
		$ready  = $this->store_file_field( 'poc_rtl_ready_files', $config );
		$brief  = $this->store_file_field( 'poc_rtl_brief_files', $config );

		if ( empty( $ready ) && empty( $brief ) ) {
			return $cart_item_data;
		}

		if ( empty( $cart_item_data[ POC_RTL_Cart::CART_KEY ] ) || ! is_array( $cart_item_data[ POC_RTL_Cart::CART_KEY ] ) ) {
			$cart_item_data[ POC_RTL_Cart::CART_KEY ] = array();
		}

		$cart_item_data[ POC_RTL_Cart::CART_KEY ]['files'] = array(
			'ready'       => $ready,
			'need_design' => $brief,
		);

		return $cart_item_data;
	}

	/**
	 * Build a nonce-protected admin download URL.
	 *
	 * @param string $path Absolute stored path.
	 */
	public static function build_download_url( string $path ): string {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'poc_rtl_download_upload',
					'file'   => self::relative_upload_path( $path ),
				),
				admin_url( 'admin-post.php' )
			),
			'poc_rtl_download_upload'
		);
	}

	/**
	 * Serve a protected download for authorized admins.
	 */
	public function download_upload(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die(
				esc_html__( 'אין הרשאה להוריד את הקובץ.', 'print-order-configurator-rtl' ),
				esc_html__( 'אין הרשאה', 'print-order-configurator-rtl' ),
				array( 'response' => 403 )
			);
		}

		check_admin_referer( 'poc_rtl_download_upload' );

		$relative = isset( $_GET['file'] ) ? sanitize_text_field( wp_unslash( rawurldecode( $_GET['file'] ) ) ) : '';
		$path     = self::upload_base_dir() . '/' . ltrim( $relative, '/\\' );
		$real     = realpath( $path );
		$base     = realpath( self::upload_base_dir() );
		$base     = false === $base ? false : trailingslashit( $base );

		if ( false === $real || false === $base || ! str_starts_with( $real, $base ) || ! is_readable( $real ) ) {
			wp_die(
				esc_html__( 'הקובץ לא נמצא.', 'print-order-configurator-rtl' ),
				esc_html__( 'קובץ לא נמצא', 'print-order-configurator-rtl' ),
				array( 'response' => 404 )
			);
		}

		nocache_headers();
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . esc_attr( basename( $real ) ) . '"' );
		header( 'Content-Length: ' . filesize( $real ) );
		readfile( $real ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	/**
	 * Store all files from one field.
	 *
	 * @param string               $field File field name.
	 * @param array<string, mixed> $config Product config.
	 * @return array<int, array<string, mixed>>
	 */
	private function store_file_field( string $field, array $config ): array {
		$files  = self::normalize_files( $field );
		$stored = array();

		if ( empty( $files ) ) {
			return $stored;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		foreach ( $files as $file ) {
			$result = self::validate_single_file( $file, $config );

			if ( is_wp_error( $result ) ) {
				continue;
			}

			add_filter( 'upload_dir', array( $this, 'filter_upload_dir' ) );
			$uploaded = wp_handle_upload(
				$file,
				array(
					'test_form' => false,
					'mimes'     => $this->allowed_mime_map( $config ),
				)
			);
			remove_filter( 'upload_dir', array( $this, 'filter_upload_dir' ) );

			if ( empty( $uploaded['file'] ) || ! empty( $uploaded['error'] ) ) {
				continue;
			}

			$this->protect_upload_dir();

			$stored[] = array(
				'path'          => $uploaded['file'],
				'type'          => $uploaded['type'] ?? '',
				'original_name' => sanitize_text_field( wp_basename( (string) $file['name'] ) ),
				'stored_name'   => basename( (string) $uploaded['file'] ),
				'size'          => filesize( (string) $uploaded['file'] ),
			);
		}

		return $stored;
	}

	/**
	 * Validate one uploaded file.
	 *
	 * @param array<string, mixed> $file File array.
	 * @param array<string, mixed> $config Product config.
	 * @return true|WP_Error
	 */
	private static function validate_single_file( array $file, array $config ): true|WP_Error {
		if ( empty( $file['name'] ) ) {
			return true;
		}

		if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new WP_Error( 'poc_rtl_upload_error', __( 'העלאת אחד הקבצים נכשלה. נסו שוב.', 'print-order-configurator-rtl' ) );
		}

		$max_size_mb = isset( $config['max_file_size_mb'] ) ? max( 1, (int) $config['max_file_size_mb'] ) : 25;
		$max_bytes   = $max_size_mb * MB_IN_BYTES;

		if ( ! empty( $file['size'] ) && (int) $file['size'] > $max_bytes ) {
			return new WP_Error(
				'poc_rtl_upload_size',
				sprintf(
					/* translators: %d: max file size in MB */
					__( 'אחד הקבצים גדול מדי. הגודל המקסימלי הוא %dMB.', 'print-order-configurator-rtl' ),
					$max_size_mb
				)
			);
		}

		$extension = strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) );
		$allowed   = isset( $config['allowed_extensions'] ) && is_array( $config['allowed_extensions'] )
			? array_map( 'strval', $config['allowed_extensions'] )
			: array( 'pdf', 'ai', 'psd', 'eps', 'jpg', 'jpeg', 'png', 'zip' );
		$dangerous = array( 'php', 'phtml', 'phar', 'exe', 'js', 'sh', 'bat', 'cmd', 'com', 'scr', 'svg' );

		if ( '' === $extension || in_array( $extension, $dangerous, true ) || ! in_array( $extension, $allowed, true ) ) {
			return new WP_Error( 'poc_rtl_upload_extension', __( 'סוג קובץ לא מורשה להעלאה.', 'print-order-configurator-rtl' ) );
		}

		$type = wp_check_filetype_and_ext( (string) $file['tmp_name'], (string) $file['name'], self::mime_map() );

		if ( empty( $type['ext'] ) || empty( $type['type'] ) ) {
			return new WP_Error( 'poc_rtl_upload_mime', __( 'לא ניתן לאמת את סוג הקובץ.', 'print-order-configurator-rtl' ) );
		}

		return true;
	}

	/**
	 * Normalize a multi-file upload field.
	 *
	 * @param string $field File field name.
	 * @return array<int, array<string, mixed>>
	 */
	private static function normalize_files( string $field ): array {
		if ( empty( $_FILES[ $field ]['name'] ) ) {
			return array();
		}

		$raw = $_FILES[ $field ];

		if ( ! is_array( $raw['name'] ) ) {
			return array( $raw );
		}

		$files = array();

		foreach ( $raw['name'] as $index => $name ) {
			if ( '' === (string) $name ) {
				continue;
			}

			$files[] = array(
				'name'     => $name,
				'type'     => $raw['type'][ $index ] ?? '',
				'tmp_name' => $raw['tmp_name'][ $index ] ?? '',
				'error'    => $raw['error'][ $index ] ?? UPLOAD_ERR_NO_FILE,
				'size'     => $raw['size'][ $index ] ?? 0,
			);
		}

		return $files;
	}

	/**
	 * Route uploads to protected plugin folder.
	 *
	 * @param array<string, mixed> $dirs Upload dirs.
	 * @return array<string, mixed>
	 */
	public function filter_upload_dir( array $dirs ): array {
		$subdir = '/' . self::UPLOAD_SUBDIR . gmdate( '/Y/m' );

		$dirs['subdir'] = $subdir;
		$dirs['path']   = $dirs['basedir'] . $subdir;
		$dirs['url']    = $dirs['baseurl'] . $subdir;

		return $dirs;
	}

	/**
	 * Protect upload folder from direct web access where supported.
	 */
	private function protect_upload_dir(): void {
		$dir = self::upload_base_dir();

		if ( ! wp_mkdir_p( $dir ) ) {
			return;
		}

		if ( ! file_exists( $dir . '/index.php' ) ) {
			file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			file_put_contents( $dir . '/.htaccess', "Deny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
	}

	/**
	 * Allowed MIME map for wp_handle_upload.
	 *
	 * @param array<string, mixed> $config Product config.
	 * @return array<string, string>
	 */
	private function allowed_mime_map( array $config ): array {
		$allowed = isset( $config['allowed_extensions'] ) && is_array( $config['allowed_extensions'] )
			? array_map( 'strval', $config['allowed_extensions'] )
			: array_keys( self::mime_map() );

		return array_intersect_key( self::mime_map(), array_flip( $allowed ) );
	}

	/**
	 * Supported MIME map.
	 *
	 * @return array<string, string>
	 */
	private static function mime_map(): array {
		return array(
			'pdf'  => 'application/pdf',
			'ai'   => 'application/postscript',
			'psd'  => 'image/vnd.adobe.photoshop',
			'eps'  => 'application/postscript',
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
			'zip'  => 'application/zip',
		);
	}

	/**
	 * Protected upload base directory.
	 */
	private static function upload_base_dir(): string {
		$uploads = wp_get_upload_dir();

		return trailingslashit( $uploads['basedir'] ) . self::UPLOAD_SUBDIR;
	}

	/**
	 * Return path relative to protected upload base.
	 *
	 * @param string $path Absolute stored path.
	 */
	private static function relative_upload_path( string $path ): string {
		$base = trailingslashit( self::upload_base_dir() );

		return ltrim( str_replace( $base, '', $path ), '/\\' );
	}
}
