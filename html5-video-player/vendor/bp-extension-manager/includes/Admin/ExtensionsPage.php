<?php
/**
 * Registers the host's "Extensions" submenu and loads the React admin app.
 *
 * @package BPEM
 */

namespace BPEM\Admin;

use BPEM\Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One page per host, nested under the host's top-level menu.
 */
final class ExtensionsPage {

	/**
	 * Host manager.
	 *
	 * @var Manager
	 */
	private $manager;

	/**
	 * Hook suffix returned by add_submenu_page() — used to gate enqueue.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Constructor.
	 *
	 * @param Manager $manager Host manager.
	 */
	public function __construct( Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Hook into the admin menu + asset enqueue.
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_head', array( $this, 'print_menu_badge_style' ) );
	}

	/**
	 * Add the submenu under the host's menu_parent.
	 */
	public function add_menu(): void {
		$menu_title = $this->page_label();

		$badge = $this->menu_badge_label();
		if ( '' !== $badge ) {
			$menu_title .= ' <span class="bpem-menu-badge">' . esc_html( $badge ) . '</span>';
		}

		$this->hook_suffix = (string) add_submenu_page(
			(string) $this->manager->get_config( 'menu_parent' ),
			/* translators: 1: host name, 2: page label (Extensions/Modules). */
			sprintf( __( '%1$s %2$s', 'bp-extension-manager' ), $this->manager->get_config( 'name' ), $this->page_label() ),
			$menu_title,
			(string) $this->manager->get_config( 'capability', 'manage_options' ),
			(string) $this->manager->get_config( 'page_slug' ),
			array( $this, 'render' )
		);
	}

	/**
	 * Menu/page label: "Modules" when only modules are enabled, else "Extensions".
	 */
	private function page_label(): string {
		if ( $this->manager->modules_enabled() && ! $this->manager->extensions_enabled() ) {
			return __( 'Modules', 'bp-extension-manager' );
		}
		return __( 'Extensions', 'bp-extension-manager' );
	}

	/**
	 * Render the mount node.
	 */
	public function render(): void {
		// Visiting the page clears the "New" badge on subsequent loads.
		if ( $this->manager->get_config( 'menu_badge' ) && ! get_option( $this->seen_option_name(), false ) ) {
			update_option( $this->seen_option_name(), true, false );
		}

		printf(
			'<div class="wrap"><div id="bpem-%s-extensions" class="bpem-app"></div></div>',
			esc_attr( $this->manager->get_slug() )
		);
	}

	/**
	 * Print the scoped badge style in <head> (the submenu renders on every admin
	 * page, so the style must be global — but only when a badge is actually shown).
	 */
	public function print_menu_badge_style(): void {
		if ( '' === $this->menu_badge_label() ) {
			return;
		}
		?>
<style id="bpem-menu-badge-<?php echo esc_attr( $this->manager->get_slug() ); ?>">
#adminmenu .bpem-menu-badge{
	display:inline-block;margin:-1px 0 -1px 7px;padding:2px 7px 1px;
	font-size:9px;font-weight:700;line-height:1.6;letter-spacing:.7px;text-transform:uppercase;
	color:#fff;vertical-align:middle;
	background:linear-gradient(135deg,#146ef5 0%,#0c53c4 100%);
	box-shadow:0 0 0 1px rgba(255,255,255,.12),0 2px 6px -1px rgba(12,83,196,.7);
	text-shadow:none;
}
#adminmenu li.current .bpem-menu-badge,
#adminmenu a:hover .bpem-menu-badge{background:linear-gradient(135deg,#2f82ff 0%,#0d5ad1 100%);}
</style>
		<?php
	}

	/**
	 * Resolve the submenu badge text, or '' when none should show.
	 *
	 * Shown when the host sets `menu_badge`, until the admin first visits the page.
	 */
	private function menu_badge_label(): string {
		$badge = $this->manager->get_config( 'menu_badge' );
		if ( empty( $badge ) ) {
			return '';
		}
		// Auto-hide after the first visit unless the host asked it to persist.
		if ( ! $this->manager->get_config( 'menu_badge_persist' ) && get_option( $this->seen_option_name(), false ) ) {
			return '';
		}
		return is_string( $badge ) ? $badge : __( 'New', 'bp-extension-manager' );
	}

	/**
	 * Per-host option flag: whether the Extensions page has been visited.
	 */
	private function seen_option_name(): string {
		return "bpem_{$this->manager->get_slug()}_menu_badge_seen";
	}

