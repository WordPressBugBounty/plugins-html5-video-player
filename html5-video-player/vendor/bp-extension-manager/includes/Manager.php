<?php
/**
 * Layer 2: a per-host instance.
 *
 * @package BPEM
 */

namespace BPEM;

use BPEM\Admin\ExtensionsPage;
use BPEM\Admin\RestController;
use BPEM\Catalog\CatalogService;
use BPEM\Modules\ModuleService;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * One Manager per host plugin. Carries the host's config and namespaces all
 * state (options, hooks, REST, AJAX, assets, transients) by its `slug`.
 */
final class Manager
{

	/**
	 * Validated config.
	 *
	 * @var array<string,mixed>
	 */
	private $config;

	/**
	 * Cached resolved Freemius instance (false = not yet resolved).
	 *
	 * @var object|null|false
	 */
	private $freemius = false;

	/**
	 * Memoized CatalogService instance.
	 *
	 * @var CatalogService|null
	 */
	private $catalog = null;

	/**
	 * Memoized ModuleService instance.
	 *
	 * @var ModuleService|null
	 */
	private $modules = null;

	/**
	 * Extra read-only module source directories bundled inside the host plugin.
	 *
	 * @var string[]
	 */
	private $bundled_module_dirs = array();

	/**
	 * Required config keys.
	 *
	 * @var string[]
	 */
	private static $required = array('slug', 'name', 'version', 'menu_parent');

	/**
	 * Use Manager::boot().
	 *
	 * @param array<string,mixed> $config Validated config.
	 */
	private function __construct(array $config)
	{
		$this->config = $config;
	}

	/**
	 * Validate config, build the Manager, register it, and open the
	 * host-scoped registration window.
	 *
	 * @param array<string,mixed> $config Raw host config.
	 * @return self|null Null when config is invalid (logged).
	 */
	public static function boot(array $config): ?self
	{
		// Required keys must be present, scalar, and non-empty. Scalars (e.g. a
		// numeric `version` constant) are coerced to string rather than rejected.
		foreach (self::$required as $key) {
			$val = $config[$key] ?? null;
			if (!is_scalar($val) || '' === trim((string) $val)) {
				self::fail_boot(
					sprintf(
						/* translators: 1: config key, 2: the value or its type */
						__('Manager::boot() needs a non-empty string for "%1$s" (received %2$s) — the Extensions page was not added.', 'bp-extension-manager'),
						$key,
						is_scalar($val) ? '"' . (string) $val . '"' : gettype($val)
					)
				);
				return null;
			}
			$config[$key] = (string) $val;
		}

		$slug = sanitize_key($config['slug']);
		if ('' === $slug || $slug !== $config['slug']) {
			self::fail_boot(
				sprintf(
					/* translators: %s: the supplied slug */
					__('Manager::boot(): slug "%s" must contain only lowercase letters, numbers, hyphens or underscores — the Extensions page was not added.', 'bp-extension-manager'),
					$config['slug']
				)
			);
			return null;
		}

		$registry = ExtensionRegistry::instance();
		if ($registry->get_manager($slug)) {
			// Already booted for this slug (e.g. duplicate host include). Ignore quietly.
			return $registry->get_manager($slug);
		}

		$defaults = array(
			'capability' => 'manage_options',
			'freemius' => null,
			'max_plan_id' => null,
			'catalog_url' => null,
			'catalog_file' => null,
			'modules_catalog_url' => null,
			'bundled_modules_dir' => null,
			'enable_extensions' => true,
			'enable_modules' => true,
			'enable_module_upload' => true,
			'enable_freemius_checkout' => true,
			'catalog_ttl' => 2 * HOUR_IN_SECONDS,
			'page_slug' => "bpem-{$slug}-extensions",
			'menu_badge' => null,
			'menu_badge_persist' => false,
		);

		// Drop unknown keys; merge known ones over defaults.
		$known = array_merge($defaults, array('slug' => '', 'name' => '', 'version' => '', 'menu_parent' => ''));
		$config = array_merge($defaults, array_intersect_key($config, $known));

		// Detect host directory from the calling file to find a fallback catalog file if needed.
		$host_dir = null;
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
		if (!empty($backtrace[0]['file'])) {
			$host_dir = dirname(wp_normalize_path($backtrace[0]['file']));
		}

		if (empty($config['catalog_url']) && empty($config['catalog_file']) && $host_dir) {
			$candidates = array(
				$host_dir . '/extensions.php',
				$host_dir . '/catalog.php',
				$host_dir . '/public/extensions.php',
				$host_dir . '/public/catalog.php',
			);
			foreach ($candidates as $candidate) {
				if (file_exists($candidate) && is_readable($candidate)) {
					$config['catalog_file'] = $candidate;
					break;
				}
			}
		}

		$config['slug'] = $slug;
		$config['capability'] = is_string($config['capability']) ? $config['capability'] : 'manage_options';
		$config['catalog_ttl'] = (int) $config['catalog_ttl'];

		$manager = new self($config);
		$registry->add_manager($manager);

		$manager->init();

		return $manager;
	}

