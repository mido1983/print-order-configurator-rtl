<?php
/**
 * Admin order panel.
 *
 * @package PrintOrderConfiguratorRTL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin order service.
 */
final class POC_RTL_Admin_Order {
	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_workflow_status' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin CSS on order screens.
	 */
	public function enqueue_admin_assets(): void {
		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->id, array( 'shop_order', 'woocommerce_page_wc-orders' ), true ) ) {
			return;
		}

		wp_enqueue_style(
			'poc-rtl-admin-order',
			POC_RTL_URL . 'assets/css/admin-order.css',
			array(),
			POC_RTL_VERSION
		);
	}

	/**
	 * Add order meta boxes for classic and HPOS order screens.
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'poc_rtl_order_panel',
			__( 'פרטי הזמנת הדפסה', 'print-order-configurator-rtl' ),
			array( $this, 'render_order_panel' ),
			'shop_order',
			'normal',
			'high'
		);

		add_meta_box(
			'poc_rtl_order_panel',
			__( 'פרטי הזמנת הדפסה', 'print-order-configurator-rtl' ),
			array( $this, 'render_order_panel' ),
			'woocommerce_page_wc-orders',
			'normal',
			'high'
		);
	}

	/**
	 * Render order panel.
	 *
	 * @param WP_Post|WC_Order $post_or_order Post or order object.
	 */
	public function render_order_panel( WP_Post|WC_Order $post_or_order ): void {
		$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		wp_nonce_field( 'poc_rtl_save_order_panel', 'poc_rtl_order_panel_nonce' );

		$current_status = POC_RTL_Statuses::sanitize( (string) $order->get_meta( POC_RTL_Statuses::ORDER_META_KEY ) );
		$direction      = (string) POC_RTL_Settings::get( 'pocrtl_admin_panel_direction', 'rtl' );
		?>
		<div class="poc-rtl-admin-order" dir="<?php echo esc_attr( $direction ); ?>" lang="he">
			<?php if ( POC_RTL_Settings::enabled( 'pocrtl_enable_internal_status' ) ) : ?>
				<label class="poc-rtl-admin-status" for="poc-rtl-workflow-status">
					<span><?php esc_html_e( 'סטטוס פנימי', 'print-order-configurator-rtl' ); ?></span>
					<select id="poc-rtl-workflow-status" name="poc_rtl_workflow_status">
						<?php foreach ( POC_RTL_Statuses::all() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_status, $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>

			<?php
			$has_data = false;

			foreach ( $order->get_items() as $item ) {
				$data = $item->get_meta( POC_RTL_Order::ORDER_ITEM_META, true );

				if ( ! is_array( $data ) ) {
					continue;
				}

				$has_data = true;
				$this->render_item_data( $item, $data );
			}

			if ( ! $has_data ) :
				?>
				<p><?php esc_html_e( 'לא נמצאו פרטי קונפיגורטור להזמנה זו.', 'print-order-configurator-rtl' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Save workflow status.
	 *
	 * @param int $order_id Order ID.
	 */
	public function save_workflow_status( int $order_id ): void {
		if ( ! isset( $_POST['poc_rtl_order_panel_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['poc_rtl_order_panel_nonce'] ) ), 'poc_rtl_save_order_panel' )
		) {
			return;
		}

		if ( ! current_user_can( 'edit_shop_order', $order_id ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( ! POC_RTL_Settings::enabled( 'pocrtl_enable_internal_status' ) ) {
			return;
		}

		$status = isset( $_POST['poc_rtl_workflow_status'] )
			? POC_RTL_Statuses::sanitize( (string) wp_unslash( $_POST['poc_rtl_workflow_status'] ) )
			: 'files_received';

		$order->update_meta_data( POC_RTL_Statuses::ORDER_META_KEY, $status );
		$order->save();
	}

	/**
	 * Render one order item configurator block.
	 *
	 * @param WC_Order_Item_Product $item Order item.
	 * @param array<string, mixed>  $data Configurator data.
	 */
	private function render_item_data( WC_Order_Item_Product $item, array $data ): void {
		?>
		<section class="poc-rtl-admin-item">
			<h3><?php echo esc_html( $item->get_name() ); ?></h3>
			<p>
				<strong><?php esc_html_e( 'מצב עיצוב:', 'print-order-configurator-rtl' ); ?></strong>
				<?php echo esc_html( 'need_design' === ( $data['design_mode'] ?? '' ) ? __( 'צריך עיצוב מהדפוס', 'print-order-configurator-rtl' ) : __( 'יש עיצוב מוכן', 'print-order-configurator-rtl' ) ); ?>
			</p>

			<?php $this->render_key_values( __( 'אפשרויות הדפסה', 'print-order-configurator-rtl' ), $this->option_labels(), $data['options'] ?? array() ); ?>

			<?php if ( ! empty( $data['production_notes'] ) ) : ?>
				<h4><?php esc_html_e( 'הערות להפקה', 'print-order-configurator-rtl' ); ?></h4>
				<p class="poc-rtl-admin-text"><?php echo nl2br( esc_html( (string) $data['production_notes'] ) ); ?></p>
			<?php endif; ?>

			<?php
			if ( 'need_design' === ( $data['design_mode'] ?? '' ) ) {
				$this->render_key_values( __( 'בריף לעיצוב', 'print-order-configurator-rtl' ), $this->brief_labels(), $data['brief'] ?? array() );
			}

			$this->render_files( $data['files'] ?? array() );
			?>
		</section>
		<?php
	}

	/**
	 * Render key/value list.
	 *
	 * @param string               $title Section title.
	 * @param array<string,string> $labels Labels.
	 * @param mixed                $values Values.
	 */
	private function render_key_values( string $title, array $labels, mixed $values ): void {
		if ( ! is_array( $values ) ) {
			return;
		}

		$rows = array_filter(
			$values,
			static fn( $value ): bool => '' !== trim( (string) $value )
		);

		if ( empty( $rows ) ) {
			return;
		}
		?>
		<h4><?php echo esc_html( $title ); ?></h4>
		<dl class="poc-rtl-admin-list">
			<?php foreach ( $labels as $field => $label ) : ?>
				<?php if ( empty( $rows[ $field ] ) ) : ?>
					<?php continue; ?>
				<?php endif; ?>
				<dt><?php echo esc_html( $label ); ?></dt>
				<dd><?php echo nl2br( esc_html( (string) $rows[ $field ] ) ); ?></dd>
			<?php endforeach; ?>
		</dl>
		<?php
	}

	/**
	 * Render uploaded files.
	 *
	 * @param mixed $files Files payload.
	 */
	private function render_files( mixed $files ): void {
		if ( ! is_array( $files ) || empty( $files ) ) {
			return;
		}
		?>
		<h4><?php esc_html_e( 'קבצים', 'print-order-configurator-rtl' ); ?></h4>
		<ul class="poc-rtl-admin-files">
			<?php foreach ( $files as $group => $group_files ) : ?>
				<?php if ( ! is_array( $group_files ) ) : ?>
					<?php continue; ?>
				<?php endif; ?>
				<?php foreach ( $group_files as $file ) : ?>
					<?php
					if ( ! is_array( $file ) || empty( $file['path'] ) || empty( $file['original_name'] ) ) {
						continue;
					}
					?>
					<li>
						<span><?php echo esc_html( 'ready' === $group ? __( 'קובץ הדפסה', 'print-order-configurator-rtl' ) : __( 'קובץ עיצוב', 'print-order-configurator-rtl' ) ); ?></span>
						<?php $download_url = POC_RTL_Upload_Handler::build_download_url( (string) $file['path'] ); ?>
						<?php if ( '' !== $download_url ) : ?>
							<a href="<?php echo esc_url( $download_url ); ?>">
								<?php echo esc_html( (string) $file['original_name'] ); ?>
							</a>
						<?php else : ?>
							<span><?php echo esc_html( (string) $file['original_name'] ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Product option labels.
	 *
	 * @return array<string, string>
	 */
	private function option_labels(): array {
		return array(
			'sizes'             => __( 'גודל', 'print-order-configurator-rtl' ),
			'quantities'        => __( 'כמות', 'print-order-configurator-rtl' ),
			'paper_types'       => __( 'סוג נייר', 'print-order-configurator-rtl' ),
			'paper_weights'     => __( 'משקל נייר', 'print-order-configurator-rtl' ),
			'print_sides'       => __( 'צדדי הדפסה', 'print-order-configurator-rtl' ),
			'lamination'        => __( 'למינציה', 'print-order-configurator-rtl' ),
			'corners'           => __( 'פינות', 'print-order-configurator-rtl' ),
			'finishing_options' => __( 'גימור', 'print-order-configurator-rtl' ),
		);
	}

	/**
	 * Design brief labels.
	 *
	 * @return array<string, string>
	 */
	private function brief_labels(): array {
		return array(
			'business_name'     => __( 'שם העסק', 'print-order-configurator-rtl' ),
			'phone'             => __( 'טלפון', 'print-order-configurator-rtl' ),
			'email'             => __( 'אימייל', 'print-order-configurator-rtl' ),
			'address'           => __( 'כתובת', 'print-order-configurator-rtl' ),
			'preferred_colors'  => __( 'צבעים מועדפים', 'print-order-configurator-rtl' ),
			'style'             => __( 'סגנון', 'print-order-configurator-rtl' ),
			'design_text'       => __( 'טקסט לעיצוב', 'print-order-configurator-rtl' ),
			'references'        => __( 'רפרנסים והשראה', 'print-order-configurator-rtl' ),
			'notes'             => __( 'הערות נוספות', 'print-order-configurator-rtl' ),
		);
	}
}
