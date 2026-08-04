<?php
/**
 * Orchestrates modules: gating pipeline, statuses, boot, and the merged payload.
 *
 * @package BPEM
 */

namespace BPEM\Modules;

use BPEM\ExtensionRegistry;
use BPEM\Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The module analogue of CatalogService + the module half of ExtensionRegistry.
 *
 * Modules are host-plan licensed (they carry no Freemius of their own): a paid or
 * premium-host-only module is unlocked by the HOST's Max Plan / premium / a named
 * plan id — never by a per-module license key.
 */
final class ModuleService {

	/**
	 * Host manager.
	 *
	 * @var Manager
	 */
	private $manager;

	/**
	 * Filesystem repository.
	 *
	 * @var ModuleRepository
	 */
	private $repo;

	/**
	 * Computed statuses, keyed by module id.
	 *
	 * @var array<string,string>
	 */
	private $statuses = array();

	/**
	 * Constructor.
	 *
	 * @param Manager $manager Host manager.
	 */
	public function __construct( Manager $manager ) {
		$this->manager = $manager;
		$this->repo    = new ModuleRepository( $manager );
	}

	/**
	 * Filesystem repository (upload/delete live there).
	 */
	public function repository(): ModuleRepository {
		return $this->repo;
	}

	/**
	 * Evaluate + boot every enabled, licensed, compatible module. Called once per
	 * request from Manager::init(), so module features run on front end + admin.
	 */
	public function boot(): void {
		foreach ( $this->repo->all() as $id => $module ) {
			$status = $this->evaluate( $module );

			if ( ExtensionRegistry::STATUS_ACTIVE === $status ) {
				try {
					require_once $module->get_main_file();
				} catch ( \Throwable $e ) {
					$status = ExtensionRegistry::STATUS_ERROR;
					$this->log( 'module "' . $id . '" threw on load: ' . $e->getMessage() );
				}
			}

			$this->statuses[ $id ] = $status;
		}
	}

	/**
	 * Run the gating pipeline for one module (short-circuits). No install step.
	 *
	 * @param Module $module Module.
	 * @return string One of ExtensionRegistry::STATUS_* (except ERROR).
	 */
	public function evaluate( Module $module ): string {
		// 1. Host version.
		if ( version_compare( (string) $this->manager->get_config( 'version' ), $module->get_min_host_version(), '<' ) ) {
			return ExtensionRegistry::STATUS_INCOMPATIBLE;
		}

		// 2. Required plugins active.
		if ( ! $this->required_plugins_active( $module->get_required_plugins() ) ) {
			return ExtensionRegistry::STATUS_MISSING_DEPENDENCY;
		}

		// 3. Admin enable toggle (default per module header, usually enabled).
		if ( ! $this->manager->is_module_enabled( $module->get_id(), $module->is_enabled_by_default() ) ) {
			return ExtensionRegistry::STATUS_DISABLED;
		}

		// 4. Host-plan license gate.
		if ( ! $this->check_license( $module ) ) {
			return ExtensionRegistry::STATUS_UNLICENSED;
		}

		return ExtensionRegistry::STATUS_ACTIVE;
	}

	/**
	 * Stored status for a module, falling back to a fresh evaluation.
	 *
	 * @param string $id Module id.
	 */
	public function get_status( string $id ): string {
		if ( isset( $this->statuses[ $id ] ) ) {
			return $this->statuses[ $id ];
		}
		$module = $this->repo->find( $id );
		return $module ? $this->evaluate( $module ) : ExtensionRegistry::STATUS_DISABLED;
	}

	/**
	 * Invalidate a cached module status.
	 *
	 * @param string $id Module id.
	 */
	public function clear_status( string $id ): void {
		unset( $this->statuses[ $id ] );
	}

	/**
	 * Computed statuses populated by boot(), keyed by module id (read-only).
	 *
	 * @return array<string,string>
	 */
	public function statuses(): array {
		return $this->statuses;
	}

	/**
	 * Whether the host license unlocks this module.
	 *
	 * @param Module $module Module.
	 */
	public function check_license( Module $module ): bool {
		if ( $this->manager->is_max_plan() ) {
			return true;
		}
		$plan = $module->get_required_plan();
		if ( '' !== $plan ) {
			return $this->manager->is_plan( $plan );
		}
		if ( $module->is_premium_host_only() || $module->is_paid() ) {
			// is_premium_active(), not is_premium(): a paid module must stop when the
			// host license expires or is deactivated, not keep running on premium code.
			return $this->manager->is_premium_active();
		}
		return true;
	}

