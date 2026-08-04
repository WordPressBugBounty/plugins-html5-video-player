<?php
/**
 * Downloads, unzips, and activates a catalog extension — server-side only.
 *
 * @package BPEM
 */

namespace BPEM\Catalog;

use BPEM\Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Install flow with a download-host allowlist. The download URL is ALWAYS
 * resolved server-side from the cached catalog — never trusted from the client.
 */
final class Installer {

	/**
	 * Catalog service.
	 *
	 * @var CatalogService
	 */
	private $catalog;

	/**
	 * Constructor.
	 *
	 * @param CatalogService $catalog Catalog service.
	 */
	public function __construct( CatalogService $catalog ) {
		$this->catalog = $catalog;
	}

	/**
	 * Install + activate an extension by id.
	 *
	 * @param Manager $m           Host manager.
	 * @param string  $ext_id      Extension id (validated against the catalog).
	 * @param string  $license_key Optional license key from a just-completed
	 *                             Freemius purchase, forwarded to the install-URL
	 *                             filter so hosts can mint a signed download URL.
	 * @return array{success:bool,status:string,message?:string}
	 */
	public function install( Manager $m, string $ext_id, string $license_key = '' ): array {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return $this->fail( __( 'You cannot install plugins.', 'bp-extension-manager' ) );
		}

		/**
		 * Gate before install (host can veto).
		 *
		 * @param bool    $can         Whether install may proceed.
		 * @param string  $ext_id      Extension id.
		 * @param Manager $m           Host manager.
		 * @param string  $license_key License key from a just-completed purchase (may be '').
		 */
		if ( ! apply_filters( "bpem/{$m->get_slug()}/can_install", true, $ext_id, $m, $license_key ) ) {
			return $this->fail( __( 'Installation is not allowed for this extension.', 'bp-extension-manager' ) );
		}

		$entry = $this->catalog->find_remote_entry( $m, $ext_id );
		if ( ! $entry ) {
			return $this->fail( __( 'Extension not found in catalog.', 'bp-extension-manager' ) );
		}

		$is_premium_only = ! empty( $entry['premium_host_only'] );
		if ( $is_premium_only && ! $m->is_premium() ) {
			return $this->fail( __( 'This extension requires a valid premium license.', 'bp-extension-manager' ) );
		}

		$raw_required = isset( $entry['required_plugins'] ) ? $entry['required_plugins'] : ( isset( $entry['requires_plugins'] ) ? $entry['requires_plugins'] : array() );
		$required     = CatalogService::required_plugin_files( $raw_required );

		if ( ! empty( $required ) ) {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			foreach ( $required as $basename ) {
				if ( ! is_plugin_active( $basename ) ) {
					return $this->fail( __( 'Requires active dependencies.', 'bp-extension-manager' ) );
				}
			}
		}

		$plugin_file = isset( $entry['plugin_file'] ) ? (string) $entry['plugin_file'] : '';

		// Never trust a catalog-supplied path: it must be a confined plugin basename
		// (no traversal, no escaping WP_PLUGIN_DIR) before any is_readable/activate.
		if ( '' !== $plugin_file && ! CatalogService::is_plugin_file_safe( $plugin_file ) ) {
			return $this->fail( __( 'Invalid plugin file in catalog.', 'bp-extension-manager' ) );
		}

		// Already on disk → don't re-download, just activate it.
		if ( '' !== $plugin_file && is_readable( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
			return $this->activate( $plugin_file );
		}

		$download_url = $this->resolve_download_url( $m, $entry, $license_key );
		if ( ! $download_url ) {
			return $this->fail( __( 'No download is available for this extension.', 'bp-extension-manager' ) );
		}

		if ( ! $this->host_allowed( $m, $download_url ) ) {
			return $this->fail( __( 'Download host is not allowed.', 'bp-extension-manager' ) );
		}

		// Cache-bust: the same URL may serve a replaced file on the server.
		$download_url = add_query_arg( 'nocache', time(), $download_url );

		$result = $this->run_upgrader( $download_url );
		if ( is_wp_error( $result ) ) {
			return $this->fail( $result->get_error_message() );
		}

		// Install only — do NOT auto-activate. The UI then shows a separate
		// "Activate" action, which re-enters install() and hits the on-disk
		// branch above to activate it.
		return array(
			'success' => true,
			'status'  => 'installed',
		);
	}

