<?php
/**
 * Fetches the remote catalog, caches it, and merges it with installed extensions.
 *
 * @package BPEM
 */

namespace BPEM\Catalog;

use BPEM\BaseExtension;
use BPEM\ExtensionRegistry;
use BPEM\Manager;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Produces the merged Extension[] payload the admin UI consumes.
 */
final class CatalogService
{

	const SCHEMA = 1;

	/**
	 * Required fields for a remote catalog entry.
	 *
	 * @var string[]
	 */
	private static $required_fields = array('id', 'name', 'version');

	/**
	 * Per-request memo of validated remote entries, keyed by host slug.
	 * Static so it is shared across the multiple CatalogService instances
	 * created within one request.
	 *
	 * @var array<string,array<int,array<string,mixed>>>
	 */
	private static $remote_memo = array();

	/**
	 * Per-request memo of validated remote MODULE entries, keyed by host slug.
	 *
	 * @var array<string,array<int,array<string,mixed>>>
	 */
	private static $module_memo = array();

	/**
	 * Per-request memo of raw decoded catalog documents, keyed by URL, so that
	 * extensions and modules sharing one catalog_url only trigger a single HTTP GET.
	 *
	 * @var array<string,array<string,mixed>|null>
	 */
	private static $raw_memo = array();

	/**
	 * Whether a string is a safe plugin basename (e.g. "foo/foo.php" or "foo.php").
	 *
	 * Rejects path traversal and stray characters so a catalog-supplied
	 * `plugin_file` can never escape WP_PLUGIN_DIR or name an arbitrary path.
	 *
	 * @param string $plugin_file Candidate basename.
	 */
	public static function is_plugin_file_safe(string $plugin_file): bool
	{
		if ('' === $plugin_file || false !== strpos($plugin_file, '..')) {
			return false;
		}
		return (bool) preg_match('#^[A-Za-z0-9_\-]+(/[A-Za-z0-9_.\-]+)?\.php$#', $plugin_file);
	}

	/**
	 * Sanitize raw Freemius Checkout parameters into the subset the in-context
	 * checkout widget needs. Returns an empty array unless BOTH a numeric
	 * `plugin_id` and a well-formed `public_key` (pk_…) are present — those two
	 * are the minimum the overlay requires, so an incomplete config disables it
	 * (the UI then falls back to the external "Buy Now" link).
	 *
	 * @param mixed $raw Raw params (from a catalog entry, filter, or SDK).
	 * @return array<string,string> { plugin_id, public_key, plan_id?, pricing_id? }
	 */
	public static function sanitize_checkout_params($raw): array
	{
		if (!is_array($raw)) {
			return array();
		}

		$plugin_id  = isset($raw['plugin_id']) ? preg_replace('/\D+/', '', (string) $raw['plugin_id']) : '';
		$public_key = isset($raw['public_key']) ? trim((string) $raw['public_key']) : '';

		// Freemius public keys are safe to expose client-side (that is their
		// purpose), but only accept the documented `pk_` + alphanumeric shape so a
		// malformed value never lands in the page.
		if (!preg_match('/^pk_[A-Za-z0-9]+$/', $public_key)) {
			$public_key = '';
		}

		if ('' === $plugin_id || '' === $public_key) {
			return array();
		}

		$params = array(
			'plugin_id'  => $plugin_id,
			'public_key' => $public_key,
		);

		// Optional preselections — numeric ids only.
		foreach (array('plan_id', 'pricing_id') as $key) {
			if (isset($raw[$key]) && '' !== (string) $raw[$key]) {
				$digits = preg_replace('/\D+/', '', (string) $raw[$key]);
				if ('' !== $digits) {
					$params[$key] = $digits;
				}
			}
		}

		return $params;
	}

