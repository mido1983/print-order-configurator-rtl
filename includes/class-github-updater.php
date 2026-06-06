<?php
/**
 * GitHub release updater.
 *
 * @package PrintOrderConfiguratorRTL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GitHub updater service.
 */
final class POC_RTL_GitHub_Updater {
	private const SLUG = 'print-order-configurator-rtl';
	private const CACHE_KEY = 'pocrtl_github_update_cache';
	private const STATUS_OPTION = 'pocrtl_github_update_status';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_information' ), 10, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'fix_install_folder' ), 10, 3 );
		add_filter( 'plugin_action_links_' . POCRTL_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
		add_action( 'admin_post_pocrtl_check_updates', array( $this, 'handle_check_updates' ) );
		add_action( 'admin_post_pocrtl_clear_update_cache', array( $this, 'handle_clear_update_cache' ) );
	}

	/**
	 * Add update data to WordPress plugin update transient.
	 *
	 * @param mixed $transient Update transient.
	 * @return mixed
	 */
	public function check_for_update( mixed $transient ): mixed {
		if ( ! is_object( $transient ) || ! $this->updates_enabled() ) {
			return $transient;
		}

		$release = $this->latest_release();

		if ( null === $release || ! version_compare( $release['version'], POCRTL_VERSION, '>' ) ) {
			return $transient;
		}

		$transient->response[ POCRTL_PLUGIN_BASENAME ] = $this->update_object( $release );

		return $transient;
	}

	/**
	 * Provide plugin information for the update modal.
	 *
	 * @param mixed  $result Existing result.
	 * @param string $action API action.
	 * @param object $args API args.
	 * @return mixed
	 */
	public function plugin_information( mixed $result, string $action, object $args ): mixed {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || self::SLUG !== $args->slug || ! $this->updates_enabled() ) {
			return $result;
		}

		$release = $this->latest_release();

		if ( null === $release ) {
			return $result;
		}

