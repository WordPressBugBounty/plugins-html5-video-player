<?php
/**
 * Holds Managers by slug, runs the gating pipeline, and stores statuses.
 *
 * @package BPEM
 */

namespace BPEM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared singleton across the whole request (lives in the winning copy only).
 */
final class ExtensionRegistry {

	const STATUS_ACTIVE             = 'active';
	const STATUS_DISABLED           = 'disabled';
	const STATUS_INCOMPATIBLE       = 'incompatible';
	const STATUS_MISSING_DEPENDENCY = 'missing_dependency';
	const STATUS_UNLICENSED         = 'unlicensed';
	const STATUS_ERROR              = 'error';

	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Managers keyed by host slug.
	 *
	 * @var array<string,Manager>
	 */
	private $managers = array();

	/**
	 * Registered extensions: [ slug => [ ext_id => BaseExtension ] ].
	 *
	 * @var array<string,array<string,BaseExtension>>
	 */
	private $extensions = array();

	/**
	 * Computed statuses: [ slug => [ ext_id => status ] ].
	 *
	 * @var array<string,array<string,string>>
	 */
	private $statuses = array();

	/**
	 * Singleton accessor.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {}

	/**
	 * Add a Manager, keyed by its slug.
	 *
	 * @param Manager $m Manager.
	 */
	public function add_manager( Manager $m ): void {
		$this->managers[ $m->get_slug() ] = $m;
	}

	/**
	 * Get a Manager by slug.
	 *
	 * @param string $slug Host slug.
	 */
	public function get_manager( string $slug ): ?Manager {
		return $this->managers[ $slug ] ?? null;
	}

	/**
	 * Register an extension under a host. Enforces single-host targeting.
	 *
	 * @param Manager       $m   Host manager.
	 * @param BaseExtension $ext Add-on.
	 */
	public function register( Manager $m, BaseExtension $ext ): void {
		if ( $ext->get_host_slug() !== $m->get_slug() ) {
			$this->log(
				sprintf(
					'rejected "%s": targets host "%s" but was registered on "%s".',
					$ext->get_id(),
					$ext->get_host_slug(),
					$m->get_slug()
				)
			);
			return;
		}

		$this->extensions[ $m->get_slug() ][ $ext->get_id() ] = $ext;
	}

	/**
	 * Registered extensions for a host.
	 *
	 * @param string $slug Host slug.
	 * @return array<string,BaseExtension>
	 */
	public function get_extensions( string $slug ): array {
		return $this->extensions[ $slug ] ?? array();
	}

	/**
	 * Evaluate + boot every extension for one host.
	 *
	 * @param Manager $m Host manager.
	 */
	public function boot_host( Manager $m ): void {
		foreach ( $this->get_extensions( $m->get_slug() ) as $ext_id => $ext ) {
			$status = $this->evaluate( $m, $ext );

			if ( self::STATUS_ACTIVE === $status ) {
				try {
					$ext->boot();
				} catch ( \Throwable $e ) {
					$status = self::STATUS_ERROR;
					$this->log( 'boot threw for "' . $ext_id . '": ' . $e->getMessage() );
				}
			}

			$this->statuses[ $m->get_slug() ][ $ext_id ] = $status;
		}
	}

	/**
	 * Run the gating pipeline (short-circuits). Does NOT call boot().
	 *
	 * @param Manager       $m   Host manager.
	 * @param BaseExtension $ext Add-on.
	 * @return string One of the STATUS_* constants (except ERROR).
	 */
	public function evaluate( Manager $m, BaseExtension $ext ): string {
		// 1. Parent (host) version.
		$min = $ext->get_min_parent_version();
		if ( version_compare( (string) $m->get_config( 'version' ), $min, '<' ) ) {
			return self::STATUS_INCOMPATIBLE;
		}

		// 2. Required plugins active.
		if ( ! $this->required_plugins_active( $ext->get_required_plugins() ) ) {
			return self::STATUS_MISSING_DEPENDENCY;
		}

		// 3. Admin enable toggle (default disabled).
		if ( ! $m->is_enabled( $ext->get_id() ) ) {
			return self::STATUS_DISABLED;
		}

		// 4. License.
		if ( ! $this->check_license( $m, $ext ) ) {
			return self::STATUS_UNLICENSED;
		}

		return self::STATUS_ACTIVE;
	}

	/**
	 * Stored status for an extension. Falls back to a fresh evaluation.
	 *
	 * @param string $slug   Host slug.
	 * @param string $ext_id Extension id.
	 */
	public function get_status( string $slug, string $ext_id ): string {
		if ( isset( $this->statuses[ $slug ][ $ext_id ] ) ) {
			return $this->statuses[ $slug ][ $ext_id ];
		}
		$m   = $this->get_manager( $slug );
		$ext = $this->extensions[ $slug ][ $ext_id ] ?? null;
		if ( $m && $ext ) {
			return $this->evaluate( $m, $ext );
		}
		return self::STATUS_DISABLED;
	}

	/**
	 * Invalidate the cached status for an extension.
	 *
	 * @param string $slug   Host slug.
	 * @param string $ext_id Extension id.
	 */
	public function clear_status( string $slug, string $ext_id ): void {
		unset( $this->statuses[ $slug ][ $ext_id ] );
	}

	/* ---------------------------------------------------------------------- */

	/**
	 * License gate. The single source of truth for extension licensing —
	 * the catalog shaper and the REST toggle must use this same check, or the
	 * client's `licensed` flag drifts from what the server enforces.
	 *
	 * @param Manager       $m   Host manager.
	 * @param BaseExtension $ext Add-on.
	 */
	public function check_license( Manager $m, BaseExtension $ext ): bool {
		if ( $m->is_max_plan() ) {
			return true; // Per-host Max Plan unlocks all.
		}
		if ( method_exists( $ext, 'is_premium_host_only' ) && $ext->is_premium_host_only() ) {
			if ( ! $m->is_premium() ) {
				return false;
			}
		}
		$fs = $ext->get_freemius();
		if ( ! $fs ) {
			return true; // Free extension.
		}
		try {
			return (bool) $fs->can_use_premium_code();
		} catch ( \Throwable $e ) {
			return false;
		}
	}


	/**
	 * Whether every required plugin basename is active.
	 *
	 * @param string[] $plugins Plugin basenames.
	 */
	private function required_plugins_active( array $plugins ): bool {
		if ( empty( $plugins ) ) {
			return true;
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		foreach ( $plugins as $basename ) {
			if ( ! is_plugin_active( $basename ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Log (only when WP_DEBUG).
	 *
	 * @param string $message Message.
	 */
	private function log( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[bpem] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
