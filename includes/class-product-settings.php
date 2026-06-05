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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue product settings assets.
	 */
	public function enqueue_assets(): void {
		$screen = get_current_screen();

		if ( ! $screen || 'product' !== $screen->id ) {
			return;
		}

		wp_enqueue_style(
			'poc-rtl-product-settings',
			POC_RTL_URL . 'assets/css/product-settings.css',
			array(),
			POC_RTL_VERSION
		);

		wp_enqueue_script(
			'poc-rtl-product-settings',
			POC_RTL_URL . 'assets/js/product-settings.js',
			array(),
			POC_RTL_VERSION,
			true
		);
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
			<div class="options_group poc-rtl-product-section">
				<h3><?php echo poc_rtl_esc_html( 'הגדרות כלליות', 'General settings', 'user' ); ?></h3>
				<p class="poc-rtl-section-help"><?php echo poc_rtl_esc_html( 'הפעילו את הקונפיגורטור רק למוצרים שבהם הלקוח צריך לבחור אפשרויות הדפסה.', 'Enable the configurator only for products where customers need print options.', 'user' ); ?></p>
				<?php
				woocommerce_wp_checkbox(
					array(
						'id'          => self::META_ENABLED,
						'label'       => poc_rtl_ui_text( 'הפעל קונפיגורטור הדפסה', 'Enable print configurator', 'user' ),
						'description' => poc_rtl_ui_text( 'הציגו ללקוח תהליך בחירה מסודר בעמוד המוצר.', 'Show a guided print order flow on this product page.', 'user' ),
						'value'       => $enabled ? 'yes' : 'no',
					)
				);
				?>
			</div>

			<div class="options_group poc-rtl-product-section">
				<h3><?php echo poc_rtl_esc_html( 'אפשרויות הדפסה', 'Printing options', 'user' ); ?></h3>
				<p class="poc-rtl-section-help"><?php echo poc_rtl_esc_html( 'הגדירו רק אפשרויות אמיתיות וברורות. הלקוח לא יקליד ערכים תפעוליים ידנית.', 'Define only real, clear options. Customers will not type operational values manually.', 'user' ); ?></p>
				<?php
				$this->render_repeatable_field( 'sizes', poc_rtl_ui_text( 'מידות זמינות', 'Available sizes', 'user' ), $config );
				$this->render_repeatable_field( 'quantities', poc_rtl_ui_text( 'כמויות זמינות', 'Available quantities', 'user' ), $config );
				$this->render_repeatable_field( 'paper_types', poc_rtl_ui_text( 'סוגי נייר זמינים', 'Available paper types', 'user' ), $config );
				$this->render_repeatable_field( 'paper_weights', poc_rtl_ui_text( 'משקלי נייר זמינים', 'Available paper weights', 'user' ), $config );
				$this->render_repeatable_field( 'print_sides', poc_rtl_ui_text( 'צדדי הדפסה זמינים', 'Available print sides', 'user' ), $config );
				$this->render_repeatable_field( 'lamination', poc_rtl_ui_text( 'אפשרויות למינציה', 'Available lamination options', 'user' ), $config );
				$this->render_repeatable_field( 'corners', poc_rtl_ui_text( 'אפשרויות פינות', 'Available corner options', 'user' ), $config );
				$this->render_repeatable_field( 'finishing_options', poc_rtl_ui_text( 'אפשרויות גימור', 'Available finishing options', 'user' ), $config );
				?>
				<p class="description">
					<?php echo poc_rtl_esc_html( 'הוסיפו אפשרויות ברורות שהלקוח יבחר מהן בעמוד המוצר.', 'Add clear options customers will choose from on the product page.', 'user' ); ?>
				</p>
			</div>

			<div class="options_group poc-rtl-product-section">
				<h3><?php echo poc_rtl_esc_html( 'שירות עיצוב', 'Design service', 'user' ); ?></h3>
				<p class="poc-rtl-section-help"><?php echo poc_rtl_esc_html( 'אם הלקוח צריך עיצוב, התוסף יאסוף בריף וקבצי השראה. העיצוב עדיין מבוצע ידנית על ידי הצוות.', 'When customers need design, the plugin collects a brief and references. Design work remains manual.', 'user' ); ?></p>
				<?php
				woocommerce_wp_checkbox(
					array(
						'id'          => 'poc_rtl_design_service_enabled',
						'label'       => poc_rtl_ui_text( 'אפשר בקשת עיצוב למוצר הזה', 'Allow design service request for this product', 'user' ),
						'description' => poc_rtl_ui_text( 'מומלץ למוצרים שבהם בית הדפוס יכול להכין עיצוב לפי בריף.', 'Use when the shop can prepare design from a customer brief.', 'user' ),
						'value'       => ! empty( $config['design_service_enabled'] ) ? 'yes' : 'no',
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => 'poc_rtl_design_service_fee',
						'label'             => poc_rtl_ui_text( 'עלות שירות עיצוב', 'Design service fee', 'user' ),
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

			<div class="options_group poc-rtl-product-section">
				<h3><?php echo poc_rtl_esc_html( 'העלאות קבצים', 'Uploads', 'user' ); ?></h3>
				<p class="poc-rtl-section-help"><?php echo poc_rtl_esc_html( 'קבעו כמה קבצים מותר להעלות ואילו סוגים מותרים. קבצים מסוכנים ייחסמו בכל מקרה.', 'Set how many files can be uploaded and which types are allowed. Dangerous files are always blocked.', 'user' ); ?></p>
				<?php
				woocommerce_wp_text_input(
					array(
						'id'                => 'poc_rtl_max_files',
						'label'             => poc_rtl_ui_text( 'מספר קבצים מקסימלי', 'Maximum files', 'user' ),
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
						'label'             => poc_rtl_ui_text( 'גודל קובץ מקסימלי MB', 'Maximum file size MB', 'user' ),
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
						'label'       => poc_rtl_ui_text( 'סיומות קבצים מותרות', 'Allowed file extensions', 'user' ),
						'value'       => implode( ', ', $config['allowed_extensions'] ),
						'description' => poc_rtl_ui_text( 'רשימה מופרדת בפסיקים. קבצי הרצה מסוכנים תמיד ייחסמו.', 'Comma-separated list. Dangerous executable files are always rejected.', 'user' ),
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
			$raw              = isset( $_POST[ $key ] ) ? $_POST[ $key ] : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$config[ $field ] = $this->sanitize_option_rows( $raw );
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
	private function render_repeatable_field( string $field, string $label, array $config ): void {
		$options = $config[ $field ] ?? array();
		?>
		<div class="poc-rtl-repeatable" data-poc-rtl-repeatable="<?php echo esc_attr( $field ); ?>">
			<p class="form-field poc-rtl-repeatable-heading">
				<label><?php echo esc_html( $label ); ?></label>
				<span class="woocommerce-help-tip" tabindex="0" aria-label="<?php echo poc_rtl_esc_attr( 'הלקוח יבחר אחת מהאפשרויות האלה בעמוד המוצר.', 'Customers will choose one of these options on the product page.', 'user' ); ?>"></span>
			</p>
			<div class="poc-rtl-repeatable-rows" data-poc-rtl-repeatable-rows>
				<?php foreach ( $options as $option ) : ?>
					<?php $this->render_repeatable_row( $field, is_array( $option ) ? $option : array( 'label' => $option, 'price_modifier' => '' ) ); ?>
				<?php endforeach; ?>
			</div>
			<p class="poc-rtl-repeatable-empty" data-poc-rtl-repeatable-empty><?php echo poc_rtl_esc_html( 'עדיין אין אפשרויות. לחצו על הוספת אפשרות כדי להתחיל.', 'No options yet. Click Add option to start.', 'user' ); ?></p>
			<p class="toolbar">
				<button type="button" class="button poc-rtl-add-option" data-poc-rtl-add-option><?php echo poc_rtl_esc_html( 'הוסף אפשרות', 'Add option', 'user' ); ?></button>
			</p>
			<template data-poc-rtl-repeatable-template>
				<?php $this->render_repeatable_row( $field, array( 'label' => '', 'price_modifier' => '' ) ); ?>
			</template>
		</div>
		<?php
	}

	/**
	 * Render one repeatable row.
	 *
	 * @param string               $field Field key.
	 * @param array<string, mixed> $option Option row.
	 */
	private function render_repeatable_row( string $field, array $option ): void {
		?>
		<div class="poc-rtl-repeatable-row">
			<input type="text" name="poc_rtl_<?php echo esc_attr( $field ); ?>[label][]" value="<?php echo esc_attr( poc_rtl_option_label( $option ) ); ?>" placeholder="<?php echo poc_rtl_esc_attr( 'שם אפשרות', 'Option label', 'user' ); ?>">
			<input type="number" name="poc_rtl_<?php echo esc_attr( $field ); ?>[price_modifier][]" value="<?php echo esc_attr( (string) ( $option['price_modifier'] ?? '' ) ); ?>" step="0.01" placeholder="<?php echo poc_rtl_esc_attr( 'תוספת מחיר עתידית', 'Future price modifier', 'user' ); ?>">
			<button type="button" class="button-link-delete poc-rtl-remove-option"><?php echo poc_rtl_esc_html( 'הסר', 'Remove', 'user' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Sanitize structured option rows.
	 *
	 * @param mixed $raw Raw submitted rows.
	 * @return array<int, array<string, string>>
	 */
	private function sanitize_option_rows( mixed $raw ): array {
		if ( is_string( $raw ) ) {
			return array_map(
				static fn( string $label ): array => array(
					'label'          => $label,
					'price_modifier' => '',
				),
				poc_rtl_sanitize_option_lines( $raw )
			);
		}

		if ( ! is_array( $raw ) ) {
			return array();
		}

		$labels = isset( $raw['label'] ) && is_array( $raw['label'] ) ? $raw['label'] : array();
		$prices = isset( $raw['price_modifier'] ) && is_array( $raw['price_modifier'] ) ? $raw['price_modifier'] : array();
		$rows   = array();

		foreach ( $labels as $index => $label ) {
			$label = sanitize_text_field( wp_unslash( (string) $label ) );

			if ( '' === $label ) {
				continue;
			}

			$rows[] = array(
				'label'          => $label,
				'price_modifier' => isset( $prices[ $index ] ) ? wc_format_decimal( wp_unslash( $prices[ $index ] ) ) : '',
			);
		}

		return $rows;
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