	/**
	 * Wire admin page, REST routes, then open the registration window and gate.
	 *
	 * Each subsystem (extensions, modules) can be turned off independently via
	 * `enable_extensions` / `enable_modules`; a disabled one registers no routes,
	 * shows no tab, and is never scanned or booted.
	 */
	private function init(): void
	{
		$enable_ext = $this->extensions_enabled();
		$enable_mod = $this->modules_enabled();

		// Admin surfaces (only meaningful in wp-admin, but registration is cheap).
		// The page + REST controller host whichever subsystem(s) are enabled.
		if ($enable_ext || $enable_mod) {
			(new ExtensionsPage($this))->register_hooks();
			(new RestController($this))->register_hooks();
		}
		if ($enable_ext) {
			(new ExtensionLicenseManager($this))->register_hooks();
		}

		// Open the host-scoped registration window: add-ons register here and
		// hosts call add_module_dir() here, so it fires even if a subsystem is
		// off (registrations for a disabled subsystem simply never boot).
		do_action("bpem/register/{$this->get_slug()}", $this);

		// Evaluate + boot this host's extensions.
		if ($enable_ext) {
			ExtensionRegistry::instance()->boot_host($this);
		}

		// Evaluate + load this host's installed modules (front end + admin).
		if ($enable_mod) {
			$this->modules()->boot();
		}

		do_action("bpem/{$this->get_slug()}/booted", $this);
	}

	/**
	 * Whether the extensions subsystem is enabled for this host (default true).
	 *
	 * Filter `bpem/{slug}/enable_extensions` can decide dynamically — e.g. gate on
	 * the host's license via `$m->is_premium()`.
	 */
	public function extensions_enabled(): bool
	{
		$on = false !== $this->get_config('enable_extensions', true);

		/**
		 * Filter whether the extensions subsystem is enabled for this host.
		 *
		 * @param bool    $on Enabled (from config, default true).
		 * @param Manager $m  Host manager.
		 */
		return (bool) apply_filters("bpem/{$this->get_slug()}/enable_extensions", $on, $this);
	}

	/**
	 * Whether the modules subsystem is enabled for this host (default true).
	 *
	 * Filter `bpem/{slug}/enable_modules` can decide dynamically — e.g. disable the
	 * whole Modules feature on a free host with `$on && $m->is_premium()`.
	 */
	public function modules_enabled(): bool
	{
		$on = false !== $this->get_config('enable_modules', true);

		/**
		 * Filter whether the modules subsystem is enabled for this host.
		 *
		 * @param bool    $on Enabled (from config, default true).
		 * @param Manager $m  Host manager.
		 */
		return (bool) apply_filters("bpem/{$this->get_slug()}/enable_modules", $on, $this);
	}

	/**
	 * Whether admins may upload a module .zip for this host (default true).
	 *
	 * Independent of the modules subsystem: set `enable_module_upload => false`
	 * (or return false from the filter) to hide the "Upload Module" control and
	 * skip its REST route while catalog install + delete stay available. Handy for
	 * a curated host that only wants modules from its own catalog.
	 *
	 * Filter `bpem/{slug}/enable_module_upload` can decide dynamically.
	 */
	public function module_upload_enabled(): bool
	{
		$on = false !== $this->get_config('enable_module_upload', true);

		/**
		 * Filter whether admins may upload a module .zip for this host.
		 *
		 * @param bool    $on Enabled (from config, default true).
		 * @param Manager $m  Host manager.
		 */
		return (bool) apply_filters("bpem/{$this->get_slug()}/enable_module_upload", $on, $this);
	}

