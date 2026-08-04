<?php
/**
 * REST routes for listing, toggling, and installing extensions.
 *
 * @package BPEM
 */

namespace BPEM\Admin;

use BPEM\Catalog\CatalogService;
use BPEM\Catalog\Installer;
use BPEM\ExtensionRegistry;
use BPEM\Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Namespace `bpem/{slug}/v1`. All routes gated by capability + nonce.
 */
final class RestController {

	/**
	 * Host manager.
	 *
	 * @var Manager
	 */
	private $manager;

	/**
	 * Constructor.
	 *
	 * @param Manager $manager Host manager.
	 */
	public function __construct( Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Hook into rest_api_init.
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * REST namespace for this host.
	 */
	private function namespace(): string {
		return "bpem/{$this->manager->get_slug()}/v1";
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		$permission = array( $this, 'check_permission' );

		if ( $this->manager->extensions_enabled() ) {
			$this->register_extension_routes( $permission );
		}
		if ( $this->manager->modules_enabled() ) {
			$this->register_module_routes( $permission );
		}
	}

	/**
	 * Extension routes (only registered when the subsystem is enabled).
	 *
	 * @param callable $permission Capability gate.
	 */
	private function register_extension_routes( $permission ): void {
		register_rest_route(
			$this->namespace(),
			'/extensions',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'list_extensions' ),
				'permission_callback' => $permission,
			)
		);