	/**
	 * Best-effort derivation of checkout params from a live Freemius instance
	 * (used for an installed add-on). Every accessor is guarded — a flaky or
	 * differently-versioned SDK must never fatal while shaping the payload.
	 *
	 * @param object|null $fs Freemius instance, or null.
	 * @return array<string,string>
	 */
	public static function checkout_params_from_freemius($fs): array
	{
		if (!is_object($fs)) {
			return array();
		}

		$plugin_id  = '';
		$public_key = '';
		try {
			if (method_exists($fs, 'get_id')) {
				$plugin_id = (string) $fs->get_id();
			}
			if (method_exists($fs, 'get_public_key')) {
				$public_key = (string) $fs->get_public_key();
			} elseif (method_exists($fs, 'get_plugin')) {
				$plugin = $fs->get_plugin();
				if (is_object($plugin) && isset($plugin->public_key)) {
					$public_key = (string) $plugin->public_key;
				}
			}
		} catch (\Throwable $e) {
			return array();
		}

		return self::sanitize_checkout_params(
			array(
				'plugin_id'  => $plugin_id,
				'public_key' => $public_key,
			)
		);
	}

	/**
	 * Merged list: installed ∪ remote, de-duped by id. Installed wins.
	 *
	 * @param Manager $m Host manager.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_merged(Manager $m): array
	{
		$registry = ExtensionRegistry::instance();
		$max_plan = $m->is_max_plan();

		$merged = array();

		// 1. Installed (registered) extensions take precedence.
		foreach ($m->get_extensions() as $ext_id => $ext) {
			$merged[$ext_id] = $this->shape_installed($m, $ext, $registry, $max_plan);
		}

		// 2. Remote-only entries.
		foreach ($this->fetch_remote($m) as $entry) {
			$id = $entry['id'];
			if (isset($merged[$id])) {
				continue; // Installed wins.
			}
			$merged[$id] = $this->shape_remote($entry, $max_plan, $m);
		}

		$list = array_values($merged);

		/**
		 * Filter the merged catalog for a host.
		 *
		 * @param array   $list Merged Extension[] payload.
		 * @param Manager $m    Host manager.
		 */
		return apply_filters("bpem/{$m->get_slug()}/catalog", $list, $m);
	}

	/**
	 * Find a single catalog entry by id (used by the Installer for server-side URL resolution).
	 *
	 * @param Manager $m  Host manager.
	 * @param string  $id Extension id.
	 * @return array<string,mixed>|null
	 */
	public function find_remote_entry(Manager $m, string $id): ?array
	{
		foreach ($this->fetch_remote($m) as $entry) {
			if ($entry['id'] === $id) {
				return $entry;
			}
		}
		return null;
	}

	/**
	 * Validated remote MODULE entries (from the `modules` array of the catalog).
	 *
	 * Reads `modules_catalog_url` when set, otherwise the shared `catalog_url`.
	 *
	 * @param Manager $m Host manager.
	 * @return array<int,array<string,mixed>>
	 */
	public function fetch_remote_modules(Manager $m): array
	{
		$slug = $m->get_slug();
		if (array_key_exists($slug, self::$module_memo)) {
			return self::$module_memo[$slug];
		}
		return self::$module_memo[$slug] = $this->fetch_remote_modules_uncached($m);
	}

	/**
	 * Find a single remote module entry by id (used by the module installer).
	 *
	 * @param Manager $m  Host manager.
	 * @param string  $id Module id.
	 * @return array<string,mixed>|null
	 */
	public function find_remote_module(Manager $m, string $id): ?array
	{
		foreach ($this->fetch_remote_modules($m) as $entry) {
			if ($entry['id'] === $id) {
				return $entry;
			}
		}
		return null;
	}

	/**
	 * Drop this host's cached catalog (extensions + modules) plus the per-request
	 * memos, so the next fetch re-reads the source instead of a stale copy.
	 *
	 * Call after a state-changing action such as install/activate: the refetch
	 * that immediately follows then reflects reality — e.g. a corrected
	 * `plugin_file` in an updated catalog — rather than a cached entry that
	 * predates the change and leaves the card stranded on "Install".
	 *
	 * @param Manager $m Host manager.
	 */
	public static function flush_cache(Manager $m): void
	{
		$slug = $m->get_slug();
		delete_transient("bpem_{$slug}_catalog");
		delete_transient("bpem_{$slug}_modules_catalog");

		// Also clear the per-request memos so a re-fetch within this same request
		// (e.g. the response shaper) cannot repopulate the transient from stale data.
		unset(self::$remote_memo[$slug], self::$module_memo[$slug]);
		self::$raw_memo = array();
	}

	/* ---------------------------------------------------------------------- */

	/**
	 * Shape an installed (registered) extension into the response array.
	 *
	 * @param Manager           $m        Host manager.
	 * @param BaseExtension     $ext      Add-on.
	 * @param ExtensionRegistry $registry Registry.
	 * @param bool              $max_plan Whether the host holds the Max Plan.
	 * @return array<string,mixed>
	 */
	private function shape_installed(Manager $m, BaseExtension $ext, ExtensionRegistry $registry, bool $max_plan): array
	{
		$id = $ext->get_id();
		$status = $registry->get_status($m->get_slug(), $id);

		$is_paid = $ext->is_paid();
		$is_premium_only = method_exists($ext, 'is_premium_host_only') && $ext->is_premium_host_only();

		// Run the real license gate — never infer it from $status: a disabled
		// extension short-circuits evaluate() before the license step, so its
		// status can never read "unlicensed" while the toggle is off.
		$licensed = $registry->check_license($m, $ext);

		// In-context Freemius checkout, derived from the add-on's own SDK. Only
		// exposed for a paid add-on that is not yet licensed (nothing to buy
		// otherwise) — and never sent to a user who can't install plugins.
		$checkout = ($is_paid && !$licensed && current_user_can('install_plugins'))
			? self::checkout_params_from_freemius($ext->get_freemius())
			: array();

		$shaped = array(
			'id' => $id,
			'name' => $ext->get_name(),
			'version' => $ext->get_version(),
			'status' => $status,
			'installed' => true,
			'enabled' => $m->is_enabled($id),
			'compatible' => ExtensionRegistry::STATUS_INCOMPATIBLE !== $status,
			'missing_plugins' => $this->missing_plugins($ext->get_required_plugins()),
			'is_paid' => $is_paid,
			'premium_host_only' => $is_premium_only,
			'licensed' => (bool) $licensed,
			'max_plan' => $max_plan,
			'reload' => self::sanitize_reload($ext->get_reload_behavior()),
			'meta' => $ext->meta_for_json(),
			'checkout' => $this->filter_checkout_params($m, $id, $checkout),
			'available' => array(
				'installable' => false,
				'price_label' => '',
			),
		);

		/**
		 * Filter a single extension's computed payload.
		 *
		 * @param array         $shaped Extension payload.
		 * @param BaseExtension $ext    Add-on.
		 * @param Manager       $m      Host manager.
		 */
		return apply_filters("bpem/{$m->get_slug()}/extension_status", $shaped, $ext, $m);
	}

	/**
	 * Shape a remote-only (not installed) catalog entry.
	 *
	 * @param array<string,mixed> $entry    Validated remote entry.
	 * @param bool                $max_plan Whether the host holds the Max Plan.
	 * @param Manager             $m        Host manager.
	 * @return array<string,mixed>
	 */
	private function shape_remote(array $entry, bool $max_plan, Manager $m): array
	{
		$is_paid = !empty($entry['is_paid']);
		$is_premium_only = !empty($entry['premium_host_only']);
		$plugin_file = isset($entry['plugin_file']) ? (string) $entry['plugin_file'] : '';

		// Only trust a well-formed plugin basename — never a traversal/arbitrary path.
		$safe_file = self::is_plugin_file_safe($plugin_file) ? $plugin_file : '';

		// Detect whether the underlying plugin is physically present / active,
		// independent of whether it has registered itself as a managed extension.
		$file_present = '' !== $safe_file && $this->plugin_file_exists($safe_file);
		$file_active = '' !== $safe_file && $this->plugin_is_active($safe_file);

		if ($is_premium_only) {
			$licensed = $m->is_premium();
		} else {
			$licensed = $max_plan || !$is_paid;
		}

		$required = isset($entry['required_plugins']) ? $entry['required_plugins'] : (isset($entry['requires_plugins']) ? $entry['requires_plugins'] : array());

		// In-context Freemius checkout params, supplied by the catalog entry under
		// a `freemius` (or `checkout`) object. A remote-only entry has no live SDK
		// on this site, so the catalog is the only source. Offered only for a paid,
		// unlicensed, installable entry, and only to users who can install plugins.
		$raw_checkout = isset($entry['freemius']) ? $entry['freemius'] : (isset($entry['checkout']) ? $entry['checkout'] : array());
		$checkout = ($is_paid && !$licensed && !$file_present && current_user_can('install_plugins'))
			? self::sanitize_checkout_params($raw_checkout)
			: array();

		return array(
			'id' => $entry['id'],
			'name' => $entry['name'],
			'version' => $entry['version'],
			'status' => ExtensionRegistry::STATUS_DISABLED,
			'installed' => false, // Not a *managed* (registered) extension.
			'plugin_active' => $file_active,
			'plugin_present' => $file_present,
			'enabled' => false,
			'compatible' => true,
			'missing_plugins' => $this->missing_plugins($required),
			'is_paid' => $is_paid,
			'premium_host_only' => $is_premium_only,
			'licensed' => (bool) $licensed,
			'max_plan' => $max_plan,
			'reload' => self::sanitize_reload($entry['reload'] ?? ''),
			'checkout' => $this->filter_checkout_params($m, (string) $entry['id'], $checkout),
			'meta' => array(
				'icon_url' => esc_url_raw((string) ($entry['icon_url'] ?? '')),
				'short_description' => (string) ($entry['short_description'] ?? ''),
				'homepage_url' => esc_url_raw((string) ($entry['homepage_url'] ?? $entry['buy_url'] ?? '')),
				'author' => (string) ($entry['author'] ?? ''),
				'author_url' => esc_url_raw((string) ($entry['author_uri'] ?? $entry['author_url'] ?? '')),
			),
			'available' => array(
				// Already on disk → not installable (offer activation instead).
				'installable' => !$file_present,
				'price_label' => (string) ($entry['price_label'] ?? ''),
			),
		);
	}

	private function missing_plugins(array $required): array
	{
		return self::describe_missing_plugins($required);
	}

	/**
	 * Normalize a declared page-reload behavior to the supported set:
	 * 'auto' | 'notice' | '' (anything else).
	 *
	 * @param mixed $value Raw declared value (method, header, or catalog key).
	 */
	public static function sanitize_reload($value): string
	{
		$value = strtolower(trim((string) $value));
		return in_array($value, array('auto', 'notice'), true) ? $value : '';
	}

	/**
	 * Run the computed checkout params through the host filter and re-sanitize the
	 * result, so a filter can inject or override credentials (e.g. derive them from
	 * the host's own Freemius) but can never smuggle a malformed value into the page.
	 *
	 * @param Manager             $m        Host manager.
	 * @param string              $id       Extension id.
	 * @param array<string,mixed> $checkout Computed params (possibly empty).
	 * @return array<string,string>
	 */
	private function filter_checkout_params(Manager $m, string $id, array $checkout): array
	{
		/**
		 * Filter the in-context Freemius Checkout parameters for one extension.
		 *
		 * Return an array with `plugin_id` + `public_key` (and optional `plan_id`,
		 * `pricing_id`) to enable the overlay, or an empty array to fall back to the
		 * external "Buy Now" link.
		 *
		 * @param array   $checkout Computed params { plugin_id, public_key, plan_id?, pricing_id? }.
		 * @param string  $id       Extension id.
		 * @param Manager $m        Host manager.
		 */
		$filtered = apply_filters("bpem/{$m->get_slug()}/checkout_params", $checkout, $id, $m);

		return self::sanitize_checkout_params($filtered);
	}

	/**
	 * Describe every required plugin that is not currently active, with a
	 * context-aware action the admin can take to resolve it.
	 *
	 * Each entry is shaped as:
	 *   name      Friendly plugin name.
	 *   slug      Directory slug (e.g. "b-slider").
	 *   installed Whether the plugin is present on disk but inactive.
	 *   action    "activate" | "install" | "" (no actionable link for this user).
	 *   url       Admin URL for the action (activate link with nonce, or the
	 *             WP.org install search), or '' when none.
	 *
	 * @param string[] $required Plugin basenames, e.g. "b-slider/b-slider.php".
	 * @return array<int,array<string,mixed>>
	 */
	public static function describe_missing_plugins($required): array
	{
		$descriptors = self::normalize_required_plugins($required);
		if (empty($descriptors)) {
			return array();
		}
		if (!function_exists('is_plugin_active')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$missing = array();
		foreach ($descriptors as $dep) {
			$basename = $dep['file'];
			if (is_plugin_active($basename)) {
				continue;
			}

			$slug = self::plugin_slug($basename);
			$present = is_readable(WP_PLUGIN_DIR . '/' . $basename);
			$name = '' !== $dep['name'] ? $dep['name'] : self::get_plugin_name($basename);
			$catalog_url = $dep['url'];
			$action = '';
			$url = '';

			if ($present && current_user_can('activate_plugins')) {
				// Already on disk → one click to activate beats sending them off-site.
				$action = 'activate';
				$url = wp_nonce_url(
					self_admin_url('plugins.php?action=activate&plugin=' . rawurlencode($basename)),
					'activate-plugin_' . $basename
				);
			} elseif ('' !== $catalog_url) {
				// Catalog knows where to get this dependency (e.g. a premium store).
				$action = 'install';
				$url = $catalog_url;
			} elseif (!$present && current_user_can('install_plugins')) {
				// Fall back to the WP.org install search for org-hosted plugins.
				$action = 'install';
				$url = self_admin_url('plugin-install.php?tab=search&type=term&s=' . rawurlencode($slug));
			}

			$entry = array(
				'name' => $name,
				'slug' => $slug,
				'installed' => $present,
				'action' => $action,
				'url' => $url,
			);

			/**
			 * Filter a single missing-plugin descriptor before it reaches the UI.
			 *
			 * Lets hosts point premium/non-WP.org dependencies at their own store
			 * or documentation instead of the default WP.org install search.
			 *
			 * @param array  $entry    Descriptor (name, slug, installed, action, url).
			 * @param string $basename Required plugin basename.
			 */
			$missing[] = apply_filters('bpem_missing_plugin', $entry, $basename);
		}
		return $missing;
	}

	/**
	 * Directory slug for a plugin basename: "b-slider/b-slider.php" -> "b-slider".
	 *
	 * @param string $basename Plugin basename.
	 * @return string
	 */
	public static function plugin_slug(string $basename): string
	{
		$parts = explode('/', $basename);
		return count($parts) > 1 ? $parts[0] : basename($basename, '.php');
	}

	/**
	 * Normalize a raw `requires_plugins` / `required_plugins` value into a list of
	 * dependency descriptors.
	 *
	 * Accepts, per item, either:
	 *   - a plain basename string, e.g. "b-slider/b-slider.php"; or
	 *   - an object providing extra hints:
	 *       { "file": "b-slider/b-slider.php", "name": "B Slider",
	 *         "url": "https://bplugins.com/b-slider" }
	 *
	 * Items without a safe plugin basename are dropped, so a catalog-supplied
	 * value can never name an arbitrary path. Any `url` is protocol-restricted.
	 *
	 * @param mixed $raw String, string[], or array of descriptors.
	 * @return array<int,array{file:string,name:string,url:string}>
	 */
	public static function normalize_required_plugins($raw): array
	{
		if (is_string($raw)) {
			$raw = array($raw);
		}
		if (!is_array($raw)) {
			return array();
		}

		$out = array();
		foreach ($raw as $item) {
			if (is_string($item)) {
				$file = $item;
				$name = '';
				$url = '';
			} elseif (is_array($item)) {
				$file = isset($item['file']) ? (string) $item['file'] : (isset($item['basename']) ? (string) $item['basename'] : '');
				$name = isset($item['name']) ? (string) $item['name'] : '';
				$url = isset($item['url']) ? esc_url_raw((string) $item['url'], array('http', 'https')) : '';
			} else {
				continue;
			}

			if (!self::is_plugin_file_safe($file)) {
				continue;
			}

			$out[] = array(
				'file' => $file,
				'name' => $name,
				'url' => $url,
			);
		}
		return $out;
	}

	/**
	 * Safe plugin basenames from a raw `requires_plugins` value — for gate checks
	 * (is a dependency active?) that only need the file, not the display metadata.
	 *
	 * @param mixed $raw String, string[], or array of descriptors.
	 * @return string[]
	 */
	public static function required_plugin_files($raw): array
	{
		return array_map(
			static function (array $dep): string {
				return $dep['file'];
			},
			self::normalize_required_plugins($raw)
		);
	}

	/**
	 * Get a friendly name for a plugin basename.
	 *
	 * @param string $basename Plugin basename.
	 * @return string
	 */
	public static function get_plugin_name(string $basename): string
	{
		$file = WP_PLUGIN_DIR . '/' . $basename;
		if (is_readable($file)) {
			if (!function_exists('get_plugin_data')) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			try {
				$data = get_plugin_data($file, false, false);
				if (!empty($data['Name'])) {
					return $data['Name'];
				}
			} catch (\Throwable $e) {
				// Fall back.
			}
		}

		// Fallback formatting: "foo-bar/foo-bar.php" -> "Foo Bar"
		$parts = explode('/', $basename);
		$slug = count($parts) > 1 ? $parts[0] : basename($basename, '.php');
		return ucwords(str_replace(array('-', '_'), ' ', $slug));
	}

	/**
	 * Whether a plugin file is present on disk.
	 *
	 * @param string $plugin_file Plugin basename, e.g. "foo/foo.php".
	 */
	private function plugin_file_exists(string $plugin_file): bool
	{
		return is_readable(WP_PLUGIN_DIR . '/' . $plugin_file);
	}

	/**
	 * Whether a plugin file is active (network-active included).
	 *
	 * @param string $plugin_file Plugin basename, e.g. "foo/foo.php".
	 */
	private function plugin_is_active(string $plugin_file): bool
	{
		if (!function_exists('is_plugin_active')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active($plugin_file);
	}

	/**
	 * Fetch the remote catalog, memoized for the duration of the request.
	 *
	 * get_merged() can run several times per request (e.g. list + single after a
	 * toggle); under WP_DEBUG the transient cache is bypassed, so without this
	 * memo each call would block on a live HTTP request.
	 *
	 * @param Manager $m Host manager.
	 * @return array<int,array<string,mixed>> Validated entries (may be empty).
	 */
	private function fetch_remote(Manager $m): array
	{
		$slug = $m->get_slug();
		if (array_key_exists($slug, self::$remote_memo)) {
			return self::$remote_memo[$slug];
		}
		return self::$remote_memo[$slug] = $this->fetch_remote_uncached($m);
	}

	/**
	 * Fetch + validate + transient-cache the remote catalog.
	 *
	 * @param Manager $m Host manager.
	 * @return array<int,array<string,mixed>> Validated entries (may be empty).
	 */
	private function fetch_remote_uncached(Manager $m): array
	{
		$url = $m->get_config('catalog_url');
		if (!$url) {
			$file = $m->get_config('catalog_file');
			if ($file && file_exists($file) && is_readable($file)) {
				$data = self::load_php_catalog($file);
				if (is_array($data) && (int) ($data['schema'] ?? 0) === self::SCHEMA) {
					return $this->validate_entries($data['extensions'] ?? array());
				}
			}
			return array();
		}

		$transient = "bpem_{$m->get_slug()}_catalog";

		/**
		 * Whether to read/write the catalog transient cache. Disabled under
		 * WP_DEBUG so catalog edits show immediately during development.
		 *
		 * @param bool    $use_cache Default: true unless WP_DEBUG.
		 * @param Manager $m         Host manager.
		 */
		$use_cache = apply_filters(
			"bpem/{$m->get_slug()}/catalog_cache",
			!(defined('WP_DEBUG') && WP_DEBUG),
			$m
		);

		if ($use_cache) {
			$cached = get_transient($transient);
			if (is_array($cached)) {
				return $cached;
			}
		}

		$data = $this->http_get_catalog($url);
		if (null === $data) {
			return array(); // Don't cache a failed/unparseable fetch — retry next load.
		}

		$entries = $this->validate_entries($data['extensions'] ?? array());

		// Cache only a non-empty result, and only when caching is enabled, so a
		// transient misconfiguration self-heals on the next request.
		if ($use_cache && !empty($entries)) {
			set_transient($transient, $entries, max(60, (int) $m->get_config('catalog_ttl', 0)));
		}

		return $entries;
	}

	/**
	 * Fetch + validate + transient-cache the remote MODULE list.
	 *
	 * @param Manager $m Host manager.
	 * @return array<int,array<string,mixed>>
	 */
	private function fetch_remote_modules_uncached(Manager $m): array
	{
		$url = $m->get_config('modules_catalog_url');
		if (!$url) {
			$url = $m->get_config('catalog_url'); // Fall back to the shared catalog.
		}
		if (!$url) {
			$file = $m->get_config('catalog_file');
			if ($file && file_exists($file) && is_readable($file)) {
				$data = self::load_php_catalog($file);
				if (is_array($data) && (int) ($data['schema'] ?? 0) === self::SCHEMA) {
					return $this->validate_entries($data['modules'] ?? array());
				}
			}
			return array();
		}

		$transient = "bpem_{$m->get_slug()}_modules_catalog";

		/** This filter is shared with the extension catalog cache. */
		$use_cache = apply_filters(
			"bpem/{$m->get_slug()}/catalog_cache",
			!(defined('WP_DEBUG') && WP_DEBUG),
			$m
		);

		if ($use_cache) {
			$cached = get_transient($transient);
			if (is_array($cached)) {
				return $cached;
			}
		}

		$data = $this->http_get_catalog($url);
		if (null === $data) {
			return array();
		}

		$entries = $this->validate_entries($data['modules'] ?? array());

		if ($use_cache && !empty($entries)) {
			set_transient($transient, $entries, max(60, (int) $m->get_config('catalog_ttl', 0)));
		}

		return $entries;
	}

	/**
	 * GET + decode + schema-check a catalog document, memoized per URL for the
	 * request so extensions and modules sharing one URL fetch it only once.
	 *
	 * @param string $url Catalog URL.
	 * @return array<string,mixed>|null Decoded document, or null on failure.
	 */
	private function http_get_catalog(string $url): ?array
	{
		if (array_key_exists($url, self::$raw_memo)) {
			return self::$raw_memo[$url];
		}

		$local_path = null;
		if (defined('WP_CONTENT_DIR') && function_exists('content_url')) {
			$content_url = content_url();
			$url_host = wp_parse_url($url, PHP_URL_HOST);
			$site_host = wp_parse_url($content_url, PHP_URL_HOST);

			if (empty($url_host) || $url_host === $site_host) {
				$content_url_path = wp_parse_url($content_url, PHP_URL_PATH);
				$url_path = wp_parse_url($url, PHP_URL_PATH);

				// Only shortcut a static .json file. A dynamic endpoint (e.g. a PHP
				// file under wp-content that emits JSON) must go over HTTP so it can
				// run — reading it off disk would return its raw source, not output.
				$is_json = $url_path && '.json' === strtolower((string) substr($url_path, -5));

				if ($is_json && $content_url_path && 0 === strpos($url_path, $content_url_path)) {
					$relative_path = ltrim(substr($url_path, strlen($content_url_path)), '/');
					if (false === strpos($relative_path, '..')) {
						$resolved_path = WP_CONTENT_DIR . '/' . $relative_path;
						if (file_exists($resolved_path) && is_readable($resolved_path)) {
							$local_path = $resolved_path;
						}
					}
				}
			}
		}

		if ($local_path) {
			$body = file_get_contents($local_path);
			if (false === $body) {
				return self::$raw_memo[$url] = null;
			}
		} else {
			$response = wp_remote_get(esc_url_raw($url), array('timeout' => 10));
			if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
				return self::$raw_memo[$url] = null;
			}
			$body = wp_remote_retrieve_body($response);
		}

		$data = json_decode($body, true);
		if (!is_array($data) || (int) ($data['schema'] ?? 0) !== self::SCHEMA) {
			return self::$raw_memo[$url] = null;
		}

		return self::$raw_memo[$url] = $data;
	}

	/**
	 * Drop malformed entries; keep only those with the required fields.
	 *
	 * @param mixed $raw Raw "extensions" array from the catalog.
	 * @return array<int,array<string,mixed>>
	 */
	private function validate_entries($raw): array
	{
		if (!is_array($raw)) {
			return array();
		}
		$out = array();
		foreach ($raw as $entry) {
			if (!is_array($entry)) {
				continue;
			}
			$valid = true;
			foreach (self::$required_fields as $field) {
				if (empty($entry[$field]) || !is_string($entry[$field])) {
					$valid = false;
					break;
				}
			}
			if ($valid) {
				$entry['id'] = sanitize_key($entry['id']);
				$out[] = $entry;
			}
		}
		return $out;
	}

	/**
	 * Safely include a local PHP catalog file and return its array payload.
	 *
	 * @param string $file Absolute path to PHP file.
	 * @return array<string,mixed>|null
	 */
	private static function load_php_catalog(string $file): ?array
	{
		try {
			$data = include $file;
			return is_array($data) ? $data : null;
		} catch (\Throwable $e) {
			return null;
		}
	}
}