		return (object) array(
			'name'          => 'Print Order Configurator RTL for WooCommerce',
			'slug'          => self::SLUG,
			'version'       => $release['version'],
			'author'        => 'Print Order Configurator RTL Contributors',
			'homepage'      => $release['url'],
			'download_link' => $release['package'],
			'sections'      => array(
				'description' => esc_html__( 'RTL-first structured print order workflow configurator for WooCommerce.', 'print-order-configurator-rtl' ),
				'changelog'   => wp_kses_post( wpautop( $release['body'] ) ),
			),
		);
	}

	/**
	 * Add settings and manual update links to the plugins page row.
	 *
	 * @param array<string,string> $links Plugin action links.
	 * @return array<string,string>
	 */
	public function plugin_action_links( array $links ): array {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			$settings_url = admin_url( 'admin.php?page=pocrtl-settings' );
			$check_url    = wp_nonce_url( admin_url( 'admin-post.php?action=pocrtl_check_updates' ), 'pocrtl_check_updates', 'pocrtl_update_nonce' );

			$links = array_merge(
				array(
					'settings' => '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'print-order-configurator-rtl' ) . '</a>',
					'check'    => '<a href="' . esc_url( $check_url ) . '">' . esc_html__( 'Check update', 'print-order-configurator-rtl' ) . '</a>',
				),
				$links
			);
		}

		return $links;
	}

	/**
	 * Manual update check admin action.
	 */
	public function handle_check_updates(): void {
		$this->assert_update_capability( 'pocrtl_check_updates' );
		$this->latest_release( true );
		delete_site_transient( 'update_plugins' );
		wp_update_plugins();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                  => 'pocrtl-settings',
					'pocrtl_update_checked' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Manual update cache clear admin action.
	 */
	public function handle_clear_update_cache(): void {
		$this->assert_update_capability( 'pocrtl_clear_update_cache' );
		self::clear_cache();
		delete_site_transient( 'update_plugins' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                 => 'pocrtl-settings',
					'pocrtl_cache_cleared' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Clear GitHub update cache and status.
	 */
	public static function clear_cache(): void {
		delete_site_transient( self::CACHE_KEY );
		delete_option( self::STATUS_OPTION );

		$repo = (string) POC_RTL_Settings::get( 'pocrtl_github_repo', POCRTL_GITHUB_REPO );
		delete_site_transient( 'pocrtl_github_release_' . md5( $repo ) );
	}

	/**
	 * Return stored update status for the settings page.
	 *
	 * @return array<string,mixed>
	 */
	public static function update_status(): array {
		$status = get_option( self::STATUS_OPTION, array() );

		return is_array( $status ) ? $status : array();
	}

	/**
	 * Force a fresh GitHub release check for settings display.
	 *
	 * @return array<string,string>|null
	 */
	public function manual_check(): ?array {
		return $this->latest_release( true );
	}

	/**
	 * Rename extracted GitHub ZIP folder to the plugin folder.
	 *
	 * @param bool|array<string,mixed> $response Install response.
	 * @param array<string,mixed>      $hook_extra Hook context.
	 * @param array<string,mixed>      $result Install result.
	 * @return bool|array<string,mixed>
	 */
	public function fix_install_folder( bool|array $response, array $hook_extra, array $result ): bool|array {
		if ( empty( $hook_extra['plugin'] ) || POCRTL_PLUGIN_BASENAME !== $hook_extra['plugin'] || empty( $result['destination'] ) ) {
			return $response;
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( ! $wp_filesystem ) {
			return $response;
		}

		$source      = trailingslashit( (string) $result['destination'] );
		$destination = trailingslashit( WP_PLUGIN_DIR ) . dirname( POCRTL_PLUGIN_BASENAME );

		if ( trailingslashit( $destination ) === $source ) {
			return $response;
		}

		if ( $wp_filesystem->exists( $destination ) ) {
			$wp_filesystem->delete( $destination, true );
		}

		$wp_filesystem->move( $source, $destination, true );

		if ( is_array( $response ) ) {
			$response['destination'] = $destination;
		}

		return $response;
	}

	/**
	 * Check whether updates are enabled.
	 */
	private function updates_enabled(): bool {
		return POC_RTL_Settings::enabled( 'pocrtl_github_updates_enabled' );
	}

	/**
	 * Build WordPress plugin update object.
	 *
	 * @param array<string,string> $release Release metadata.
	 */
	private function update_object( array $release ): object {
		return (object) array(
			'id'          => POCRTL_PLUGIN_BASENAME,
			'slug'        => self::SLUG,
			'plugin'      => POCRTL_PLUGIN_BASENAME,
			'new_version' => $release['version'],
			'url'         => $release['url'],
			'package'     => $release['package'],
			'tested'      => get_bloginfo( 'version' ),
			'requires'    => '6.5',
		);
	}

	/**
	 * Fetch latest GitHub release metadata.
	 *
	 * @return array<string,string>|null
	 */
	private function latest_release( bool $force = false ): ?array {
		$repo = (string) POC_RTL_Settings::get( 'pocrtl_github_repo', POCRTL_GITHUB_REPO );

		if ( ! preg_match( '/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repo ) ) {
			$repo = POCRTL_GITHUB_REPO;
		}

		$cache_key = self::CACHE_KEY;
		$cached    = get_site_transient( $cache_key );

		if ( ! $force && is_array( $cached ) ) {
			return $cached;
		}

		$request = array(
			'timeout' => 12,
			'headers' => array(
				'User-Agent' => 'Print-Order-Configurator-RTL/' . POCRTL_VERSION,
			),
		);

		$token = (string) POC_RTL_Settings::get( 'pocrtl_github_token', '' );

		if ( '' !== $token ) {
			$request['headers']['Authorization'] = 'Bearer ' . $token;
		}

		$repo_parts = explode( '/', $repo, 2 );
		$api_url    = 'https://api.github.com/repos/' . rawurlencode( $repo_parts[0] ) . '/' . rawurlencode( $repo_parts[1] ) . '/releases/latest';
		$response   = wp_remote_get( $api_url, $request );

		if ( is_wp_error( $response ) ) {
			$this->store_status(
				array(
					'ok'          => false,
					'api_url'     => $api_url,
					'http_status' => 0,
					'error'       => $response->get_error_message(),
					'checked_at'  => time(),
				)
			);
			delete_site_transient( $cache_key );
			return null;
		}

		$http_status = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $http_status ) {
			$this->store_status(
				array(
					'ok'          => false,
					'api_url'     => $api_url,
					'http_status' => $http_status,
					'error'       => wp_remote_retrieve_response_message( $response ) ?: __( 'GitHub API request failed.', 'print-order-configurator-rtl' ),
					'checked_at'  => time(),
				)
			);
			delete_site_transient( $cache_key );
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			$this->store_status(
				array(
					'ok'          => false,
					'api_url'     => $api_url,
					'http_status' => $http_status,
					'error'       => __( 'GitHub returned invalid release JSON.', 'print-order-configurator-rtl' ),
					'checked_at'  => time(),
				)
			);
			delete_site_transient( $cache_key );
			return null;
		}

		$package = $this->release_package_url( $data );

		if ( '' === $package ) {
			$this->store_status(
				array(
					'ok'          => false,
					'api_url'     => $api_url,
					'http_status' => $http_status,
					'raw_tag'     => (string) $data['tag_name'],
					'version'     => $this->normalize_version( (string) $data['tag_name'] ),
					'error'       => __( 'No ZIP package was found in the latest GitHub release.', 'print-order-configurator-rtl' ),
					'checked_at'  => time(),
				)
			);
			delete_site_transient( $cache_key );
			return null;
		}

		$release = array(
			'version' => $this->normalize_version( (string) $data['tag_name'] ),
			'url'     => isset( $data['html_url'] ) ? esc_url_raw( (string) $data['html_url'] ) : 'https://github.com/' . $repo,
			'package' => esc_url_raw( $package ),
			'body'    => isset( $data['body'] ) ? wp_kses_post( (string) $data['body'] ) : '',
		);

		$this->store_status(
			array(
				'ok'             => true,
				'api_url'        => $api_url,
				'http_status'    => $http_status,
				'raw_tag'        => (string) $data['tag_name'],
				'version'        => $release['version'],
				'package'        => $release['package'],
				'url'            => $release['url'],
				'checked_at'     => time(),
				'update_available' => version_compare( $release['version'], POCRTL_VERSION, '>' ),
			)
		);

		set_site_transient( $cache_key, $release, 6 * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Find a ZIP package URL from release assets or fallback zipball.
	 *
	 * @param array<string,mixed> $data Release data.
	 */
	private function release_package_url( array $data ): string {
		$zip_assets = array();

		if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				if ( ! is_array( $asset ) || empty( $asset['browser_download_url'] ) ) {
					continue;
				}

				$name = isset( $asset['name'] ) ? strtolower( (string) $asset['name'] ) : '';

				if ( str_ends_with( $name, '.zip' ) ) {
					$zip_assets[ $name ] = (string) $asset['browser_download_url'];
				}
			}
		}

		if ( ! empty( $zip_assets ) ) {
			$version = isset( $data['tag_name'] ) ? $this->normalize_version( (string) $data['tag_name'] ) : '';
			$preferred = array(
				self::SLUG . '.zip',
				self::SLUG . '-' . $version . '.zip',
				self::SLUG . '-v' . $version . '.zip',
			);

			foreach ( $preferred as $name ) {
				if ( isset( $zip_assets[ $name ] ) ) {
					return $zip_assets[ $name ];
				}
			}

			return reset( $zip_assets ) ?: '';
		}

		return isset( $data['zipball_url'] ) ? (string) $data['zipball_url'] : '';
	}

	/**
	 * Normalize GitHub tag to semantic version string.
	 *
	 * @param string $version Raw version.
	 */
	private function normalize_version( string $version ): string {
		return ltrim( trim( $version ), 'vV' );
	}

	/**
	 * Store non-sensitive update status/debug information.
	 *
	 * @param array<string,mixed> $status Status payload.
	 */
	private function store_status( array $status ): void {
		update_option( self::STATUS_OPTION, $status, false );
	}

	/**
	 * Verify admin action capability and nonce.
	 *
	 * @param string $action Nonce action.
	 */
	private function assert_update_capability( string $action ): void {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to manage plugin updates.', 'print-order-configurator-rtl' ),
				esc_html__( 'Permission denied', 'print-order-configurator-rtl' ),
				array( 'response' => 403 )
			);
		}

		check_admin_referer( $action, 'pocrtl_update_nonce' );
	}
}