	/**
	 * Enqueue + localize the React bundle, only on this host's page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue( string $hook ): void {
		if ( $hook !== $this->hook_suffix ) {
			return;
		}

		$slug      = $this->manager->get_slug();
		$build_dir = BPEM_PATH . '/build';
		$build_url = rtrim( BPEM_URL, '/' ) . '/build';
		$asset     = is_readable( "{$build_dir}/index.asset.php" )
			? require "{$build_dir}/index.asset.php"
			: array(
				'dependencies' => array( 'wp-element', 'wp-api-fetch', 'wp-components', 'wp-i18n' ),
				'version'      => defined( 'BPEM_VERSION' ) ? BPEM_VERSION : '1.0.0',
			);

		$handle = "bpem-admin-{$slug}";

		$dependencies = $asset['dependencies'];

		// In-context Freemius Checkout: load the widget so a paid extension can be
		// bought without leaving the site, and make the admin bundle depend on it so
		// `FS.Checkout` is defined before our app runs. Loaded from Freemius' CDN
		// (the public checkout host) only on this host's page.
		$checkout_enabled = $this->manager->freemius_checkout_enabled();
		if ( $checkout_enabled ) {
			$checkout_handle = 'bpem-freemius-checkout';
			if ( ! wp_script_is( $checkout_handle, 'registered' ) ) {
				wp_register_script(
					$checkout_handle,
					'https://checkout.freemius.com/checkout.min.js',
					array(),
					null, // Freemius versions the URL itself; a query string would break its CDN cache.
					true
				);
			}
			wp_enqueue_script( $checkout_handle );
			$dependencies[] = $checkout_handle;
		}

		wp_enqueue_script(
			$handle,
			"{$build_url}/index.js",
			$dependencies,
			$asset['version'],
			true
		);

		// wp-scripts emits the imported stylesheet as style-index.css.
		//
		// Version by the CSS file's own mtime, NOT $asset['version']: that hash
		// tracks only the JS bundle, so pure SCSS changes would otherwise reuse
		// the old ?ver= and the browser would keep serving stale CSS.
		$style_path = "{$build_dir}/style-index.css";
		if ( is_readable( $style_path ) ) {
			$style_ver = filemtime( $style_path ) ?: $asset['version'];
			wp_enqueue_style( $handle, "{$build_url}/style-index.css", array( 'wp-components' ), $style_ver );
		}

		wp_set_script_translations( $handle, 'bp-extension-manager' );

		// Filesystem mutations (upload/install/delete) need install_plugins and are
		// blocked when file mods are disabled site-wide.
		$can_manage = $this->manager->modules_enabled()
			&& ! ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS )
			&& current_user_can( 'install_plugins' );

		// Prefill the Freemius Checkout with the current admin's details.
		$current_user = wp_get_current_user();

		wp_localize_script(
			$handle,
			'BPEM_DATA',
			array(
				'slug'      => $slug,
				'name'      => $this->manager->get_config( 'name' ),
				'mountId'   => "bpem-{$slug}-extensions",
				'restRoot'  => esc_url_raw( rest_url( "bpem/{$slug}/v1" ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'ajax'      => admin_url( 'admin-ajax.php' ),
				'ajaxNonce' => wp_create_nonce( "bpem_{$slug}_admin" ),
				'licenseAction' => "bpem_{$slug}_license",
				'isMaxPlan' => $this->manager->is_max_plan(),
				'canInstall' => current_user_can( 'install_plugins' ),
				// In-context Freemius Checkout (widget only loaded when true).
				'checkoutEnabled' => $checkout_enabled,
				'buyer'           => array(
					'email' => $current_user ? (string) $current_user->user_email : '',
					'first' => $current_user ? (string) $current_user->user_firstname : '',
					'last'  => $current_user ? (string) $current_user->user_lastname : '',
				),
				'hasExtensions'     => $this->manager->extensions_enabled()
					&& (
						(bool) $this->manager->get_extensions()
						|| (bool) $this->manager->get_config( 'catalog_url' )
						|| (bool) $this->manager->get_config( 'catalog_file' )
					),
				'hasModules'        => $this->manager->modules_enabled()
					&& (
						(bool) $this->manager->modules()->repository()->all()
						|| (bool) $this->manager->get_config( 'catalog_url' )
						|| (bool) $this->manager->get_config( 'modules_catalog_url' )
						|| (bool) $this->manager->get_config( 'catalog_file' )
					),
				'canManageModules'  => $can_manage,
				// Upload is manageable AND not turned off for this host.
				'canUploadModule'   => $can_manage && $this->manager->module_upload_enabled(),
			)
		);
	}
}