		register_rest_route(
			$this->namespace(),
			'/extensions/(?P<id>[a-z0-9_\-]+)/toggle',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'toggle_extension' ),
				'permission_callback' => $permission,
				'args'                => array(
					'enabled' => array(
						'required' => true,
						'type'     => 'boolean',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace(),
			'/extensions/(?P<id>[a-z0-9_\-]+)/install',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'install_extension' ),
				'permission_callback' => $permission,
			)
		);
	}

	/**
	 * Module routes (only registered when the subsystem is enabled).
	 *
	 * @param callable $permission Capability gate.
	 */
	private function register_module_routes( $permission ): void {
		register_rest_route(
			$this->namespace(),
			'/modules',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'list_modules' ),
				'permission_callback' => $permission,
			)
		);

		register_rest_route(
			$this->namespace(),
			'/modules/(?P<id>[a-z0-9_\-]+)/toggle',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'toggle_module' ),
				'permission_callback' => $permission,
				'args'                => array(
					'enabled' => array(
						'required' => true,
						'type'     => 'boolean',
					),
				),
			)
		);

		// Mutating the filesystem (upload/install/delete) needs the stronger
		// install_plugins capability, not just the page capability.
		$manage = array( $this, 'check_manage_permission' );

		// Local .zip upload is opt-outable per host (`enable_module_upload`); when
		// off, don't expose the route at all — catalog install + delete stay.
		if ( $this->manager->module_upload_enabled() ) {
			register_rest_route(
				$this->namespace(),
				'/modules/upload',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'upload_module' ),
					'permission_callback' => $manage,
				)
			);
		}

		register_rest_route(
			$this->namespace(),
			'/modules/(?P<id>[a-z0-9_\-]+)/install',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'install_module' ),
				'permission_callback' => $manage,
			)
		);

		register_rest_route(
			$this->namespace(),
			'/modules/(?P<id>[a-z0-9_\-]+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_module' ),
				'permission_callback' => $manage,
			)
		);
	}

	/**
	 * Capability gate (nonce is verified by core via X-WP-Nonce).
	 */
	public function check_permission(): bool {
		return current_user_can( (string) $this->manager->get_config( 'capability', 'manage_options' ) );
	}

	/**
	 * Stronger gate for filesystem mutations (module upload / install / delete):
	 * the page capability AND `install_plugins`, and never when file mods are
	 * disabled site-wide.
	 */
	public function check_manage_permission(): bool {
		if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
			return false;
		}
		return $this->check_permission() && current_user_can( 'install_plugins' );
	}

	/**
	 * GET /extensions — merged installed ∪ remote list.
	 *
	 * @return \WP_REST_Response
	 */
	public function list_extensions(): \WP_REST_Response {
		$list = $this->manager->catalog()->get_merged( $this->manager );
		return rest_ensure_response( $list );
	}

	/**
	 * POST /extensions/{id}/toggle.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function toggle_extension( \WP_REST_Request $request ) {
		$id  = sanitize_key( $request['id'] );
		$ext = $this->manager->get_extensions()[ $id ] ?? null;
		if ( ! $ext ) {
			return new \WP_Error( 'bpem_unknown', __( 'Unknown extension.', 'bp-extension-manager' ), array( 'status' => 404 ) );
		}

		$enabled = (bool) $request['enabled'];

		// Refuse to enable an unlicensed extension — same gate the registry
		// applies at boot and the catalog exposes as `licensed`.
		if ( $enabled && ! ExtensionRegistry::instance()->check_license( $this->manager, $ext ) ) {
			return new \WP_Error(
				'bpem_unlicensed',
				__( 'This extension requires a valid license.', 'bp-extension-manager' ),
				array( 'status' => 403 )
			);
		}

		if ( $enabled ) {
			$this->manager->enable( $id );
		} else {
			$this->manager->disable( $id );
		}

		return rest_ensure_response( $this->single( $id ) );
	}

	/**
	 * POST /extensions/{id}/install.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function install_extension( \WP_REST_Request $request ): \WP_REST_Response {
		$id = sanitize_key( $request['id'] );

		// Optional license key from an in-context Freemius purchase, forwarded to
		// the install-URL filter so a paid add-on can be downloaded right after
		// checkout without leaving the page.
		$license_key = $request->get_param( 'license_key' );
		$license_key = is_string( $license_key ) ? sanitize_text_field( $license_key ) : '';

		$installer = new Installer( $this->manager->catalog() );
		$result    = $installer->install( $this->manager, $id, $license_key );

		// A successful install/activate changes on-disk state and may follow a
		// catalog correction, so drop the cached catalog: the client refetches
		// immediately after and must see fresh presence data, not a stale entry
		// (a mismatched `plugin_file` would otherwise strand the card on "Install").
		if ( ! empty( $result['success'] ) ) {
			CatalogService::flush_cache( $this->manager );
		}

		return rest_ensure_response( $result );
	}

	/* ------------------------------ modules ------------------------------- */

	/**
	 * GET /modules — merged installed ∪ remote module list.
	 *
	 * @return \WP_REST_Response
	 */
	public function list_modules(): \WP_REST_Response {
		return rest_ensure_response( $this->manager->modules()->get_all() );
	}

	/**
	 * POST /modules/{id}/toggle.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function toggle_module( \WP_REST_Request $request ) {
		$id      = sanitize_key( $request['id'] );
		$service = $this->manager->modules();
		$module  = $service->repository()->find( $id );
		if ( ! $module ) {
			return new \WP_Error( 'bpem_unknown', __( 'Unknown module.', 'bp-extension-manager' ), array( 'status' => 404 ) );
		}

		$enabled = (bool) $request['enabled'];

		// Refuse to enable a module the host license does not unlock.
		if ( $enabled && ! $service->check_license( $module ) ) {
			return new \WP_Error(
				'bpem_unlicensed',
				__( 'This module requires a valid license.', 'bp-extension-manager' ),
				array( 'status' => 403 )
			);
		}

		if ( $enabled ) {
			$this->manager->enable_module( $id );
		} else {
			$this->manager->disable_module( $id );
		}

		return rest_ensure_response( $this->single_module( $id ) );
	}

	/**
	 * POST /modules/upload — install a module from an uploaded zip.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function upload_module( \WP_REST_Request $request ): \WP_REST_Response {
		$files = $request->get_file_params();
		$file  = $files['file'] ?? null;

		if ( ! is_array( $file ) || empty( $file['tmp_name'] ) || ! empty( $file['error'] ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'status'  => 'error',
					'message' => __( 'No file was uploaded.', 'bp-extension-manager' ),
				)
			);
		}

		$result = $this->manager->modules()->repository()->install_zip( (string) $file['tmp_name'] );

		// A freshly installed module defaults to enabled (admin added it on purpose).
		if ( ! empty( $result['success'] ) && ! empty( $result['id'] ) ) {
			$result['module'] = $this->single_module( (string) $result['id'] );
		}

		return rest_ensure_response( $result );
	}

	/**
	 * POST /modules/{id}/install — install a remote-catalog module by id.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function install_module( \WP_REST_Request $request ): \WP_REST_Response {
		$id    = sanitize_key( $request['id'] );
		$entry = $this->manager->catalog()->find_remote_module( $this->manager, $id );
		if ( ! $entry ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'status'  => 'error',
					'message' => __( 'Module not found in catalog.', 'bp-extension-manager' ),
				)
			);
		}

		$is_premium_only = ! empty( $entry['premium_host_only'] );
		if ( ( $is_premium_only || ! empty( $entry['is_paid'] ) ) && ! $this->manager->is_max_plan() && ! $this->manager->is_premium_active() ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'status'  => 'error',
					'message' => __( 'This module requires a valid premium license.', 'bp-extension-manager' ),
				)
			);
		}

		$url    = isset( $entry['download_url'] ) ? (string) $entry['download_url'] : '';
		$url    = (string) apply_filters( "bpem/{$this->manager->get_slug()}/module_install_url", $url, $entry, $this->manager );
		$result = '' === $url
			? array(
				'success' => false,
				'status'  => 'error',
				'message' => __( 'No download is available for this module.', 'bp-extension-manager' ),
			)
			: $this->manager->modules()->repository()->install_from_url( $url );

		if ( ! empty( $result['success'] ) && ! empty( $result['id'] ) ) {
			$result['module'] = $this->single_module( (string) $result['id'] );
		}

		return rest_ensure_response( $result );
	}

	/**
	 * DELETE /modules/{id} — remove an installed module.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function delete_module( \WP_REST_Request $request ): \WP_REST_Response {
		$id     = sanitize_key( $request['id'] );
		$result = $this->manager->modules()->repository()->delete( $id );
		if ( ! empty( $result['success'] ) ) {
			$this->manager->forget_module( $id );
		}
		return rest_ensure_response( $result );
	}

	/* ---------------------------------------------------------------------- */

	/**
	 * Re-derive a single module's payload (used after a mutation).
	 *
	 * @param string $id Module id.
	 * @return array<string,mixed>
	 */
	private function single_module( string $id ): array {
		return $this->manager->modules()->single( $id );
	}

	/**
	 * Re-derive a single extension's payload (used after toggle).
	 *
	 * @param string $id Extension id.
	 * @return array<string,mixed>
	 */
	private function single( string $id ): array {
		foreach ( $this->manager->catalog()->get_merged( $this->manager ) as $row ) {
			if ( $row['id'] === $id ) {
				return $row;
			}
		}
		return array( 'id' => $id );
	}

}
