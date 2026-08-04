<?php
/**
 * bp-extension-manager — Layer 1: version-safe bootstrap.
 *
 * This file is bundled inside multiple host plugins. Several copies (possibly at
 * different versions) may load in the same request. ONLY the newest copy is
 * allowed to define classes; every host shares that single winning copy.
 *
 * INVARIANTS (see CONTRIBUTING.md — do not break these):
 *   1. This file records version + path only. It NEVER requires class files.
 *   2. `bpem_register_copy` + `$GLOBALS['bpem_copies']` are frozen forever.
 *   3. No define() for the library version — use the local $bpem_this_version.
 *   4. Bump $bpem_this_version on every release.
 *
 * @package BPEM
 */

if (!defined('ABSPATH')) {
	exit;
}

$bpem_this_version = '1.0.0';   // Local var — NEVER a constant.
$bpem_this_file = __FILE__;

// ---- FROZEN: identical bytes in every released version, forever ----
if (!function_exists('bpem_register_copy')) {

	$GLOBALS['bpem_copies'] = array();

	/**
	 * Record a bundled copy of the library.
	 *
	 * @param string $version         Semantic version of the copy.
	 * @param string $bootstrap_file  Absolute path to that copy's bootstrap.php.
	 */
	function bpem_register_copy($version, $bootstrap_file)
	{
		$GLOBALS['bpem_copies'][$version] = $bootstrap_file;
	}

	add_action('plugins_loaded', 'bpem_boot_newest', -1000);

	/**
	 * Load the newest registered copy and announce readiness.
	 *
	 * Runs once. Sorts every recorded copy by version and requires the highest
	 * one's autoloader. Hosts boot their Manager on the `bpem_loaded` action.
	 */

	function bpem_boot_newest()
	{
		uksort($GLOBALS['bpem_copies'], 'version_compare');
		$newest_file = end($GLOBALS['bpem_copies']);          // Highest version wins.
		require_once dirname($newest_file) . '/includes/load.php';
		do_action('bpem_loaded');                             // Single entry point for hosts.
	}
}
// ------------------------------------------------------------------

bpem_register_copy($bpem_this_version, $bpem_this_file);
