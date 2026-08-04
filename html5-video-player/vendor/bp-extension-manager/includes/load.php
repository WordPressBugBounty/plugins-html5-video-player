<?php
/**
 * Runs exactly once, from the winning (newest) copy only.
 *
 * Defines BPEM_LOADED (the winning copy's includes dir) and registers a PSR-4
 * autoloader mapping the `BPEM\` namespace onto this directory.
 *
 * BPEM_LOADED is defined exactly once (by the winner), so it never collides —
 * unlike a version constant, which every copy would try to define.
 *
 * @package BPEM
 */

if (!defined('ABSPATH')) {
	exit;
}

if (defined('BPEM_LOADED')) {
	return;
}

define('BPEM_LOADED', __DIR__);                          // Winning copy's includes/ dir.
define('BPEM_PATH', dirname(__DIR__));                 // Winning copy's library ROOT (build/ lives here).
define('BPEM_VERSION', '1.0.0');                         // Runtime version (winner only — safe, defined once).
// BPEM_URL is defined below, after the library-root URL is resolved.


/*
 * Resolve the URL of the library ROOT (not includes/), robust to layout.
 *
 * Map the normalized filesystem path onto a known base dir + its public URL.
 * This covers plugins/, mu-plugins, themes, or anywhere under wp-content / ABSPATH.
 * The plugins_url() fallback consults WordPress's symlink registry, which is
 * correct for a library properly vendored inside an active host plugin.
 *
 * For a symlinked dev install whose real path lives OUTSIDE the WordPress tree
 * (e.g. a repo symlinked into Local's wp-content/plugins), auto-detection cannot
 * recover the public URL — filter `bpem_asset_url` to set it explicitly.
 */
$bpem_root = wp_normalize_path(BPEM_PATH);
$bpem_url = '';

$bpem_bases = array(
	wp_normalize_path(WP_CONTENT_DIR) => content_url(),
	wp_normalize_path(ABSPATH) => site_url('/'),
);
if (defined('WP_PLUGIN_DIR')) {
	$bpem_bases = array(wp_normalize_path(WP_PLUGIN_DIR) => plugins_url()) + $bpem_bases;
}

foreach ($bpem_bases as $bpem_base_dir => $bpem_base_url) {
	$bpem_base_dir = trailingslashit($bpem_base_dir);
	if ('' !== $bpem_base_dir && 0 === strpos($bpem_root . '/', $bpem_base_dir)) {
		$bpem_url = trailingslashit($bpem_base_url) . substr($bpem_root . '/', strlen($bpem_base_dir));
		break;
	}
}

// The scan below touches the filesystem (scandir + realpath per plugin dir), so
// cache its result in a transient keyed by this path. Only the expensive
// scan-fallback is cached; the cheap prefix mapping above always wins first.
$bpem_url_cache_key = 'bpem_url_' . md5($bpem_root);
if ('' === $bpem_url) {
	$bpem_cached_url = get_transient($bpem_url_cache_key);
	if (is_string($bpem_cached_url) && '' !== $bpem_cached_url) {
		$bpem_url = $bpem_cached_url;
	}
}