	/**
	 * Merged payload: installed (on-disk) ∪ remote (catalog) modules. Installed wins.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_all(): array {
		$max_plan = $this->manager->is_max_plan();
		$merged   = array();

		// 1. Installed modules.
		foreach ( $this->repo->all() as $id => $module ) {
			$merged[ $id ] = $this->shape_installed( $module, $max_plan );
		}

		// 2. Remote-only catalog modules.
		foreach ( $this->manager->catalog()->fetch_remote_modules( $this->manager ) as $entry ) {
			$id = $entry['id'];
			if ( isset( $merged[ $id ] ) ) {
				continue; // Installed wins.
			}
			$merged[ $id ] = $this->shape_remote( $entry, $max_plan );
		}

		$list = array_values( $merged );

		/**
		 * Filter the merged module list for a host.
		 *
		 * @param array   $list Merged module payload.
		 * @param Manager $m    Host manager.
		 */
		return apply_filters( "bpem/{$this->manager->get_slug()}/modules", $list, $this->manager );
	}

	/**
	 * Re-derive a single module's payload (used after toggle/upload/delete).
	 *
	 * @param string $id Module id.
	 * @return array<string,mixed>
	 */
	public function single( string $id ): array {
		foreach ( $this->get_all() as $row ) {
			if ( $row['id'] === $id ) {
				return $row;
			}
		}
		return array( 'id' => $id );
	}

	/* ---------------------------------------------------------------------- */

	/**
	 * Shape an installed module into the response array.
	 *
	 * @param Module $module   Module.
	 * @param bool   $max_plan Whether the host holds the Max Plan.
	 * @return array<string,mixed>
	 */
	private function shape_installed( Module $module, bool $max_plan ): array {
		$id              = $module->get_id();
		$status          = $this->get_status( $id );
		$is_paid         = $module->is_paid();
		$is_premium_only = $module->is_premium_host_only();

		// Run the real license gate — never infer it from $status: a disabled
		// module short-circuits evaluate() before the license step, so its
		// status can never read "unlicensed" while the toggle is off.
		$licensed = $this->check_license( $module );

		$shaped = array(
			'id'                => $id,
			'type'              => 'module',
			'name'              => $module->get_name(),
			'version'           => $module->get_version(),
			'status'            => $status,
			'installed'         => true,
			'enabled'           => $this->manager->is_module_enabled( $id, $module->is_enabled_by_default() ),
			'compatible'        => ExtensionRegistry::STATUS_INCOMPATIBLE !== $status,
			'missing_plugins'   => $this->missing_plugins( $module->get_required_plugins() ),
			'is_paid'           => $is_paid,
			'premium_host_only' => $is_premium_only,
			'licensed'          => (bool) $licensed,
			'max_plan'          => $max_plan,
			'reload'            => \BPEM\Catalog\CatalogService::sanitize_reload( $module->get_reload_behavior() ),
			'deletable'         => $module->is_deletable(),
			'bundled'           => ! $module->is_deletable(),
			'meta'              => $module->meta_for_json(),
			'available'         => array(
				'installable' => false,
				'price_label' => '',
			),
		);

		/**
		 * Filter a single module's computed payload.
		 *
		 * @param array   $shaped Module payload.
		 * @param Module  $module Module.
		 * @param Manager $m      Host manager.
		 */
		return apply_filters( "bpem/{$this->manager->get_slug()}/module_status", $shaped, $module, $this->manager );
	}

	/**
	 * Shape a remote-only (not yet installed) catalog module.
	 *
	 * @param array<string,mixed> $entry    Validated remote entry.
	 * @param bool                $max_plan Whether the host holds the Max Plan.
	 * @return array<string,mixed>
	 */
	private function shape_remote( array $entry, bool $max_plan ): array {
		$is_paid         = ! empty( $entry['is_paid'] );
		$is_premium_only = ! empty( $entry['premium_host_only'] );

		if ( $is_premium_only ) {
			$licensed = $this->manager->is_premium_active();
		} else {
			$licensed = $max_plan || ! $is_paid;
		}

		$required = isset( $entry['requires_plugins'] ) ? $entry['requires_plugins'] : ( isset( $entry['required_plugins'] ) ? $entry['required_plugins'] : array() );

		return array(
			'id'                => $entry['id'],
			'type'              => 'module',
			'name'              => $entry['name'],
			'version'           => $entry['version'],
			'status'            => ExtensionRegistry::STATUS_DISABLED,
			'installed'         => false,
			'enabled'           => false,
			'compatible'        => true,
			'missing_plugins'   => $this->missing_plugins( $required ),
			'is_paid'           => $is_paid,
			'premium_host_only' => $is_premium_only,
			'licensed'          => (bool) $licensed,
			'max_plan'          => $max_plan,
			'reload'            => \BPEM\Catalog\CatalogService::sanitize_reload( $entry['reload'] ?? '' ),
			'deletable'         => false,
			'meta'              => array(
				'icon_url'          => esc_url_raw( (string) ( $entry['icon_url'] ?? '' ) ),
				'short_description' => (string) ( $entry['short_description'] ?? $entry['description'] ?? '' ),
				'homepage_url'      => esc_url_raw( (string) ( $entry['homepage_url'] ?? $entry['buy_url'] ?? '' ) ),
				'author'            => (string) ( $entry['author'] ?? '' ),
				'author_url'        => esc_url_raw( (string) ( $entry['author_uri'] ?? $entry['author_url'] ?? '' ) ),
			),
			'available'         => array(
				'installable' => true,
				'price_label' => (string) ( $entry['price_label'] ?? '' ),
			),
		);
	}

	/**
	 * Describe any required plugins that are not active, with a resolving action.
	 *
	 * @param string[] $required Plugin basenames.
	 * @return array<int,array<string,mixed>>
	 */
	private function missing_plugins( array $required ): array {
		return \BPEM\Catalog\CatalogService::describe_missing_plugins( $required );
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
	 * Log (only under WP_DEBUG).
	 *
	 * @param string $message Message.
	 */
	private function log( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[bpem] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