	/**
	 * Whether the in-context Freemius Checkout is enabled for this host (default true).
	 *
	 * When on (and the extensions subsystem is enabled), the admin page loads the
	 * Freemius Checkout widget so a paid extension can be bought without leaving the
	 * site. Turn it off to keep the classic "Buy Now" link-out behavior.
	 *
	 * Filter `bpem/{slug}/enable_freemius_checkout` can decide dynamically.
	 */
	public function freemius_checkout_enabled(): bool
	{
		$on = $this->extensions_enabled() && false !== $this->get_config('enable_freemius_checkout', true);

		/**
		 * Filter whether the in-context Freemius Checkout is enabled for this host.
		 *
		 * @param bool    $on Enabled (from config, default true; requires extensions on).
		 * @param Manager $m  Host manager.
		 */
		return (bool) apply_filters("bpem/{$this->get_slug()}/enable_freemius_checkout", $on, $this);
	}

	/* ---------------------------------------------------------------------- */

	/**
	 * Host slug.
	 */
	public function get_slug(): string
	{
		return $this->config['slug'];
	}

	/**
	 * Read a config value.
	 *
	 * @param string $key     Config key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public function get_config(string $key, $default = null)
	{
		return array_key_exists($key, $this->config) ? $this->config[$key] : $default;
	}

	/**
	 * Register an add-on with this host (delegates to the registry).
	 *
	 * @param BaseExtension $ext Add-on instance.
	 */
	public function register(BaseExtension $ext): void
	{
		ExtensionRegistry::instance()->register($this, $ext);
	}

	/**
	 * All registered extensions for this host.
	 *
	 * @return array<string,BaseExtension> Keyed by extension id.
	 */
	public function get_extensions(): array
	{
		return ExtensionRegistry::instance()->get_extensions($this->get_slug());
	}

	/**
	 * Whether an extension is enabled. Default FALSE (disabled until enabled).
	 *
	 * @param string $ext_id Extension id.
	 */
	public function is_enabled(string $ext_id): bool
	{
		return in_array($ext_id, $this->get_enabled_ids(), true);
	}

	/**
	 * Enable an extension (persists).
	 *
	 * @param string $ext_id Extension id.
	 */
	public function enable(string $ext_id): void
	{
		$ids = $this->get_enabled_ids();
		if (!in_array($ext_id, $ids, true)) {
			$ids[] = $ext_id;
			$this->save_enabled_ids($ids);
			ExtensionRegistry::instance()->clear_status($this->get_slug(), $ext_id);
		}
	}

	/**
	 * Disable an extension (persists).
	 *
	 * @param string $ext_id Extension id.
	 */
	public function disable(string $ext_id): void
	{
		$ids = array_values(array_diff($this->get_enabled_ids(), array($ext_id)));
		$this->save_enabled_ids($ids);
		ExtensionRegistry::instance()->clear_status($this->get_slug(), $ext_id);
	}

