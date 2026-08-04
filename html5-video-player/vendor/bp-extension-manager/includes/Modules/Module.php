<?php
/**
 * A single installed module: an on-disk, header-described feature package.
 *
 * @package BPEM
 */

namespace BPEM\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable value object describing one discovered module.
 *
 * A module is a folder under the host's managed modules dir whose main PHP file
 * carries a WordPress-style header block (parsed via get_file_data()). Unlike an
 * extension it is NOT a separate plugin and NOT registered in code — it is
 * uploaded/installed, toggled, and deleted by the admin, and its main file is
 * require'd when every gate passes.
 */
final class Module {

	/**
	 * Header field map: internal key => header label parsed by get_file_data().
	 *
	 * @var array<string,string>
	 */
	public static $headers = array(
		'name'              => 'Module Name',
		'version'           => 'Version',
		'description'       => 'Description',
		'author'            => 'Author',
		'author_uri'        => 'Author URI',
		'icon_url'          => 'Icon URL',
		'homepage_uri'      => 'Homepage URI',
		'requires_host'     => 'Requires Host',
		'requires_plugins'  => 'Requires Plugins',
		'premium'           => 'Premium',
		'premium_host_only' => 'Premium Host Only',
		'requires_plan'     => 'Requires Plan',
		'default_enabled'   => 'Default Enabled',
		'reload'            => 'Reload',
	);

	/**
	 * Standard WordPress plugin-header labels used as a fallback so an existing
	 * plugin can double as a module without editing its header. Only fields whose
	 * module label differs from the plugin label need an entry here — shared
	 * labels (Version, Description, Author, Author URI, Requires Plugins) are
	 * already picked up by {@see $headers}. A value is only taken from a fallback
	 * label when the primary module label is empty.
	 *
	 * @var array<string,string>
	 */
	public static $fallback_headers = array(
		'name'         => 'Plugin Name',
		'homepage_uri' => 'Plugin URI',
	);

	/**
	 * Sanitized module id (its folder name).
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Absolute path to the module's main PHP file (the one carrying the header).
	 *
	 * @var string
	 */
	private $main_file;

	/**
	 * Parsed header values (all string, as returned by get_file_data()).
	 *
	 * @var array<string,string>
	 */
	private $data;

	/**
	 * Whether this module lives in the writable managed dir (uploaded) and may
	 * be deleted. False for read-only modules bundled inside the host plugin.
	 *
	 * @var bool
	 */
	private $deletable;

	/**
	 * Constructor.
	 *
	 * @param string                $id        Sanitized module id.
	 * @param string                $main_file Absolute path to the main PHP file.
	 * @param array<string,string>  $data      Parsed header data.
	 * @param bool                  $deletable Whether the module can be deleted.
	 */
	public function __construct( string $id, string $main_file, array $data, bool $deletable = true ) {
		$this->id        = $id;
		$this->main_file = $main_file;
		$this->data      = $data;
		$this->deletable = $deletable;
	}

	/**
	 * Whether the module can be deleted (uploaded modules) vs read-only (bundled).
	 */
	public function is_deletable(): bool {
		return $this->deletable;
	}

	/**
	 * Module id (folder name).
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Absolute path to the main PHP file.
	 */
	public function get_main_file(): string {
		return $this->main_file;
	}

	/**
	 * Display name (falls back to the id).
	 */
	public function get_name(): string {
		$name = trim( (string) ( $this->data['name'] ?? '' ) );
		return '' !== $name ? $name : $this->id;
	}

	/**
	 * Version string.
	 */
	public function get_version(): string {
		$v = trim( (string) ( $this->data['version'] ?? '' ) );
		return '' !== $v ? $v : '0.0.0';
	}

	/**
	 * Minimum host version required.
	 */
	public function get_min_host_version(): string {
		$v = trim( (string) ( $this->data['requires_host'] ?? '' ) );
		return '' !== $v ? $v : '0.0.0';
	}

	/**
	 * Required active plugin basenames parsed from the comma-separated header.
	 *
	 * @return string[]
	 */
	public function get_required_plugins(): array {
		$raw = (string) ( $this->data['requires_plugins'] ?? '' );
		if ( '' === trim( $raw ) ) {
			return array();
		}
		$parts = array_map( 'trim', explode( ',', $raw ) );
		return array_values( array_filter( $parts ) );
	}

	/**
	 * Optional host plan id that unlocks this module ('' when none).
	 */
	public function get_required_plan(): string {
		return trim( (string) ( $this->data['requires_plan'] ?? '' ) );
	}

	/**
	 * Whether the module is paid (requires host premium).
	 */
	public function is_paid(): bool {
		return $this->truthy( $this->data['premium'] ?? '' );
	}

	/**
	 * Whether a (free) module is usable only on a premium host.
	 */
	public function is_premium_host_only(): bool {
		return $this->truthy( $this->data['premium_host_only'] ?? '' );
	}

	/**
	 * Page-reload behavior after this module is toggled ('' | 'notice' | 'auto'),
	 * declared via the `Reload:` header.
	 */
	public function get_reload_behavior(): string {
		return trim( (string) ( $this->data['reload'] ?? '' ) );
	}

	/**
	 * Default enabled state for a freshly discovered module (default TRUE).
	 */
	public function is_enabled_by_default(): bool {
		$raw = trim( (string) ( $this->data['default_enabled'] ?? '' ) );
		if ( '' === $raw ) {
			return true; // Modules ship on unless a header opts out.
		}
		return $this->truthy( $raw );
	}

	/**
	 * UI metadata for the REST/JSON payload (mirrors BaseExtension::meta_for_json()).
	 *
	 * @return array<string,string>
	 */
	public function meta_for_json(): array {
		return array(
			'icon_url'          => esc_url_raw( (string) ( $this->data['icon_url'] ?? '' ) ),
			'short_description' => (string) ( $this->data['description'] ?? '' ),
			'homepage_url'      => esc_url_raw( (string) ( $this->data['homepage_uri'] ?? '' ) ),
			'author'            => (string) ( $this->data['author'] ?? '' ),
			'author_url'        => esc_url_raw( (string) ( $this->data['author_uri'] ?? '' ) ),
		);
	}

	/**
	 * Interpret a header string as a boolean flag.
	 *
	 * @param mixed $value Raw header value.
	 */
	private function truthy( $value ): bool {
		$value = strtolower( trim( (string) $value ) );
		return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
	}
}
