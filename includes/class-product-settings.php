<?php
/**
 * WooCommerce product-level settings.
 *
 * @package PrintOrderConfiguratorRTL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product settings service.
 */
final class POC_RTL_Product_Settings {
	private const META_ENABLED = '_poc_rtl_enabled';
	private const META_CONFIG = '_poc_rtl_config';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_data_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_product_data_panel' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_product_settings' ) );
	}

	/**
	 * Add product data tab.
	 *
	 * @param array<string, mixed> $tabs Product data tabs.
	 * @return array<string, mixed>
	 */
	public function add_product_data_tab( array $tabs ): array {
		$tabs['poc_rtl'] = array(
			'label'    => __( 'Print Configurator', 'print-order-configurator-rtl' ),
			'target'   => 'poc_rtl_product_data',
			'class'    => array( 'show_if_simple', 'show_if_variable' ),
			'priority' => 65,
		);

		return $tabs;
	}

	/**
	 * Render product settings panel.
	 */
	public function render_product_data_panel(): void {
		global $post;

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$config  = self::get_product_config( $post->ID );
		$enabled = 'yes' === get_post_meta( $post->ID, self::META_ENABLED, true );
		?>
		<div id="poc_rtl_product_data" class="panel woocommerce_options_panel">
			<div class="options_group">
				<?php
				woocommerce_wp_checkbox(
					array(
						'id'          => self::META_ENABLED,
						'label'       => __( 'Enable print configurator', 'print-order-configurator-rtl' ),
						'description' => __( 'Show the RTL print order configurator on this product.', 'print-order-configurator-rtl' ),
						'value'       => $enabled ? 'yes' : 'no',
					)
				);
				?>
			</div>

			<div class="options_group">
				<?php
				$this->render_textarea_field( 'sizes', __( 'Sizes', 'print-order-configurator-rtl' ), $config );
				$this->render_textarea_field( 'quantities', __( 'Quantities', 'print-order-configurator-rtl' ), $config );
				$this->render_textarea_field( 'paper_types', __( 'Paper types', 'print-order-configurator-rtl' ), $config );
				$this->render_textarea_field( 'paper_weights', __( 'Paper weights', 'print-order-configurator-rtl' ), $config );
				$this->render_textarea_field( 'print_sides', __( 'Print sides', 'print-order-configurator-rtl' ), $config );
				$this->render_textarea_field( 'lamination', __( 'Lamination', 'print-order-configurator-rtl' ), $config );
				$this->render_textarea_field( 'corners', __( 'Corners', 'print-order-configurator-rtl' ), $config );
				$this->render_textarea_field( 'finishing_options', __( 'Finishing options', 'print-order-configurator-rtl' ), $config );
				?>
				<p class="description">
					<?php esc_html_e( 'Enter one available option per line. Hebrew and mixed Hebrew/English values are supported.', 'print-order-configurator-rtl' ); ?>
				</p>
			</div>

			<div class="options_group">
				<?php
				woocommerce_wp_checkbox(
					array(
						'id'          => 'poc_rtl_design_service_enabled',
						'label'       => __( 'Enable design service request', 'print-order-configurator-rtl' ),
						'description' => __( 'Allow customers to request manual design service for this product.', 'print-order-configurator-rtl' ),
						'value'       => ! empty( $config['design_service_enabled'] ) ? 'yes' : 'no',
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => 'poc_rtl_design_service_fee',
						'label'             => __( 'Design service fee', 'print-order-configurator-rtl' ),
						'type'              => 'number',
						'value'             => $config['design_service_fee'],
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '0.01',
						),
					)
				);
				?>
			</div>

			<div class="options_group">
				<?php
				woocommerce_wp_text_input(
					array(
						'id'                => 'poc_rtl_max_files',
						'label'             => __( 'Maximum files', 'print-order-configurator-rtl' ),
						'type'              => 'number',
						'value'             => $config['max_files'],
						'custom_attributes' => array(
							'min'  => '1',
							'step' => '1',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => 'poc_rtl_max_file_size_mb',
						'label'             => __( 'Maximum file size MB', 'print-order-configurator-rtl' ),
						'type'              => 'number',
						'value'             => $config['max_file_size_mb'],
						'custom_attributes' => array(
							'min'  => '1',
							'step' => '1',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => 'poc_rtl_allowed_extensions',
						'label'       => __( 'Allowed file extensions', 'print-order-configurator-rtl' ),
						'value'       => implode( ', ', $config['allowed_extensions'] ),
						'description' => __( 'Comma-separated list. Dangerous executable extensions are always rejected.', 'print-order-configurator-rtl' ),
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save settings for a product.
	 *
	 * @param WC_Product $product Product object.
	 */
	public function save_product_settings( WC_Product $product ): void {
		$enabled = isset( $_POST[ self::META_ENABLED ] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$product->update_meta_data( self::META_ENABLED, $enabled );

		$config = self::default_config();

		foreach ( self::option_fields() as $field ) {
			$key              = 'poc_rtl_' . $field;
			$raw              = isset( $_POST[ $key ] ) ? (string) $_POST[ $key ] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$config[ $field ] = poc_rtl_sanitize_option_lines( $raw );
		}

		$config['design_service_enabled'] = isset( $_POST['poc_rtl_design_service_enabled'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$config['design_service_fee']     = isset( $_POST['poc_rtl_design_service_fee'] ) ? wc_format_decimal( wp_unslash( $_POST['poc_rtl_design_service_fee'] ) ) : '0'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$config['max_files']              = isset( $_POST['poc_rtl_max_files'] ) ? max( 1, absint( $_POST['poc_rtl_max_files'] ) ) : 20; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$config['max_file_size_mb']       = isset( $_POST['poc_rtl_max_file_size_mb'] ) ? max( 1, absint( $_POST['poc_rtl_max_file_size_mb'] ) ) : 25; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$config['allowed_extensions']     = $this->sanitize_extensions(
			isset( $_POST['poc_rtl_allowed_extensions'] ) ? (string) $_POST['poc_rtl_allowed_extensions'] : '' // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);

		$product->update_meta_data( self::META_CONFIG, $config );
	}

	/**
	 * Get saved product config with defaults.
	 *
	 * @param int $product_id Product ID.
	 * @return array<string, mixed>
	 */
	public static function get_product_config( int $product_id ): array {
		$stored = get_post_meta( $product_id, self::META_CONFIG, true );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return wp_parse_args( $stored, self::default_config() );
	}

	/**
	 * Return whether configurator is enabled for a product.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function is_enabled( int $product_id ): bool {
		return POC_RTL_Settings::is_global_enabled() && 'yes' === get_post_meta( $product_id, self::META_ENABLED, true );
	}

	/**
	 * Render textarea option field.
	 *
	 * @param string               $field Field key.
	 * @param string               $label Field label.
	 * @param array<string, mixed> $config Product config.
	 */
	private function render_textarea_field( string $field, string $label, array $config ): void {
		woocommerce_wp_textarea_input(
			array(
				'id'    => 'poc_rtl_' . $field,
				'label' => $label,
				'value' => poc_rtl_option_lines_to_text( $config[ $field ] ?? array() ),
				'rows'  => 4,
			)
		);
	}

	/**
	 * Default product config.
	 *
	 * @return array<string, mixed>
	 */
	private static function default_config(): array {
		$multiple_uploads = POC_RTL_Settings::enabled( 'pocrtl_default_multiple_uploads' );

		return array(
			'sizes'                  => array(),
			'quantities'             => array(),
			'paper_types'            => array(),
			'paper_weights'          => array(),
			'print_sides'            => array(),
			'lamination'             => array(),
			'corners'                => array(),
			'finishing_options'      => array(),
			'design_service_enabled' => POC_RTL_Settings::enabled( 'pocrtl_design_service_enabled_global' ),
			'design_service_fee'     => (string) POC_RTL_Settings::get( 'pocrtl_default_design_service_price', '0' ),
			'max_files'              => $multiple_uploads ? 20 : 1,
			'max_file_size_mb'       => (int) POC_RTL_Settings::get( 'pocrtl_default_max_file_size_mb', 100 ),
			'allowed_extensions'     => POC_RTL_Settings::default_allowed_extensions(),
		);
	}

	/**
	 * Product option field keys.
	 *
	 * @return array<int, string>
	 */
	private static function option_fields(): array {
		return array(
			'sizes',
			'quantities',
			'paper_types',
			'paper_weights',
			'print_sides',
			'lamination',
			'corners',
			'finishing_options',
		);
	}

	/**
	 * Sanitize allowed extensions and remove dangerous values.
	 *
	 * @param string $raw Raw comma-separated extensions.
	 * @return array<int, string>
	 */
	private function sanitize_extensions( string $raw ): array {
		$dangerous = array( 'php', 'phtml', 'phar', 'exe', 'js', 'sh', 'bat', 'cmd', 'com', 'scr', 'svg' );

		if ( POC_RTL_Settings::enabled( 'pocrtl_allow_svg_uploads' ) ) {
			$dangerous = array_diff( $dangerous, array( 'svg' ) );
		}

		$parts     = preg_split( '/[\s,]+/', wp_unslash( $raw ) );

		if ( false === $parts ) {
			return self::default_config()['allowed_extensions'];
		}

		$clean = array();

		foreach ( $parts as $part ) {
			$extension = strtolower( sanitize_key( ltrim( $part, '.' ) ) );

			if ( '' !== $extension && ! in_array( $extension, $dangerous, true ) ) {
				$clean[] = $extension;
			}
		}

		$clean = array_values( array_unique( $clean ) );

		return empty( $clean ) ? self::default_config()['allowed_extensions'] : $clean;
	}
}