	/* ---------------------------------------------------------------------- */

	/**
	 * Activate a plugin by basename.
	 *
	 * @param string $plugin_file Plugin basename.
	 * @return array{success:bool,status:string,message?:string}
	 */
	private function activate( string $plugin_file ): array {
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return $this->fail( __( 'You cannot activate plugins.', 'bp-extension-manager' ) );
		}

		$result = activate_plugin( $plugin_file );
		if ( is_wp_error( $result ) ) {
			return array(
				'success' => true,
				'status'  => 'installed',
				'message' => __( 'Installed, but activation failed.', 'bp-extension-manager' ),
			);
		}

		return array(
			'success' => true,
			'status'  => 'active',
		);
	}

	/**
	 * Resolve the real download URL server-side.
	 *
	 * For free add-ons, use the catalog `download_url`. For paid add-ons the
	 * real URL should come from Freemius/a signed source — hosts hook the
	 * `bpem/{slug}/install_url` filter to inject it. When the install follows an
	 * in-context Freemius purchase, the buyer's license key is passed along so the
	 * host can build a signed, license-scoped download URL for the add-on.
	 *
	 * @param Manager             $m           Host manager.
	 * @param array<string,mixed> $entry       Catalog entry.
	 * @param string              $license_key License key from a just-completed purchase (may be '').
	 * @return string Empty string when none.
	 */
	private function resolve_download_url( Manager $m, array $entry, string $license_key = '' ): string {
		$url = isset( $entry['download_url'] ) ? (string) $entry['download_url'] : '';

		/**
		 * Override the resolved download URL (e.g. a Freemius signed URL for paid add-ons).
		 *
		 * @param string              $url         Default URL from the catalog.
		 * @param array<string,mixed> $entry       Catalog entry.
		 * @param Manager             $m           Host manager.
		 * @param string              $license_key License key from a just-completed purchase (may be '').
		 */
		$url = apply_filters( "bpem/{$m->get_slug()}/install_url", $url, $entry, $m, $license_key );

		return esc_url_raw( (string) $url );
	}

	/**
	 * Whether the download host is on the allowlist (catalog domain + filterable extras).
	 *
	 * @param Manager $m   Host manager.
	 * @param string  $url Download URL.
	 */
	private function host_allowed( Manager $m, string $url ): bool {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}

		// Trusted by default: the official WordPress.org repo and bPlugins.
		$allowed = array(
			'downloads.wordpress.org',
			'wordpress.org',
			'bplugins.com',
			'www.bplugins.com',
		);

		$catalog = $m->get_config( 'catalog_url' );
		if ( $catalog ) {
			$catalog_host = wp_parse_url( $catalog, PHP_URL_HOST );
			if ( $catalog_host ) {
				$allowed[] = $catalog_host;
			}
		}

		/**
		 * Filter the download-host allowlist.
		 *
		 * @param string[] $allowed Allowed hostnames.
		 * @param Manager  $m       Host manager.
		 */
		$allowed = (array) apply_filters( "bpem/{$m->get_slug()}/download_hosts", $allowed, $m );

		return in_array( strtolower( $host ), array_map( 'strtolower', $allowed ), true );
	}

	/**
	 * Run Plugin_Upgrader against a URL.
	 *
	 * @param string $url Download URL.
	 * @return bool|\WP_Error
	 */
	private function run_upgrader( string $url ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		if ( ! \WP_Filesystem() ) {
			return new \WP_Error( 'bpem_fs', __( 'Could not access the filesystem.', 'bp-extension-manager' ) );
		}

		$skin     = new \Automatic_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $url );

		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( false === $result || null === $result ) {
			return new \WP_Error( 'bpem_install', __( 'Installation failed.', 'bp-extension-manager' ) );
		}
		return true;
	}

	/**
	 * Build a failure response.
	 *
	 * @param string $message Message.
	 * @return array{success:bool,status:string,message:string}
	 */
	private function fail( string $message ): array {
		return array(
			'success' => false,
			'status'  => 'error',
			'message' => $message,
		);
	}
}