if ('' === $bpem_url) {
	/*
	 * Symlinked install: the real path lives outside the WordPress tree, so the
	 * mapping above failed. Find the plugin/mu-plugin folder whose realpath() is
	 * this library (or an ancestor of it) and rebuild the URL from its public
	 * folder name + any nested suffix (e.g. /lib/bp-extension-manager).
	 */
	$bpem_scan = array();
	if (defined('WP_PLUGIN_DIR')) {
		$bpem_scan[wp_normalize_path(WP_PLUGIN_DIR)] = plugins_url();
	}
	if (defined('WPMU_PLUGIN_DIR')) {
		$bpem_scan[wp_normalize_path(WPMU_PLUGIN_DIR)] = content_url('mu-plugins');
	}

	foreach ($bpem_scan as $bpem_scan_dir => $bpem_scan_url) {
		// scandir + is_dir follows symlinks-to-directories (glob GLOB_ONLYDIR does not).
		$bpem_entries = @scandir($bpem_scan_dir); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if (!$bpem_entries) {
			continue;
		}
		foreach ($bpem_entries as $bpem_name) {
			if ('.' === $bpem_name || '..' === $bpem_name) {
				continue;
			}
			$bpem_full = $bpem_scan_dir . '/' . $bpem_name;
			if (!is_dir($bpem_full)) {
				continue;
			}
			$bpem_real = realpath($bpem_full);
			if (!$bpem_real) {
				continue;
			}
			$bpem_real = wp_normalize_path($bpem_real);
			if ($bpem_root === $bpem_real || 0 === strpos($bpem_root . '/', $bpem_real . '/')) {
				$bpem_suffix = substr($bpem_root, strlen($bpem_real)); // '' or '/lib/bp-extension-manager'
				$bpem_url = trailingslashit($bpem_scan_url) . ltrim($bpem_name . $bpem_suffix, '/');
				break 2;
			}
		}
	}

	// Cache a successful scan so it doesn't re-run every request. Keyed by path,
	// so moving/renaming the install invalidates it automatically.
	if ('' !== $bpem_url) {
		set_transient($bpem_url_cache_key, $bpem_url, WEEK_IN_SECONDS);
	}
}

if ('' === $bpem_url) {
	// Last resort: consults WP's $wp_plugin_paths symlink registry for vendored installs.
	$bpem_url = plugin_dir_url(BPEM_PATH . '/bp-extension-manager.php');
}

/**
 * Filter the library's base asset URL (must end with a slash).
 *
 * Use this when the library is symlinked from outside the WordPress tree and the
 * resolved URL is wrong (e.g. contains an absolute filesystem path).
 *
 * @param string $url Resolved library-root URL.
 */
if (!defined('BPEM_URL')) {
	/*
	 * Production: use the resolved URL (filterable via `bpem_asset_url`).
	 *
	 * Development: a symlinked install whose real path is OUTSIDE the WordPress
	 * tree cannot be auto-resolved, so define BPEM_URL yourself — in wp-config.php
	 * or a local mu-plugin — BEFORE this library loads, e.g.:
	 *
	 *   define('BPEM_URL', 'http://dev.local/wp-content/plugins/3d-viewer/vendor/bp-extension-manager/');
	 *
	 * A pre-defined constant always wins; nothing dev-specific is committed here.
	 */
	define('BPEM_URL', apply_filters('bpem_asset_url', trailingslashit($bpem_url)));
}

spl_autoload_register(
	function ($class) {
		if (strpos($class, 'BPEM\\') !== 0) {
			return;
		}
		$rel = str_replace('\\', '/', substr($class, strlen('BPEM\\')));
		$path = __DIR__ . '/' . $rel . '.php';
		if (is_readable($path)) {
			require_once $path;
		}
	}
);

/*
 * Load the library's own translations, once, from the winning copy.
 *
 * The text domain is a FIXED literal ('bp-extension-manager') because gettext
 * extraction (`wp i18n make-pot`) is static — it cannot resolve a variable or a
 * config value, so a dynamic domain would silently never translate. Hosts do NOT
 * supply this; the library ships and owns its own strings under /languages.
 *
 * Hooked on `init` because WordPress 6.7+ warns when translations load earlier.
 * The .mo is resolved by ABSOLUTE path so it works wherever the library is
 * vendored (plugins/, mu-plugins, a theme, or a nested /vendor dir) — a path
 * relative to WP_PLUGIN_DIR (as load_plugin_textdomain expects) would not.
 */
add_action(
	'init',
	function () {
		$domain = 'bp-extension-manager';
		/** This filter is documented in wp-includes/l10n.php */
		$locale = apply_filters('plugin_locale', determine_locale(), $domain);
		$mofile = BPEM_PATH . '/languages/' . $domain . '-' . $locale . '.mo';
		if (is_readable($mofile)) {
			load_textdomain($domain, $mofile);
		}
	}
);

// Instantiate the shared registry so it is ready before hosts boot their Managers.
\BPEM\ExtensionRegistry::instance();