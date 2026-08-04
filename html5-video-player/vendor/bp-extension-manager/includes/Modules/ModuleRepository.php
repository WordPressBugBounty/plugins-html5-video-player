<?php
/**
 * Filesystem layer for modules: discover, install (zip/url), and delete.
 *
 * @package BPEM
 */

namespace BPEM\Modules;

use BPEM\Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns the host-scoped managed modules directory
 * (uploads/bpem-modules/{slug}/{module-id}/) and every mutation of it.
 *
 * SECURITY: modules contain PHP the host later require()s, so installing one is
 * as sensitive as installing a plugin. Every mutation is capability-gated by the
 * caller (RestController) and this class additionally: validates uploaded zips
 * carry a real module header, sanitizes ids, confines all paths to base_dir(),
 * and hardens the directory against direct web access.
 */
final class ModuleRepository {

	/**
	 * Host manager.
	 *
	 * @var Manager
	 */
	private $manager;

	/**
	 * Per-request memo of discovered modules, keyed nothing (single host per repo).
	 *
	 * @var array<string,Module>|null
	 */
	private $memo = null;

	/**
	 * Constructor.
	 *
	 * @param Manager $manager Host manager.
	 */
	public function __construct( Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Absolute path to this host's managed modules directory (trailing slash).
	 *
	 * Filterable so a host can relocate storage if needed.
	 */
	public function base_dir(): string {
		$uploads = wp_upload_dir();
		$base    = trailingslashit( $uploads['basedir'] ) . 'bpem-modules/' . $this->manager->get_slug() . '/';

		/**
		 * Filter the managed modules directory (must end with a slash).
		 *
		 * @param string  $base Default: uploads/bpem-modules/{slug}/.
		 * @param Manager $m    Host manager.
		 */
		return (string) apply_filters( "bpem/{$this->manager->get_slug()}/modules_dir", $base, $this->manager );
	}

	/**
	 * Source directories to scan, in precedence order: the writable managed
	 * (uploads) dir first, then any read-only dirs bundled in the host plugin.
	 *
	 * @return array<string,bool> [ absolute dir => writable/deletable ].
	 */
	private function source_dirs(): array {
		$dirs = array( untrailingslashit( $this->base_dir() ) => true );
		foreach ( $this->manager->get_bundled_module_dirs() as $dir ) {
			$dir = untrailingslashit( $dir );
			if ( '' !== $dir && ! array_key_exists( $dir, $dirs ) ) {
				$dirs[ $dir ] = false; // Read-only (bundled).
			}
		}
		return $dirs;
	}

	/**
	 * All discovered modules, keyed by id. Memoized per request.
	 *
	 * Scans the writable managed dir first, then read-only bundled dirs; the
	 * first directory to define an id wins (an uploaded module overrides a
	 * bundled one of the same id).
	 *
	 * @return array<string,Module>
	 */
	public function all(): array {
		if ( null !== $this->memo ) {
			return $this->memo;
		}

		$this->memo = array();

		foreach ( $this->source_dirs() as $base => $deletable ) {
			$base = trailingslashit( $base );
			if ( ! is_dir( $base ) ) {
				continue;
			}
			$entries = @scandir( $base ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( ! $entries ) {
				continue;
			}
			foreach ( $entries as $name ) {
				if ( '.' === $name || '..' === $name ) {
					continue;
				}
				$dir = $base . $name;
				if ( ! is_dir( $dir ) ) {
					continue;
				}
				$id = sanitize_key( $name );
				if ( '' === $id || isset( $this->memo[ $id ] ) ) {
					continue; // Higher-precedence dir already defined this id.
				}
				$module = $this->load_from_dir( $id, $dir, $deletable );
				if ( $module ) {
					$this->memo[ $id ] = $module;
				}
			}
		}

		return $this->memo;
	}

	/**
	 * Find one module by id.
	 *
	 * @param string $id Module id.
	 */
	public function find( string $id ): ?Module {
		$id = sanitize_key( $id );
		return $this->all()[ $id ] ?? null;
	}

	/**
	 * Whether a module id is installed on disk.
	 *
	 * @param string $id Module id.
	 */
	public function exists( string $id ): bool {
		return null !== $this->find( $id );
	}

	/* ---------------------------------------------------------------------- */

	/**
	 * Install a module from an uploaded zip file (a $_FILES tmp path).
	 *
	 * @param string $tmp_zip Absolute path to the uploaded temp zip.
	 * @return array{success:bool,status:string,message?:string,id?:string}
	 */
	public function install_zip( string $tmp_zip ): array {
		if ( ! is_readable( $tmp_zip ) ) {
			return $this->fail( __( 'Uploaded file could not be read.', 'bp-extension-manager' ) );
		}

		if ( ! $this->init_filesystem() ) {
			return $this->fail( __( 'Could not access the filesystem.', 'bp-extension-manager' ) );
		}

		// Unzip into a throwaway staging dir first, so validation happens before
		// anything lands in the live modules directory.
		$staging = trailingslashit( get_temp_dir() ) . 'bpem-mod-' . wp_generate_password( 12, false );
		$result  = unzip_file( $tmp_zip, $staging );
		if ( is_wp_error( $result ) ) {
			return $this->fail( $result->get_error_message() );
		}

		$validated = $this->validate_staging( $staging );
		if ( is_wp_error( $validated ) ) {
			global $wp_filesystem;
			$wp_filesystem->delete( $staging, true );
			return $this->fail( $validated->get_error_message() );
		}

		// $validated = [ 'id' => string, 'source' => absolute dir holding the module ].
		$id     = $validated['id'];
		$source = $validated['source'];
		$dest   = $this->base_dir() . $id;

		$this->ensure_base_dir();

		global $wp_filesystem;

		// Replace any existing copy (upload = install or update).
		if ( $wp_filesystem->is_dir( $dest ) ) {
			$wp_filesystem->delete( $dest, true );
		}

		if ( ! $this->move_dir( $source, $dest ) ) {
			$wp_filesystem->delete( $staging, true );
			return $this->fail( __( 'Could not write the module to disk.', 'bp-extension-manager' ) );
		}

		$wp_filesystem->delete( $staging, true );
		$this->memo = null; // Invalidate discovery cache.

		return array(
			'success' => true,
			'status'  => 'installed',
			'id'      => $id,
		);
	}

	/**
	 * Download a module zip from a URL (host-allowlisted) and install it.
	 *
	 * @param string $url Download URL (already resolved server-side).
	 * @return array{success:bool,status:string,message?:string,id?:string}
	 */
	public function install_from_url( string $url ): array {
		if ( ! $this->host_allowed( $url ) ) {
			return $this->fail( __( 'Download host is not allowed.', 'bp-extension-manager' ) );
		}

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Cache-bust: the same URL may serve a replaced file on the server.
		$url = add_query_arg( 'nocache', time(), $url );

		$tmp = download_url( esc_url_raw( $url ) );
		if ( is_wp_error( $tmp ) ) {
			return $this->fail( $tmp->get_error_message() );
		}

		$result = $this->install_zip( $tmp );
		wp_delete_file( $tmp );
		return $result;
	}

	/**
	 * Delete an installed module (path-confined recursive removal).
	 *
	 * @param string $id Module id.
	 * @return array{success:bool,status:string,message?:string}
	 */
	public function delete( string $id ): array {
		$id = sanitize_key( $id );

		// Read-only modules bundled in the host plugin cannot be deleted.
		$module = $this->find( $id );
		if ( $module && ! $module->is_deletable() ) {
			return $this->fail( __( 'This module is bundled with the plugin and cannot be deleted.', 'bp-extension-manager' ) );
		}

		if ( ! $this->init_filesystem() ) {
			return $this->fail( __( 'Could not access the filesystem.', 'bp-extension-manager' ) );
		}

		global $wp_filesystem;

		$dir = $this->base_dir() . $id;

		// Confine: the resolved target must live strictly inside base_dir().
		$base_real = realpath( $this->base_dir() );
		$dir_real  = realpath( $dir );
		if ( ! $base_real || ! $dir_real || ! $wp_filesystem->is_dir( $dir_real ) ) {
			return $this->fail( __( 'Module not found.', 'bp-extension-manager' ) );
		}
		$base_real = wp_normalize_path( trailingslashit( $base_real ) );
		$dir_real  = wp_normalize_path( $dir_real );
		if ( 0 !== strpos( $dir_real . '/', $base_real ) ) {
			return $this->fail( __( 'Refusing to delete a path outside the modules directory.', 'bp-extension-manager' ) );
		}

		if ( ! $wp_filesystem->delete( $dir, true ) ) {
			return $this->fail( __( 'Could not remove the module files.', 'bp-extension-manager' ) );
		}

		$this->memo = null;

		return array(
			'success' => true,
			'status'  => 'deleted',
		);
	}

	/* ---------------------------------------------------------------------- */

	/**
	 * Parse a module folder into a Module (or null when it carries no header).
	 *
	 * @param string $id        Sanitized module id.
	 * @param string $dir       Absolute module directory.
	 * @param bool   $deletable Whether the module lives in the writable dir.
	 */
	private function load_from_dir( string $id, string $dir, bool $deletable = true ): ?Module {
		$main = $this->find_main_file( $dir );
		if ( '' === $main ) {
			return null;
		}
		$data = $this->read_headers( $main );
		if ( '' === trim( (string) ( $data['name'] ?? '' ) ) ) {
			return null; // Not a module (no "Module Name" / "Plugin Name" header).
		}
		return new Module( $id, $main, $data, $deletable );
	}

	/**
	 * Find the module's main PHP file: the first root .php whose header carries a
	 * "Module Name" (or, as a fallback, a standard "Plugin Name") label.
	 *
	 * @param string $dir Absolute module directory.
	 * @return string Absolute path, or '' when none.
	 */
	private function find_main_file( string $dir ): string {
		$files = glob( trailingslashit( $dir ) . '*.php' );
		if ( ! $files ) {
			return '';
		}
		foreach ( $files as $file ) {
			$data = $this->read_headers( $file );
			if ( '' !== trim( (string) ( $data['name'] ?? '' ) ) ) {
				return $file;
			}
		}
		return '';
	}

	/**
	 * Read the module header block from a file.
	 *
	 * @param string $file Absolute PHP file path.
	 * @return array<string,string>
	 */
	private function read_headers( string $file ): array {
		if ( ! function_exists( 'get_file_data' ) ) {
			require_once ABSPATH . 'wp-includes/functions.php';
		}
		$data = get_file_data( $file, Module::$headers );

		// Let a standard plugin header stand in for a missing module label so an
		// existing plugin can be used as a module unedited.
		$fallback = get_file_data( $file, Module::$fallback_headers );
		foreach ( $fallback as $key => $value ) {
			if ( '' === trim( (string) ( $data[ $key ] ?? '' ) ) && '' !== trim( (string) $value ) ) {
				$data[ $key ] = $value;
			}
		}

		return $data;
	}

	/**
	 * Validate a staging directory holds exactly one usable module and return
	 * its id + the directory that should be moved into place.
	 *
	 * Handles zips that wrap the module in a top-level folder (the common case).
	 *
	 * @param string $staging Absolute staging directory.
	 * @return array{id:string,source:string}|\WP_Error
	 */
	private function validate_staging( string $staging ) {
		// Case A: the module files sit at the staging root.
		if ( '' !== $this->find_main_file( $staging ) ) {
			return $this->finish_validation( $staging, basename( $staging ) );
		}

		// Case B: a single wrapper folder contains the module.
		$dirs = glob( trailingslashit( $staging ) . '*', GLOB_ONLYDIR );
		if ( $dirs && 1 === count( $dirs ) ) {
			$inner = $dirs[0];
			if ( '' !== $this->find_main_file( $inner ) ) {
				return $this->finish_validation( $inner, basename( $inner ) );
			}
		}

		return new \WP_Error(
			'bpem_module_invalid',
			__( 'The zip does not contain a valid module (missing a "Module Name" or "Plugin Name" header).', 'bp-extension-manager' )
		);
	}

	/**
	 * Derive + sanitize the module id from a validated source directory.
	 *
	 * @param string $source   Absolute dir holding the module files.
	 * @param string $raw_name Candidate id (folder name).
	 * @return array{id:string,source:string}|\WP_Error
	 */
	private function finish_validation( string $source, string $raw_name ) {
		$id = sanitize_key( $raw_name );
		if ( '' === $id ) {
			// Fall back to a slug derived from the module name header.
			$main = $this->find_main_file( $source );
			$data = '' !== $main ? $this->read_headers( $main ) : array();
			$id   = sanitize_title( (string) ( $data['name'] ?? '' ) );
		}
		if ( '' === $id ) {
			return new \WP_Error(
				'bpem_module_id',
				__( 'Could not determine a valid module id from the zip.', 'bp-extension-manager' )
			);
		}
		return array(
			'id'     => $id,
			'source' => $source,
		);
	}

	/* --------------------------- fs primitives ---------------------------- */

	/**
	 * Ensure the managed base dir exists and is hardened against direct access.
	 */
	private function ensure_base_dir(): void {
		global $wp_filesystem;
		$base = $this->base_dir();
		if ( ! $wp_filesystem->is_dir( $base ) ) {
			wp_mkdir_p( $base );
		}
		// Belt-and-braces: block directory listing / direct execution of module
		// PHP over HTTP (Apache). nginx ignores .htaccess — the ABSPATH guard in
		// module files is the second line of defense.
		$index = $base . 'index.php';
		if ( ! $wp_filesystem->exists( $index ) ) {
			$wp_filesystem->put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
		$htaccess = $base . '.htaccess';
		$htaccess_content = "<FilesMatch \"\\.(php|php[0-9]|phtml)$\">\n" .
			"    <IfModule mod_authz_core.c>\n" .
			"        Require all denied\n" .
			"    </IfModule>\n" .
			"    <IfModule !mod_authz_core.c>\n" .
			"        Order allow,deny\n" .
			"        Deny from all\n" .
			"    </IfModule>\n" .
			"</FilesMatch>\n";

		if ( ! $wp_filesystem->exists( $htaccess ) || trim( (string) $wp_filesystem->get_contents( $htaccess ) ) === 'Deny from all' ) {
			$wp_filesystem->put_contents( $htaccess, $htaccess_content );
		}
	}

	/**
	 * Initialize WP_Filesystem (direct access to functions like unzip_file).
	 */
	private function init_filesystem(): bool {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		return (bool) \WP_Filesystem();
	}

	/**
	 * Move a directory, preferring an atomic rename and falling back to a
	 * recursive copy (e.g. across filesystems / open_basedir).
	 *
	 * @param string $from Source dir.
	 * @param string $to   Destination dir.
	 */
	private function move_dir( string $from, string $to ): bool {
		global $wp_filesystem;
		if ( $wp_filesystem->move( $from, $to ) ) {
			return true;
		}
		return $this->rcopy( $from, $to );
	}

	/**
	 * Recursively copy a directory tree.
	 *
	 * @param string $from Source dir.
	 * @param string $to   Destination dir.
	 */
	private function rcopy( string $from, string $to ): bool {
		global $wp_filesystem;
		if ( ! $wp_filesystem->is_dir( $from ) ) {
			return false;
		}
		if ( ! $wp_filesystem->is_dir( $to ) && ! $wp_filesystem->mkdir( $to ) ) {
			return false;
		}
		$items = $wp_filesystem->dirlist( $from );
		if ( ! $items ) {
			return false;
		}
		foreach ( $items as $name => $details ) {
			$src = $from . '/' . $name;
			$dst = $to . '/' . $name;
			if ( 'd' === $details['type'] ) {
				if ( ! $this->rcopy( $src, $dst ) ) {
					return false;
				}
			} else {
				if ( ! $wp_filesystem->copy( $src, $dst, true ) ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Whether a download URL's host is on the allowlist (mirrors Installer).
	 *
	 * @param string $url Download URL.
	 */
	private function host_allowed( string $url ): bool {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}

		$allowed = array(
			'downloads.wordpress.org',
			'wordpress.org',
			'bplugins.com',
			'www.bplugins.com',
		);

		foreach ( array( 'catalog_url', 'modules_catalog_url' ) as $key ) {
			$catalog = $this->manager->get_config( $key );
			if ( $catalog ) {
				$catalog_host = wp_parse_url( $catalog, PHP_URL_HOST );
				if ( $catalog_host ) {
					$allowed[] = $catalog_host;
				}
			}
		}

		/**
		 * Filter the module download-host allowlist.
		 *
		 * @param string[] $allowed Allowed hostnames.
		 * @param Manager  $m       Host manager.
		 */
		$allowed = (array) apply_filters( "bpem/{$this->manager->get_slug()}/module_download_hosts", $allowed, $this->manager );

		return in_array( strtolower( $host ), array_map( 'strtolower', $allowed ), true );
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
