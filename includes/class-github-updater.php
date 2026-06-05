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

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_information' ), 10, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'fix_install_folder' ), 10, 3 );
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

		$transient->response[ POC_RTL_BASENAME ] = (object) array(
			'id'          => POC_RTL_BASENAME,
			'slug'        => self::SLUG,
			'plugin'      => POC_RTL_BASENAME,
			'new_version' => $release['version'],
			'url'         => $release['url'],
			'package'     => $release['package'],
			'tested'      => '',
			'requires'    => '6.5',
		);

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
	 * Rename extracted GitHub ZIP folder to the plugin folder.
	 *
	 * @param bool|array<string,mixed> $response Install response.
	 * @param array<string,mixed>      $hook_extra Hook context.
	 * @param array<string,mixed>      $result Install result.
	 * @return bool|array<string,mixed>
	 */
	public function fix_install_folder( bool|array $response, array $hook_extra, array $result ): bool|array {
		if ( empty( $hook_extra['plugin'] ) || POC_RTL_BASENAME !== $hook_extra['plugin'] || empty( $result['destination'] ) ) {
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
		$destination = trailingslashit( WP_PLUGIN_DIR ) . dirname( POC_RTL_BASENAME );

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
	 * Fetch latest GitHub release metadata.
	 *
	 * @return array<string,string>|null
	 */
	private function latest_release(): ?array {
		$repo = (string) POC_RTL_Settings::get( 'pocrtl_github_repo', POCRTL_GITHUB_REPO );

		if ( ! preg_match( '/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repo ) ) {
			$repo = POCRTL_GITHUB_REPO;
		}

		$cache_key = 'pocrtl_github_release_' . md5( $repo );
		$cached    = get_site_transient( $cache_key );

		if ( is_array( $cached ) ) {
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

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_site_transient( $cache_key, null, HOUR_IN_SECONDS );
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			set_site_transient( $cache_key, null, HOUR_IN_SECONDS );
			return null;
		}

		$package = $this->release_package_url( $data );

		if ( '' === $package ) {
			set_site_transient( $cache_key, null, HOUR_IN_SECONDS );
			return null;
		}

		$release = array(
			'version' => $this->normalize_version( (string) $data['tag_name'] ),
			'url'     => isset( $data['html_url'] ) ? esc_url_raw( (string) $data['html_url'] ) : 'https://github.com/' . $repo,
			'package' => esc_url_raw( $package ),
			'body'    => isset( $data['body'] ) ? wp_kses_post( (string) $data['body'] ) : '',
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
		if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				if ( ! is_array( $asset ) || empty( $asset['browser_download_url'] ) ) {
					continue;
				}

				$name = isset( $asset['name'] ) ? strtolower( (string) $asset['name'] ) : '';

				if ( str_ends_with( $name, '.zip' ) ) {
					return (string) $asset['browser_download_url'];
				}
			}
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
}
