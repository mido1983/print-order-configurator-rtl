<?php
/**
 * Frontend product configurator.
 *
 * @package PrintOrderConfiguratorRTL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend service.
 */
final class POC_RTL_Frontend {
	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_configurator' ) );
	}

	/**
	 * Enqueue frontend assets only for enabled product pages.
	 */
	public function enqueue_assets(): void {
		if ( ! is_product() ) {
			return;
		}

		$product_id = get_the_ID();

		if ( ! $product_id || ! POC_RTL_Product_Settings::is_enabled( (int) $product_id ) ) {
			return;
		}

		wp_enqueue_style(
			'poc-rtl-frontend',
			POC_RTL_URL . 'assets/css/frontend.css',
			array(),
			POC_RTL_VERSION
		);

		wp_enqueue_script(
			'poc-rtl-frontend',
			POC_RTL_URL . 'assets/js/frontend.js',
			array(),
			POC_RTL_VERSION,
			true
		);
	}

	/**
	 * Render the configurator form fields.
	 */
	public function render_configurator(): void {
		global $product;

		if ( ! $product instanceof WC_Product || ! POC_RTL_Product_Settings::is_enabled( $product->get_id() ) ) {
			return;
		}

		$config = POC_RTL_Product_Settings::get_product_config( $product->get_id() );
		$accept = $this->allowed_accept_attribute( $config );
		$dir    = (string) POC_RTL_Settings::get( 'pocrtl_default_direction', 'rtl' );
		$lang   = (string) POC_RTL_Settings::get( 'pocrtl_default_interface_language', 'he' );
		$multiple_uploads = ! empty( $config['max_files'] ) && (int) $config['max_files'] > 1;
		?>
		<section class="poc-rtl-configurator" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>" aria-labelledby="poc-rtl-title">
			<?php wp_nonce_field( 'poc_rtl_add_to_cart', 'poc_rtl_nonce' ); ?>
			<h2 id="poc-rtl-title" class="poc-rtl-title"><?php esc_html_e( 'פרטי הזמנת הדפסה', 'print-order-configurator-rtl' ); ?></h2>

			<div class="poc-rtl-grid">
				<?php
				$this->render_select( 'sizes', __( 'גודל', 'print-order-configurator-rtl' ), $config );
				$this->render_select( 'quantities', __( 'כמות', 'print-order-configurator-rtl' ), $config );
				$this->render_select( 'paper_types', __( 'סוג נייר', 'print-order-configurator-rtl' ), $config );
				$this->render_select( 'paper_weights', __( 'משקל נייר', 'print-order-configurator-rtl' ), $config );
				$this->render_select( 'print_sides', __( 'צדדי הדפסה', 'print-order-configurator-rtl' ), $config );
				$this->render_select( 'lamination', __( 'למינציה', 'print-order-configurator-rtl' ), $config );
				$this->render_select( 'corners', __( 'פינות', 'print-order-configurator-rtl' ), $config );
				$this->render_select( 'finishing_options', __( 'גימור', 'print-order-configurator-rtl' ), $config );
				?>
			</div>

			<fieldset class="poc-rtl-workflow">
				<legend><?php esc_html_e( 'מה מצב העיצוב?', 'print-order-configurator-rtl' ); ?></legend>

				<label class="poc-rtl-choice">
					<input type="radio" name="poc_rtl[design_mode]" value="ready" checked>
					<span><?php echo esc_html( (string) POC_RTL_Settings::get( 'pocrtl_label_ready_design', __( 'יש לי עיצוב מוכן', 'print-order-configurator-rtl' ) ) ); ?></span>
				</label>

				<?php if ( POC_RTL_Settings::enabled( 'pocrtl_design_service_enabled_global' ) && ! empty( $config['design_service_enabled'] ) ) : ?>
					<label class="poc-rtl-choice">
						<input type="radio" name="poc_rtl[design_mode]" value="need_design">
						<span>
							<?php echo esc_html( (string) POC_RTL_Settings::get( 'pocrtl_label_need_design', __( 'אין לי עיצוב - אני צריך עיצוב מהדפוס', 'print-order-configurator-rtl' ) ) ); ?>
							<?php if ( (float) $config['design_service_fee'] > 0 ) : ?>
								<bdi class="poc-rtl-fee">
									<?php
									printf(
										/* translators: %s: formatted price */
										esc_html__( '+ %s', 'print-order-configurator-rtl' ),
										wp_kses_post( wc_price( (float) $config['design_service_fee'] ) )
									);
									?>
								</bdi>
							<?php endif; ?>
						</span>
					</label>
				<?php endif; ?>
			</fieldset>

			<div class="poc-rtl-panel" data-poc-rtl-panel="ready">
				<label for="poc-rtl-production-notes"><?php esc_html_e( 'הערות להפקה', 'print-order-configurator-rtl' ); ?></label>
				<textarea id="poc-rtl-production-notes" name="poc_rtl[production_notes]" rows="4" placeholder="<?php esc_attr_e( 'לדוגמה: שלום Michael Design 054-1234567', 'print-order-configurator-rtl' ); ?>"></textarea>

				<label for="poc-rtl-ready-files"><?php esc_html_e( 'קבצים מוכנים להדפסה', 'print-order-configurator-rtl' ); ?></label>
				<input id="poc-rtl-ready-files" type="file" name="poc_rtl_ready_files[]"<?php echo $multiple_uploads ? ' multiple' : ''; ?> accept="<?php echo esc_attr( $accept ); ?>">
			</div>

			<div class="poc-rtl-panel" data-poc-rtl-panel="need_design" hidden>
				<div class="poc-rtl-grid">
					<?php
					$this->render_text_input( 'business_name', __( 'שם העסק', 'print-order-configurator-rtl' ) );
					$this->render_text_input( 'phone', __( 'טלפון', 'print-order-configurator-rtl' ), 'tel' );
					$this->render_text_input( 'email', __( 'אימייל', 'print-order-configurator-rtl' ), 'email' );
					$this->render_text_input( 'address', __( 'כתובת', 'print-order-configurator-rtl' ) );
					$this->render_text_input( 'preferred_colors', __( 'צבעים מועדפים', 'print-order-configurator-rtl' ) );
					$this->render_text_input( 'style', __( 'סגנון', 'print-order-configurator-rtl' ) );
					?>
				</div>

				<label for="poc-rtl-design-text"><?php esc_html_e( 'טקסט לעיצוב', 'print-order-configurator-rtl' ); ?></label>
				<textarea id="poc-rtl-design-text" name="poc_rtl[brief][design_text]" rows="5"></textarea>

				<label for="poc-rtl-design-references"><?php esc_html_e( 'רפרנסים והשראה', 'print-order-configurator-rtl' ); ?></label>
				<textarea id="poc-rtl-design-references" name="poc_rtl[brief][references]" rows="4"></textarea>

				<label for="poc-rtl-design-notes"><?php esc_html_e( 'הערות נוספות', 'print-order-configurator-rtl' ); ?></label>
				<textarea id="poc-rtl-design-notes" name="poc_rtl[brief][notes]" rows="4"></textarea>

				<label for="poc-rtl-brief-files"><?php esc_html_e( 'לוגואים, תמונות וקבצי השראה', 'print-order-configurator-rtl' ); ?></label>
				<input id="poc-rtl-brief-files" type="file" name="poc_rtl_brief_files[]"<?php echo $multiple_uploads ? ' multiple' : ''; ?> accept="<?php echo esc_attr( $accept ); ?>">
			</div>
		</section>
		<?php
	}

	/**
	 * Render select field if product options exist.
	 *
	 * @param string               $field Field key.
	 * @param string               $label Field label.
	 * @param array<string, mixed> $config Product config.
	 */
	private function render_select( string $field, string $label, array $config ): void {
		$options = $config[ $field ] ?? array();

		if ( ! is_array( $options ) || empty( $options ) ) {
			return;
		}
		?>
		<label class="poc-rtl-field">
			<span><?php echo esc_html( $label ); ?></span>
			<select name="poc_rtl[options][<?php echo esc_attr( $field ); ?>]">
				<option value=""><?php esc_html_e( 'בחרו אפשרות', 'print-order-configurator-rtl' ); ?></option>
				<?php foreach ( $options as $option ) : ?>
					<option value="<?php echo esc_attr( (string) $option ); ?>"><?php echo esc_html( (string) $option ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<?php
	}

	/**
	 * Render text input.
	 *
	 * @param string $field Field key.
	 * @param string $label Field label.
	 * @param string $type Input type.
	 */
	private function render_text_input( string $field, string $label, string $type = 'text' ): void {
		?>
		<label class="poc-rtl-field">
			<span><?php echo esc_html( $label ); ?></span>
			<input type="<?php echo esc_attr( $type ); ?>" name="poc_rtl[brief][<?php echo esc_attr( $field ); ?>]">
		</label>
		<?php
	}

	/**
	 * Build file input accept attribute from product config.
	 *
	 * @param array<string, mixed> $config Product config.
	 */
	private function allowed_accept_attribute( array $config ): string {
		$extensions = isset( $config['allowed_extensions'] ) && is_array( $config['allowed_extensions'] )
			? $config['allowed_extensions']
			: array( 'pdf', 'ai', 'psd', 'eps', 'jpg', 'jpeg', 'png', 'zip' );

		$extensions = array_map(
			static function ( mixed $extension ): string {
				$extension = ltrim( sanitize_key( (string) $extension ), '.' );

				return '' === $extension ? '' : '.' . $extension;
			},
			$extensions
		);

		return implode( ',', array_filter( $extensions ) );
	}
}