	/**
	 * Whether the host holds the Max Plan (unlocks every add-on).
	 *
	 * Uses Freemius is_plan( $id, true ): this plan OR any higher plan.
	 */
	public function is_max_plan(): bool
	{
		$fs = $this->get_freemius();
		$pid = $this->get_config('max_plan_id');
		if (!$fs || !$pid) {
			return false;
		}
		try {
			return (bool) $fs->is_plan($pid, true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * Whether the host plugin itself is premium (licensed/paying).
	 */
	public function is_premium(): bool
	{
		if ($this->is_max_plan()) {
			return true;
		}
		$fs = $this->get_freemius();
		if (!$fs) {
			return false;
		}
		try {
			return method_exists($fs, 'can_use_premium_code') && $fs->can_use_premium_code();
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * Whether the host holds a currently valid (active, non-expired) premium license.
	 *
	 * Stricter than {@see is_premium()}, and the correct gate for a paid or
	 * premium-host-only module: Freemius keeps `can_use_premium_code()` returning
	 * true for an EXPIRED license by default (its "keep premium features after the
	 * license expires" behavior), so `is_premium()` alone would let such a module
	 * keep loading after the host's license lapses or is deactivated. Treating an
	 * expired license as unlicensed is what actually stops the module.
	 */
	public function is_premium_active(): bool
	{
		if ($this->is_max_plan()) {
			return true;
		}
		$fs = $this->get_freemius();
		if (!$fs) {
			return false;
		}
		try {
			// Must be running premium code with a way to use it (a deactivated
			// license on a freemium host already fails here).
			if (!method_exists($fs, 'can_use_premium_code') || !$fs->can_use_premium_code()) {
				return false;
			}
			// …and that license must not have expired.
			if (method_exists($fs, 'is_expired') && $fs->is_expired()) {
				return false;
			}
			return true;
		} catch (\Throwable $e) {
			return false;
		}
	}


	/**
	 * Resolve the host's Freemius instance from config (callable name, callable, or object).
	 *
	 * @return object|null
	 */
	public function get_freemius()
	{
		if (false !== $this->freemius) {
			return $this->freemius;
		}

		$raw = $this->get_config('freemius');
		$this->freemius = null;

		if (is_string($raw) && function_exists($raw)) {
			$this->freemius = call_user_func($raw);
		} elseif ($raw instanceof \Closure) {
			$this->freemius = call_user_func($raw);
		} elseif (is_object($raw)) {
			$this->freemius = $raw; // A Freemius instance.
		} elseif (is_callable($raw)) {
			$this->freemius = call_user_func($raw);
		}

		return $this->freemius;
	}

	/**
	 * Shared CatalogService.
	 */
	public function catalog(): CatalogService
	{
		return $this->catalog ??= new CatalogService();
	}

	/**
	 * Shared ModuleService (discovery, gating, install/delete, boot).
	 */
	public function modules(): ModuleService
	{
		return $this->modules ??= new ModuleService($this);
	}

	/**
	 * Register a read-only module directory bundled inside the host plugin.
	 *
	 * Modules discovered here appear in the Modules tab and can be toggled, but
	 * are NOT deletable and are never overwritten by uploads (uploads always go
	 * to the managed uploads dir). Call from the `bpem/register/{slug}` hook so
	 * it is registered before modules boot.
	 *
	 * Example: $manager->add_module_dir( __DIR__ . '/modules' );
	 *
	 * @param string $dir Absolute path to a directory of module folders.
	 */
	public function add_module_dir(string $dir): void
	{
		$dir = untrailingslashit(wp_normalize_path($dir));
		if ('' !== $dir && !in_array($dir, $this->bundled_module_dirs, true)) {
			$this->bundled_module_dirs[] = $dir;
		}
	}

	/**
	 * Read-only bundled module directories (config + registered + filtered).
	 *
	 * @return string[]
	 */
	public function get_bundled_module_dirs(): array
	{
		$dirs = $this->bundled_module_dirs;

		$config = $this->get_config('bundled_modules_dir');
		foreach ((array) $config as $dir) {
			if (is_string($dir) && '' !== $dir) {
				$dirs[] = untrailingslashit(wp_normalize_path($dir));
			}
		}

		/**
		 * Filter the read-only bundled module directories for a host.
		 *
		 * @param string[] $dirs Absolute directory paths.
		 * @param Manager  $m    Host manager.
		 */
		$dirs = (array) apply_filters("bpem/{$this->get_slug()}/bundled_module_dirs", $dirs, $this);

		return array_values(array_unique(array_filter(array_map('strval', $dirs))));
	}

	/**
	 * Whether the host holds a specific Freemius plan (this plan OR higher).
	 *
	 * Used to gate a module that declares a `Requires Plan` id.
	 *
	 * @param string $plan_id Plan id.
	 */
	public function is_plan(string $plan_id): bool
	{
		$fs = $this->get_freemius();
		if (!$fs || '' === $plan_id) {
			return false;
		}
		try {
			return method_exists($fs, 'is_plan') && (bool) $fs->is_plan($plan_id, true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/* ------------------------------ modules ------------------------------- */

	/**
	 * Whether a module is enabled. Falls back to the module's declared default.
	 *
	 * @param string $id      Module id.
	 * @param bool   $default Default state when the admin has not toggled it.
	 */
	public function is_module_enabled(string $id, bool $default = true): bool
	{
		$state = $this->get_modules_state();
		return array_key_exists($id, $state) ? (bool) $state[$id] : $default;
	}

	/**
	 * Enable a module (persists).
	 *
	 * @param string $id Module id.
	 */
	public function enable_module(string $id): void
	{
		$state = $this->get_modules_state();
		$state[$id] = true;
		$this->save_modules_state($state);
		$this->modules()->clear_status($id);
	}

	/**
	 * Disable a module (persists).
	 *
	 * @param string $id Module id.
	 */
	public function disable_module(string $id): void
	{
		$state = $this->get_modules_state();
		$state[$id] = false;
		$this->save_modules_state($state);
		$this->modules()->clear_status($id);
	}

	/**
	 * Drop a module's persisted enable state (called after a delete).
	 *
	 * @param string $id Module id.
	 */
	public function forget_module(string $id): void
	{
		$state = $this->get_modules_state();
		if (array_key_exists($id, $state)) {
			unset($state[$id]);
			$this->save_modules_state($state);
		}
		$this->modules()->clear_status($id);
	}

	/**
	 * Option name holding this host's per-module enable states.
	 */
	public function modules_state_option_name(): string
	{
		return "bpem_{$this->get_slug()}_modules_state";
	}

	/**
	 * Persisted per-module enable states: [ id => bool ].
	 *
	 * @return array<string,bool>
	 */
	private function get_modules_state(): array
	{
		$state = get_option($this->modules_state_option_name(), array());
		if (!is_array($state)) {
			return array();
		}
		$out = array();
		foreach ($state as $id => $on) {
			$out[(string) $id] = (bool) $on;
		}
		return $out;
	}

	/**
	 * Persist per-module enable states (no autoload).
	 *
	 * @param array<string,bool> $state States.
	 */
	private function save_modules_state(array $state): void
	{
		update_option($this->modules_state_option_name(), $state, false);
	}

	/* ----------------------- persistence helpers -------------------------- */

	/**
	 * Option name holding this host's enabled extension ids.
	 */
	public function enabled_option_name(): string
	{
		return "bpem_{$this->get_slug()}_enabled";
	}

	/**
	 * Enabled extension ids for this host.
	 *
	 * @return string[]
	 */
	private function get_enabled_ids(): array
	{
		$ids = get_option($this->enabled_option_name(), array());
		return is_array($ids) ? array_values(array_filter(array_map('strval', $ids))) : array();
	}

	/**
	 * Persist the enabled ids (no autoload — small array, rarely read on front end).
	 *
	 * @param string[] $ids Enabled ids.
	 */
	private function save_enabled_ids(array $ids): void
	{
		update_option($this->enabled_option_name(), array_values(array_unique($ids)), false);
	}

	/**
	 * Collected boot/config errors, shown to admins as a notice.
	 *
	 * @var string[]
	 */
	private static $boot_errors = array();

	/**
	 * Whether the admin-notice renderer has been hooked.
	 *
	 * @var bool
	 */
	private static $notice_hooked = false;

	/**
	 * Record a fatal config problem: log it (under WP_DEBUG) AND surface it as a
	 * dismissible admin notice so a misconfiguration isn't silently swallowed.
	 *
	 * @param string $message Human-readable message.
	 */
	private static function fail_boot(string $message): void
	{
		self::$boot_errors[] = $message;

		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('[bpem] ' . $message); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		if (!self::$notice_hooked && function_exists('add_action')) {
			self::$notice_hooked = true;
			add_action('admin_notices', array(__CLASS__, 'render_boot_errors'));
		}
	}

	/**
	 * Print queued boot errors as admin notices (admins only).
	 */
	public static function render_boot_errors(): void
	{
		if (!current_user_can('manage_options')) {
			return;
		}
		foreach (self::$boot_errors as $message) {
			printf(
				'<div class="notice notice-error"><p><strong>bp-extension-manager:</strong> %s</p></div>',
				esc_html($message)
			);
		}
	}
}
