<?php
/**
 * Global plugin settings.
 *
 * @package PrintOrderConfiguratorRTL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings service.
 */
final class POC_RTL_Settings {
	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_post_pocrtl_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add submenu under WooCommerce.
	 */
	public function add_menu_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Print Configurator RTL', 'print-order-configurator-rtl' ),
			__( 'הגדרות הדפסה RTL', 'print-order-configurator-rtl' ),
			'manage_woocommerce',
			'pocrtl-settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin settings CSS.
	 */
	public function enqueue_assets(): void {
		$screen = get_current_screen();

		if ( ! $screen || 'woocommerce_page_pocrtl-settings' !== $screen->id ) {
			return;
		}

		wp_enqueue_style(
			'poc-rtl-admin-settings',
			POC_RTL_URL . 'assets/css/admin-settings.css',
			array(),
			POC_RTL_VERSION
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die(
				esc_html__( 'אין הרשאה לנהל את הגדרות התוסף.', 'print-order-configurator-rtl' ),
				esc_html__( 'אין הרשאה', 'print-order-configurator-rtl' ),
				array( 'response' => 403 )
			);
		}

		$settings = self::all();
		$server   = $this->server_limits();
		?>
		<div class="wrap poc-rtl-settings" dir="rtl" lang="he">
			<h1><?php esc_html_e( 'הגדרות הדפסה RTL', 'print-order-configurator-rtl' ); ?></h1>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pocrtl_save_settings">
				<?php wp_nonce_field( 'pocrtl_save_settings', 'pocrtl_settings_nonce' ); ?>

				<?php $this->render_general_section( $settings ); ?>
				<?php $this->render_upload_section( $settings, $server ); ?>
				<?php $this->render_design_section( $settings ); ?>
				<?php $this->render_workflow_section( $settings ); ?>
				<?php $this->render_display_section( $settings ); ?>
				<?php $this->render_security_section( $settings ); ?>
				<?php $this->render_github_updates_section( $settings ); ?>

				<?php submit_button( __( 'שמור הגדרות', 'print-order-configurator-rtl' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Save settings.
	 */
	public function save_settings(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die(
				esc_html__( 'אין הרשאה לנהל את הגדרות התוסף.', 'print-order-configurator-rtl' ),
				esc_html__( 'אין הרשאה', 'print-order-configurator-rtl' ),
				array( 'response' => 403 )
			);
		}

		check_admin_referer( 'pocrtl_save_settings', 'pocrtl_settings_nonce' );

		$posted = wp_unslash( $_POST );

		$allow_svg = ! empty( $posted['pocrtl_allow_svg_uploads'] );

		foreach ( self::defaults() as $key => $default ) {
			update_option( $key, $this->sanitize_setting( $key, $posted[ $key ] ?? null, $default, $allow_svg ) );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'pocrtl-settings',
					'pocrtl_saved'  => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render admin notices.
	 */
	public function render_admin_notices(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) || empty( $_GET['pocrtl_saved'] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'הגדרות Print Configurator RTL נשמרו.', 'print-order-configurator-rtl' )
		);
	}

	/**
	 * Get all settings with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$settings = array();

		foreach ( self::defaults() as $key => $default ) {
			$settings[ $key ] = get_option( $key, $default );
		}

		return $settings;
	}

	/**
	 * Get one setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $fallback Optional fallback.
	 * @return mixed
	 */
	public static function get( string $key, mixed $fallback = null ): mixed {
		$defaults = self::defaults();

		return get_option( $key, $defaults[ $key ] ?? $fallback );
	}

	/**
	 * Return whether a checkbox-like setting is enabled.
	 *
	 * @param string $key Setting key.
	 */
	public static function enabled( string $key ): bool {
		return 'yes' === self::get( $key, 'no' );
	}

	/**
	 * Global plugin enabled flag.
	 */
	public static function is_global_enabled(): bool {
		return self::enabled( 'pocrtl_enabled_global' );
	}

	/**
	 * Default allowed extensions as array.
	 *
	 * @return array<int, string>
	 */
	public static function default_allowed_extensions(): array {
		return self::sanitize_extensions( (string) self::get( 'pocrtl_default_allowed_file_types' ), self::enabled( 'pocrtl_allow_svg_uploads' ) );
	}

	/**
	 * Upload directory name.
	 */
	public static function upload_directory(): string {
		$directory = sanitize_file_name( (string) self::get( 'pocrtl_upload_directory', 'print-order-configurator' ) );

		return '' === $directory ? 'print-order-configurator' : $directory;
	}

	/**
	 * Settings defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'pocrtl_enabled_global'                 => 'yes',
			'pocrtl_default_direction'              => 'rtl',
			'pocrtl_default_interface_language'     => 'he',
			'pocrtl_default_allowed_file_types'     => 'pdf,ai,psd,eps,jpg,jpeg,png,zip',
			'pocrtl_default_max_file_size_mb'       => 100,
			'pocrtl_default_multiple_uploads'       => 'yes',
			'pocrtl_upload_directory'               => 'print-order-configurator',
			'pocrtl_design_service_enabled_global'  => 'yes',
			'pocrtl_default_design_service_price'   => '0',
			'pocrtl_label_ready_design'             => 'יש לי עיצוב מוכן',
			'pocrtl_label_need_design'              => 'אין לי עיצוב - אני צריך עיצוב מהדפוס',
			'pocrtl_enable_internal_status'         => 'yes',
			'pocrtl_workflow_statuses'              => "קבצים התקבלו\nממתין לעיצוב\nממתין לאישור לקוח\nמוכן להדפסה\nנשלח להדפסה",
			'pocrtl_show_config_in_cart'            => 'yes',
			'pocrtl_show_config_in_checkout'        => 'yes',
			'pocrtl_show_config_in_emails'          => 'no',
			'pocrtl_admin_panel_direction'          => 'rtl',
			'pocrtl_allow_svg_uploads'              => 'no',
			'pocrtl_protect_admin_downloads'        => 'yes',
			'pocrtl_github_updates_enabled'         => 'yes',
			'pocrtl_github_repo'                    => POCRTL_GITHUB_REPO,
			'pocrtl_github_update_channel'          => 'stable',
			'pocrtl_github_token'                   => '',
		);
	}

	/**
	 * Sanitize setting by key.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Raw value.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	private function sanitize_setting( string $key, mixed $value, mixed $default, bool $allow_svg ): mixed {
		$checkboxes = array(
			'pocrtl_enabled_global',
			'pocrtl_default_multiple_uploads',
			'pocrtl_design_service_enabled_global',
			'pocrtl_enable_internal_status',
			'pocrtl_show_config_in_cart',
			'pocrtl_show_config_in_checkout',
			'pocrtl_show_config_in_emails',
			'pocrtl_allow_svg_uploads',
			'pocrtl_protect_admin_downloads',
			'pocrtl_github_updates_enabled',
		);

		if ( in_array( $key, $checkboxes, true ) ) {
			return null === $value ? 'no' : 'yes';
		}

		return match ( $key ) {
			'pocrtl_default_direction' => in_array( $value, array( 'rtl', 'ltr' ), true ) ? $value : $default,
			'pocrtl_admin_panel_direction' => in_array( $value, array( 'rtl', 'ltr', 'auto' ), true ) ? $value : $default,
			'pocrtl_default_interface_language' => sanitize_key( (string) $value ) ?: $default,
			'pocrtl_default_allowed_file_types' => implode( ',', self::sanitize_extensions( (string) $value, $allow_svg ) ),
			'pocrtl_default_max_file_size_mb' => max( 1, absint( $value ) ),
			'pocrtl_default_design_service_price' => wc_format_decimal( $value ?? 0 ),
			'pocrtl_upload_directory' => sanitize_file_name( (string) $value ) ?: $default,
			'pocrtl_workflow_statuses' => implode( "\n", poc_rtl_sanitize_option_lines( (string) $value ) ),
			'pocrtl_github_repo' => $this->sanitize_github_repo( (string) $value, (string) $default ),
			'pocrtl_github_update_channel' => 'stable',
			'pocrtl_github_token' => '' === (string) $value ? (string) get_option( 'pocrtl_github_token', '' ) : sanitize_text_field( (string) $value ),
			default => sanitize_text_field( (string) $value ),
		};
	}

	/**
	 * Sanitize extension list.
	 *
	 * @param string $raw Raw extension list.
	 * @return array<int, string>
	 */
	private static function sanitize_extensions( string $raw, bool $allow_svg ): array {
		$dangerous = array( 'php', 'phtml', 'phar', 'exe', 'js', 'sh', 'bat', 'cmd', 'com', 'scr' );

		if ( ! $allow_svg ) {
			$dangerous[] = 'svg';
		}

		$parts = preg_split( '/[\s,]+/', $raw );

		if ( false === $parts ) {
			return array( 'pdf', 'ai', 'psd', 'eps', 'jpg', 'jpeg', 'png', 'zip' );
		}

		$clean = array();

		foreach ( $parts as $part ) {
			$extension = strtolower( sanitize_key( ltrim( $part, '.' ) ) );

			if ( '' !== $extension && ! in_array( $extension, $dangerous, true ) ) {
				$clean[] = $extension;
			}
		}

		$clean = array_values( array_unique( $clean ) );

		return empty( $clean ) ? array( 'pdf', 'ai', 'psd', 'eps', 'jpg', 'jpeg', 'png', 'zip' ) : $clean;
	}

	/**
	 * Render general section.
	 *
	 * @param array<string, mixed> $settings Settings.
	 */
	private function render_general_section( array $settings ): void {
		$this->open_section( __( 'הגדרות כלליות', 'print-order-configurator-rtl' ) );
		$this->checkbox( 'pocrtl_enabled_global', __( 'הפעל תוסף גלובלית', 'print-order-configurator-rtl' ), $settings );
		$this->select( 'pocrtl_default_direction', __( 'כיוון ממשק ברירת מחדל', 'print-order-configurator-rtl' ), $settings, array( 'rtl' => 'RTL', 'ltr' => 'LTR' ) );
		$this->text( 'pocrtl_default_interface_language', __( 'שפת ממשק ברירת מחדל', 'print-order-configurator-rtl' ), $settings );
		$this->close_section();
	}

	/**
	 * Render upload section.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param array<string, mixed> $server Server limits.
	 */
	private function render_upload_section( array $settings, array $server ): void {
		$this->open_section( __( 'הגדרות העלאה', 'print-order-configurator-rtl' ) );
		$this->text( 'pocrtl_default_allowed_file_types', __( 'סוגי קבצים מותרים כברירת מחדל', 'print-order-configurator-rtl' ), $settings, __( 'SVG אינו מופעל כברירת מחדל.', 'print-order-configurator-rtl' ) );
		$this->number( 'pocrtl_default_max_file_size_mb', __( 'גודל קובץ מקסימלי MB', 'print-order-configurator-rtl' ), $settings, 1, 1 );
		$this->checkbox( 'pocrtl_default_multiple_uploads', __( 'אפשר העלאת מספר קבצים כברירת מחדל', 'print-order-configurator-rtl' ), $settings );
		$this->text( 'pocrtl_upload_directory', __( 'שם תיקיית העלאות', 'print-order-configurator-rtl' ), $settings );
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'מגבלות העלאה בשרת', 'print-order-configurator-rtl' ); ?></th>
			<td>
				<p><?php echo esc_html( 'upload_max_filesize: ' . $server['upload_max_filesize'] ); ?></p>
				<p><?php echo esc_html( 'post_max_size: ' . $server['post_max_size'] ); ?></p>
				<p><?php echo esc_html( 'max_file_uploads: ' . $server['max_file_uploads'] ); ?></p>
				<?php if ( $this->max_file_size_exceeds_server( (int) $settings['pocrtl_default_max_file_size_mb'], $server ) ) : ?>
					<p class="notice notice-warning inline"><?php esc_html_e( 'אזהרה: מגבלת התוסף גבוהה ממגבלת ההעלאה של השרת.', 'print-order-configurator-rtl' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
		$this->close_section();
	}

	/**
	 * Render design service section.
	 *
	 * @param array<string, mixed> $settings Settings.
	 */
	private function render_design_section( array $settings ): void {
		$this->open_section( __( 'הגדרות שירות עיצוב', 'print-order-configurator-rtl' ) );
		$this->checkbox( 'pocrtl_design_service_enabled_global', __( 'הפעל שירות עיצוב גלובלית', 'print-order-configurator-rtl' ), $settings );
		$this->number( 'pocrtl_default_design_service_price', __( 'מחיר ברירת מחדל לשירות עיצוב', 'print-order-configurator-rtl' ), $settings, 0, 0.01 );
		$this->text( 'pocrtl_label_ready_design', __( 'תווית ברירת מחדל - יש עיצוב מוכן', 'print-order-configurator-rtl' ), $settings );
		$this->text( 'pocrtl_label_need_design', __( 'תווית ברירת מחדל - צריך עיצוב', 'print-order-configurator-rtl' ), $settings );
		$this->close_section();
	}

	/**
	 * Render workflow section.
	 *
	 * @param array<string, mixed> $settings Settings.
	 */
	private function render_workflow_section( array $settings ): void {
		$this->open_section( __( 'הגדרות Workflow', 'print-order-configurator-rtl' ) );
		$this->checkbox( 'pocrtl_enable_internal_status', __( 'הפעל סטטוס פנימי', 'print-order-configurator-rtl' ), $settings );
		$this->textarea( 'pocrtl_workflow_statuses', __( 'סטטוסים פנימיים', 'print-order-configurator-rtl' ), $settings, __( 'סטטוס אחד בכל שורה.', 'print-order-configurator-rtl' ) );
		$this->close_section();
	}

	/**
	 * Render display section.
	 *
	 * @param array<string, mixed> $settings Settings.
	 */
	private function render_display_section( array $settings ): void {
		$this->open_section( __( 'הגדרות תצוגה', 'print-order-configurator-rtl' ) );
		$this->checkbox( 'pocrtl_show_config_in_cart', __( 'הצג קונפיגורציה בעגלה', 'print-order-configurator-rtl' ), $settings );
		$this->checkbox( 'pocrtl_show_config_in_checkout', __( 'הצג קונפיגורציה בקופה', 'print-order-configurator-rtl' ), $settings );
		$this->checkbox( 'pocrtl_show_config_in_emails', __( 'הצג קונפיגורציה באימיילים ללקוח', 'print-order-configurator-rtl' ), $settings );
		$this->select( 'pocrtl_admin_panel_direction', __( 'כיוון פאנל ניהול', 'print-order-configurator-rtl' ), $settings, array( 'rtl' => 'RTL', 'ltr' => 'LTR', 'auto' => 'Auto' ) );
		$this->close_section();
	}

	/**
	 * Render security section.
	 *
	 * @param array<string, mixed> $settings Settings.
	 */
	private function render_security_section( array $settings ): void {
		$this->open_section( __( 'הגדרות אבטחה', 'print-order-configurator-rtl' ) );
		$this->checkbox( 'pocrtl_allow_svg_uploads', __( 'אפשר העלאת SVG', 'print-order-configurator-rtl' ), $settings, __( 'SVG files can contain unsafe content. Enable only if you sanitize SVG uploads or trust your workflow.', 'print-order-configurator-rtl' ) );
		$this->checkbox( 'pocrtl_protect_admin_downloads', __( 'הגן על הורדות מנהל', 'print-order-configurator-rtl' ), $settings );
		$this->close_section();
	}

	/**
	 * Render GitHub updates section.
	 *
	 * @param array<string, mixed> $settings Settings.
	 */
	private function render_github_updates_section( array $settings ): void {
		$this->open_section( __( 'GitHub Updates', 'print-order-configurator-rtl' ) );
		$this->checkbox( 'pocrtl_github_updates_enabled', __( 'Enable GitHub updates', 'print-order-configurator-rtl' ), $settings, __( 'Check GitHub releases and show WordPress plugin updates when a newer release is available.', 'print-order-configurator-rtl' ) );
		$this->text( 'pocrtl_github_repo', __( 'GitHub repository', 'print-order-configurator-rtl' ), $settings, __( 'Format: owner/repository. Public repositories do not need a token.', 'print-order-configurator-rtl' ) );
		$this->select( 'pocrtl_github_update_channel', __( 'Update channel', 'print-order-configurator-rtl' ), $settings, array( 'stable' => 'stable' ) );
		$this->password( 'pocrtl_github_token', __( 'GitHub token', 'print-order-configurator-rtl' ), __( 'Optional. Use only for private repositories. The token is stored but never displayed.', 'print-order-configurator-rtl' ) );
		$this->close_section();
	}

	/**
	 * Open a settings section.
	 *
	 * @param string $title Section title.
	 */
	private function open_section( string $title ): void {
		?>
		<section class="poc-rtl-settings-section">
			<h2><?php echo esc_html( $title ); ?></h2>
			<table class="form-table" role="presentation">
		<?php
	}

	/**
	 * Close a settings section.
	 */
	private function close_section(): void {
		?>
			</table>
		</section>
		<?php
	}

	/**
	 * Render checkbox field.
	 *
	 * @param string               $key Setting key.
	 * @param string               $label Label.
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $description Optional description.
	 */
	private function checkbox( string $key, string $label, array $settings, string $description = '' ): void {
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="yes" <?php checked( 'yes', $settings[ $key ] ?? 'no' ); ?>>
					<?php esc_html_e( 'מופעל', 'print-order-configurator-rtl' ); ?>
				</label>
				<?php $this->description( $description ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render text field.
	 *
	 * @param string               $key Setting key.
	 * @param string               $label Label.
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $description Optional description.
	 */
	private function text( string $key, string $label, array $settings, string $description = '' ): void {
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input class="regular-text" id="<?php echo esc_attr( $key ); ?>" type="text" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( (string) ( $settings[ $key ] ?? '' ) ); ?>">
				<?php $this->description( $description ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render number field.
	 *
	 * @param string               $key Setting key.
	 * @param string               $label Label.
	 * @param array<string, mixed> $settings Settings.
	 * @param float|int            $min Minimum.
	 * @param float|int            $step Step.
	 */
	private function number( string $key, string $label, array $settings, float|int $min, float|int $step ): void {
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input id="<?php echo esc_attr( $key ); ?>" type="number" name="<?php echo esc_attr( $key ); ?>" min="<?php echo esc_attr( (string) $min ); ?>" step="<?php echo esc_attr( (string) $step ); ?>" value="<?php echo esc_attr( (string) ( $settings[ $key ] ?? '' ) ); ?>">
			</td>
		</tr>
		<?php
	}

	/**
	 * Render select field.
	 *
	 * @param string               $key Setting key.
	 * @param string               $label Label.
	 * @param array<string, mixed> $settings Settings.
	 * @param array<string,string> $options Options.
	 */
	private function select( string $key, string $label, array $settings, array $options ): void {
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<select id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>">
					<?php foreach ( $options as $value => $option_label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings[ $key ] ?? '', $value ); ?>>
							<?php echo esc_html( $option_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render textarea field.
	 *
	 * @param string               $key Setting key.
	 * @param string               $label Label.
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $description Optional description.
	 */
	private function textarea( string $key, string $label, array $settings, string $description = '' ): void {
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<textarea class="large-text" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" rows="6"><?php echo esc_textarea( (string) ( $settings[ $key ] ?? '' ) ); ?></textarea>
				<?php $this->description( $description ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render password field.
	 *
	 * @param string $key Setting key.
	 * @param string $label Label.
	 * @param string $description Description.
	 */
	private function password( string $key, string $label, string $description = '' ): void {
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input class="regular-text" id="<?php echo esc_attr( $key ); ?>" type="password" name="<?php echo esc_attr( $key ); ?>" value="" autocomplete="new-password" placeholder="<?php echo esc_attr__( 'Leave blank to keep existing token', 'print-order-configurator-rtl' ); ?>">
				<?php $this->description( $description ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Sanitize GitHub repository setting.
	 *
	 * @param string $repo Raw repository.
	 * @param string $default Default repository.
	 */
	private function sanitize_github_repo( string $repo, string $default ): string {
		$repo = trim( $repo );

		return preg_match( '/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repo ) ? $repo : $default;
	}

	/**
	 * Render description.
	 *
	 * @param string $description Description.
	 */
	private function description( string $description ): void {
		if ( '' === $description ) {
			return;
		}

		printf( '<p class="description">%s</p>', esc_html( $description ) );
	}

	/**
	 * Server upload limits.
	 *
	 * @return array<string, mixed>
	 */
	private function server_limits(): array {
		return array(
			'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
			'post_max_size'       => ini_get( 'post_max_size' ),
			'max_file_uploads'    => ini_get( 'max_file_uploads' ),
		);
	}

	/**
	 * Check whether plugin max exceeds server upload/post limit.
	 *
	 * @param int                  $plugin_max_mb Plugin max MB.
	 * @param array<string, mixed> $server Server limits.
	 */
	private function max_file_size_exceeds_server( int $plugin_max_mb, array $server ): bool {
		$upload = $this->size_to_mb( (string) $server['upload_max_filesize'] );
		$post   = $this->size_to_mb( (string) $server['post_max_size'] );
		$limit  = min( $upload, $post );

		return $limit > 0 && $plugin_max_mb > $limit;
	}

	/**
	 * Convert PHP shorthand size to MB.
	 *
	 * @param string $size Size string.
	 */
	private function size_to_mb( string $size ): int {
		$size = trim( $size );
		$unit = strtolower( substr( $size, -1 ) );
		$num  = (float) $size;

		return match ( $unit ) {
			'g' => (int) ( $num * 1024 ),
			'k' => (int) ceil( $num / 1024 ),
			default => (int) $num,
		};
	}
}
