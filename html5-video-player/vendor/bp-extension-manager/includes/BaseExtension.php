<?php
/**
 * Abstract contract every add-on extends.
 *
 * @package BPEM
 */

namespace BPEM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A single add-on, targeting exactly one host plugin.
 */
abstract class BaseExtension {

	/* -------------------------------------------------------------------------
	 * Identity (unique within its host).
	 * ---------------------------------------------------------------------- */

	/**
	 * Stable unique id within the host (e.g. "measure-tool").
	 */
	abstract public function get_id(): string;

	/**
	 * Human display name.
	 */
	abstract public function get_name(): string;

	/**
	 * Add-on version.
	 */
	abstract public function get_version(): string;

	/* -------------------------------------------------------------------------
	 * Targeting — EXACTLY ONE host.
	 * ---------------------------------------------------------------------- */

	/**
	 * The single host `slug` this extension attaches to.
	 */
	abstract public function get_host_slug(): string;

	/* -------------------------------------------------------------------------
	 * Requirements (optional overrides).
	 * ---------------------------------------------------------------------- */

	/**
	 * Minimum parent (host) version required.
	 */
	public function get_min_parent_version(): string {
		return '0.0.0';
	}

	/**
	 * Plugin basenames that must be active, e.g. ['woocommerce/woocommerce.php'].
	 *
	 * @return string[]
	 */
	public function get_required_plugins(): array {
		return array();
	}

	/* -------------------------------------------------------------------------
	 * Licensing.
	 * ---------------------------------------------------------------------- */

	/**
	 * The add-on's Freemius instance, or null for a free add-on.
	 *
	 * @return object|null
	 */
	public function get_freemius() {
		return null;
	}

	/* -------------------------------------------------------------------------
	 * UI metadata.
	 * ---------------------------------------------------------------------- */

	/**
	 * UI metadata: icon_url, short_description, homepage_url, is_paid.
	 *
	 * @return array<string,mixed>
	 */
	public function get_meta(): array {
		return array();
	}

	/* -------------------------------------------------------------------------
	 * Lifecycle.
	 * ---------------------------------------------------------------------- */

	/**
	 * Run the add-on. Called ONLY when every gate passes (see ExtensionRegistry).
	 */
	abstract public function boot(): void;

	/* -------------------------------------------------------------------------
	 * Shared helpers (do not override).
	 * ---------------------------------------------------------------------- */

	/**
	 * Whether this add-on is paid (drives the UI license field).
	 */
	final public function is_paid(): bool {
		$meta = $this->get_meta();
		if ( isset( $meta['is_paid'] ) ) {
			return (bool) $meta['is_paid'];
		}
		return null !== $this->get_freemius();
	}

	/**
	 * Whether this extension, despite being free, can only be used on a premium host.
	 */
	public function is_premium_host_only(): bool {
		$meta = $this->get_meta();
		if ( isset( $meta['premium_host_only'] ) ) {
			return (bool) $meta['premium_host_only'];
		}
		return false;
	}

	/**
	 * Page-reload behavior after this add-on is toggled in the manager UI:
	 * '' (none, default), 'notice' (prompt the admin to reload), or 'auto'
	 * (reload the page as soon as the toggle succeeds). Declare it when the
	 * add-on registers admin surfaces (menus, scripts) that only appear or
	 * disappear on the next full page load.
	 */
	public function get_reload_behavior(): string {
		$meta = $this->get_meta();
		return isset( $meta['reload'] ) ? (string) $meta['reload'] : '';
	}


	/**
	 * Normalize metadata for the REST/JSON payload.
	 *
	 * @return array<string,mixed>
	 */
	final public function meta_for_json(): array {
		$meta = $this->get_meta();
		return array(
			'icon_url'          => isset( $meta['icon_url'] ) ? esc_url_raw( (string) $meta['icon_url'] ) : '',
			'short_description' => isset( $meta['short_description'] ) ? (string) $meta['short_description'] : '',
			'homepage_url'      => isset( $meta['homepage_url'] ) ? esc_url_raw( (string) $meta['homepage_url'] ) : '',
			'author'            => isset( $meta['author'] ) ? (string) $meta['author'] : '',
			'author_url'        => isset( $meta['author_url'] ) ? esc_url_raw( (string) $meta['author_url'] ) : '',
		);
	}
}
