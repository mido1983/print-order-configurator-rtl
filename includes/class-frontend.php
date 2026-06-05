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
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'single_add_to_cart_text' ) );
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
		<section class="poc-rtl-configurator pocrtl-configurator pocrtl-configurator-panel" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>" aria-labelledby="poc-rtl-title">
			<?php wp_nonce_field( 'poc_rtl_add_to_cart', 'poc_rtl_nonce' ); ?>
			<div class="poc-rtl-configurator-header">
				<h2 id="poc-rtl-title" class="poc-rtl-title"><?php echo poc_rtl_esc_html( 'הגדרת הזמנת הדפסה', 'Configure your print order', 'site' ); ?></h2>
				<p><?php echo poc_rtl_esc_html( 'בחרו אפשרויות, העלו קבצים והוסיפו לעגלה בצורה מסודרת.', 'Choose options, upload files, and add the order to cart.', 'site' ); ?></p>
			</div>

			<div class="poc-rtl-section">
				<h3><?php echo poc_rtl_esc_html( 'אפשרויות הדפסה', 'Printing options', 'site' ); ?></h3>
				<div class="poc-rtl-grid">
				<?php
				$this->render_select( 'sizes', __( 'גודל', 'print-order-configurator-rtl' ), $config );
				$this->render_quantity_buttons( 'quantities', __( 'כמות', 'print-order-configurator-rtl' ), $config );
				$this->render_select( 'paper_types', __( 'סוג נייר', 'print-order-configurator-rtl' ), $config );
				$this->render_select( 'paper_weights', __( 'משקל נייר', 'print-order-configurator-rtl' ), $config );
				$this->render_select( 'print_sides', __( 'צדדי הדפסה', 'print-order-configurator-rtl' ), $config );
				$this->render_select( 'lamination', __( 'למינציה', 'print-order-configurator-rtl' ), $config );
				$this->render_select( 'corners', __( 'פינות', 'print-order-configurator-rtl' ), $config );
				$this->render_select( 'finishing_options', __( 'גימור', 'print-order-configurator-rtl' ), $config );
				?>
				</div>
			</div>

			<fieldset class="poc-rtl-section poc-rtl-workflow">
				<legend><?php echo poc_rtl_esc_html( 'מה מצב העיצוב?', 'What is the design status?', 'site' ); ?></legend>

				<label class="poc-rtl-choice pocrtl-design-mode-card">
					<input type="radio" name="poc_rtl[design_mode]" value="ready" checked>
					<span class="poc-rtl-choice-body">
						<strong><?php echo esc_html( (string) POC_RTL_Settings::get( 'pocrtl_label_ready_design', __( 'יש לי עיצוב מוכן', 'print-order-configurator-rtl' ) ) ); ?></strong>
						<small><?php echo poc_rtl_esc_html( 'העלו PDF, תמונות או קבצי עבודה מוכנים.', 'Upload print-ready files or working files.', 'site' ); ?></small>
					</span>
				</label>

				<?php if ( POC_RTL_Settings::enabled( 'pocrtl_design_service_enabled_global' ) && ! empty( $config['design_service_enabled'] ) ) : ?>
					<label class="poc-rtl-choice pocrtl-design-mode-card">
						<input type="radio" name="poc_rtl[design_mode]" value="need_design">
						<span class="poc-rtl-choice-body">
							<strong><?php echo esc_html( (string) POC_RTL_Settings::get( 'pocrtl_label_need_design', __( 'אין לי עיצוב - אני צריך עיצוב מהדפוס', 'print-order-configurator-rtl' ) ) ); ?></strong>
							<small><?php echo poc_rtl_esc_html( 'מלאו בריף קצר והצוות יכין את העיצוב ידנית.', 'Fill a short brief and the team will design it manually.', 'site' ); ?></small>
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

			<div class="poc-rtl-section poc-rtl-panel" data-poc-rtl-panel="ready">
				<h3><?php echo poc_rtl_esc_html( 'העלאת קבצים', 'Upload files', 'site' ); ?></h3>
				<label for="poc-rtl-production-notes"><?php echo poc_rtl_esc_html( 'הערות להפקה', 'Production notes', 'site' ); ?></label>
				<textarea id="poc-rtl-production-notes" name="poc_rtl[production_notes]" rows="4" placeholder="<?php echo poc_rtl_esc_attr( 'לדוגמה: שלום Michael Design 054-1234567', 'Example: Shalom Michael Design 054-1234567', 'site' ); ?>"></textarea>

				<?php
				$this->render_upload_control(
					'poc-rtl-ready-files',
					'poc_rtl_ready_files[]',
					poc_rtl_ui_text( 'קבצים מוכנים להדפסה', 'Ready print files', 'site' ),
					poc_rtl_ui_text( 'גררו קבצים לכאן או לחצו להעלאה', 'Drag files here or click to upload', 'site' ),
					$accept,
					$multiple_uploads
				);
				?>
			</div>

			<div class="poc-rtl-section poc-rtl-panel" data-poc-rtl-panel="need_design" hidden>
				<h3><?php echo poc_rtl_esc_html( 'בריף לעיצוב', 'Design brief', 'site' ); ?></h3>
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

				<label for="poc-rtl-design-text"><?php echo poc_rtl_esc_html( 'טקסט לעיצוב', 'Design text', 'site' ); ?></label>
				<textarea id="poc-rtl-design-text" name="poc_rtl[brief][design_text]" rows="5"></textarea>

				<label for="poc-rtl-design-references"><?php echo poc_rtl_esc_html( 'רפרנסים והשראה', 'References and inspiration', 'site' ); ?></label>
				<textarea id="poc-rtl-design-references" name="poc_rtl[brief][references]" rows="4"></textarea>

				<label for="poc-rtl-design-notes"><?php echo poc_rtl_esc_html( 'הערות נוספות', 'Additional notes', 'site' ); ?></label>
				<textarea id="poc-rtl-design-notes" name="poc_rtl[brief][notes]" rows="4"></textarea>

				<?php
				$this->render_upload_control(
					'poc-rtl-brief-files',
					'poc_rtl_brief_files[]',
					poc_rtl_ui_text( 'לוגואים, תמונות וקבצי השראה', 'Logos, photos, and reference files', 'site' ),
					poc_rtl_ui_text( 'גררו לוגואים ותמונות לכאן או לחצו להעלאה', 'Drag logos and images here or click to upload', 'site' ),
					$accept,
					$multiple_uploads
				);
				?>
			</div>
		</section>
		<?php
	}

	/**
	 * Use a Hebrew CTA when the site is Hebrew and this product uses the configurator.
	 *
	 * @param string $text Button text.
	 */
	public function single_add_to_cart_text( string $text ): string {
		global $product;

		if ( $product instanceof WC_Product && POC_RTL_Product_Settings::is_enabled( $product->get_id() ) && poc_rtl_is_hebrew_locale( 'site' ) ) {
			return __( 'הוסף לעגלה', 'print-order-configurator-rtl' );
		}

		return $text;
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
				<option value=""><?php echo poc_rtl_esc_html( 'בחרו אפשרות', 'Choose an option', 'site' ); ?></option>
				<?php foreach ( $options as $option ) : ?>
					<?php $option_label = poc_rtl_option_label( $option ); ?>
					<?php if ( '' === $option_label ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<option value="<?php echo esc_attr( $option_label ); ?>"><?php echo esc_html( $option_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<?php
	}

	/**
	 * Render quantity buttons.
	 *
	 * @param string               $field Field key.
	 * @param string               $label Field label.
	 * @param array<string, mixed> $config Product config.
	 */
	private function render_quantity_buttons( string $field, string $label, array $config ): void {
		$options = poc_rtl_option_labels( $config[ $field ] ?? array() );

		if ( empty( $options ) ) {
			return;
		}
		?>
		<div class="poc-rtl-field poc-rtl-quantity-field">
			<span><?php echo esc_html( $label ); ?></span>
			<div class="poc-rtl-option-buttons" role="radiogroup" aria-label="<?php echo esc_attr( $label ); ?>">
				<?php foreach ( $options as $index => $option ) : ?>
					<label class="poc-rtl-option-button">
						<input type="radio" name="poc_rtl[options][<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( $option ); ?>" <?php checked( 0, $index ); ?>>
						<span><?php echo esc_html( $option ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
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
	 * Render custom upload control.
	 *
	 * @param string $id Input ID.
	 * @param string $name Input name.
	 * @param string $label Control label.
	 * @param string $prompt Upload prompt.
	 * @param string $accept Accept attribute.
	 * @param bool   $multiple Whether multiple files are allowed.
	 */
	private function render_upload_control( string $id, string $name, string $label, string $prompt, string $accept, bool $multiple ): void {
		?>
		<div class="poc-rtl-upload pocrtl-upload-zone" data-poc-rtl-upload>
			<label class="poc-rtl-upload-label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<label class="poc-rtl-upload-dropzone" for="<?php echo esc_attr( $id ); ?>">
				<span class="poc-rtl-upload-icon" aria-hidden="true">+</span>
				<span class="poc-rtl-upload-prompt"><?php echo esc_html( $prompt ); ?></span>
				<span class="poc-rtl-upload-hint"><?php echo poc_rtl_esc_html( 'הקבצים שנבחרו יוצגו כאן לפני ההוספה לעגלה', 'Selected files will appear here before adding to cart', 'site' ); ?></span>
			</label>
			<input id="<?php echo esc_attr( $id ); ?>" class="poc-rtl-upload-input" type="file" name="<?php echo esc_attr( $name ); ?>"<?php echo $multiple ? ' multiple' : ''; ?> accept="<?php echo esc_attr( $accept ); ?>">
			<ul class="poc-rtl-upload-list" data-poc-rtl-upload-list aria-live="polite"></ul>
		</div>
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
