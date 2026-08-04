<?php
if (!defined('ABSPATH'))
	exit;

if (!class_exists('H5VPAdmin')) {
	class H5VPAdmin
	{
		protected static $_instance = null;

		/** Slug of the hidden guided-setup screen. */
		const SETUP_SLUG = 'h5vp-setup';

		function __construct()
		{
			add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
			add_action('admin_menu', [$this, 'adminMenu'], 20);
			add_filter('admin_body_class', [$this, 'setupBodyClass']);
		}

		public static function getInstance()
		{
			if (null === self::$_instance) {
				self::$_instance = new self;
			}
			return self::$_instance;
		}

		/**
		 * Admin URL of the guided-setup screen.
		 *
		 * @return string
		 */
		public static function setupUrl()
		{
			return admin_url('edit.php?post_type=videoplayer&page=' . self::SETUP_SLUG);
		}

		/**
		 * Whether the current request is the guided-setup screen.
		 *
		 * @return bool
		 */
		private function isSetupScreen()
		{
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check, no state change.
			return isset($_GET['page']) && self::SETUP_SLUG === sanitize_key(wp_unslash($_GET['page']));
		}

		function adminEnqueueScripts($hook)
		{
			if ($this->isSetupScreen()) {
				$this->enqueueSetup();
				return;
			}

			if (strpos($hook, 'html5-video-player') !== false) {
				wp_enqueue_style('h5vp-admin-style', H5VP_PLUGIN_DIR . 'build/dashboard.css', [], H5VP_VER);

				wp_enqueue_script('h5vp-admin-script', H5VP_PLUGIN_DIR . 'build/dashboard.js', ['react', 'react-dom', 'wp-components', 'wp-i18n', 'wp-api', 'wp-util', 'lodash', 'wp-media-utils', 'wp-data', 'wp-core-data', 'wp-api-request'], H5VP_VER, true);
				wp_localize_script('h5vp-admin-script', 'h5vpDashboard', [
					'dir' => H5VP_PLUGIN_DIR,
				]);
			}
		}

		/**
		 * Assets for the guided-setup screen. Separate bundle from the
		 * dashboard so the wizard doesn't pull in demos, pricing, and the
		 * router it never uses.
		 */
		private function enqueueSetup()
		{
			wp_enqueue_style('h5vp-onboarding-style', H5VP_PLUGIN_DIR . 'build/onboarding.css', [], H5VP_VER);

			wp_enqueue_script('h5vp-onboarding-script', H5VP_PLUGIN_DIR . 'build/onboarding.js', ['react', 'react-dom', 'wp-i18n', 'wp-util'], H5VP_VER, true);
			wp_localize_script('h5vp-onboarding-script', 'h5vpDashboard', [
				'dir' => H5VP_PLUGIN_DIR,
			]);
			wp_set_script_translations('h5vp-onboarding-script', 'html5-video-player', H5VP_PLUGIN_PATH . 'languages');
		}

		function adminMenu()
		{
			add_submenu_page(
				'edit.php?post_type=videoplayer',
				__('Demo & Help', 'html5-video-player'),
				'<span style="color: #f18500;">' . __('Demo & Help', 'html5-video-player') . '</span>',
				'manage_options',
				'html5-video-player',
				[$this, 'dashboardPage'],
				20
			);

			// Registered so the screen is reachable by URL, then hidden from the
			// menu. add_submenu_page( null, ... ) would do the same but is
			// deprecated as of PHP 8.1.
			$setup_hook = add_submenu_page(
				'edit.php?post_type=videoplayer',
				__('Guided Setup', 'html5-video-player'),
				__('Guided Setup', 'html5-video-player'),
				'manage_options',
				self::SETUP_SLUG,
				[$this, 'setupPage']
			);
			remove_submenu_page('edit.php?post_type=videoplayer', self::SETUP_SLUG);

			// get_admin_page_title() derives $title by scanning the $submenu
			// global, and we just removed our entry from it. Without this the
			// global stays null and admin-header.php trips PHP 8.1's
			// "strip_tags(): passing null" deprecation. Fires immediately
			// before admin-header.php is included.
			if ($setup_hook) {
				add_action("load-{$setup_hook}", function () {
					$GLOBALS['title'] = __('Guided Setup', 'html5-video-player');
				});
			}
		}



		/**
		 * Hide the WordPress admin chrome on the wizard screen.
		 *
		 * @param string $classes
		 * @return string
		 */
		function setupBodyClass($classes)
		{
			if ($this->isSetupScreen()) {
				$classes .= ' bpl-onboarding-fullscreen';
			}

			return $classes;
		}

		function dashboardPage()
		{ ?>
			<div id='h5vpAdminDashboard' data-info="<?php echo esc_attr(wp_json_encode([
				'version' => H5VP_VER,
				'adminUrl' => admin_url(),
				'setupUrl' => self::setupUrl(),
				// Opens Getting Started on whichever workflow the user picked
				// during guided setup.
				'editor' => (string) \H5VP\Model\Settings::get('h5vp_onboarding_editor', ''),
				'nonce' => wp_create_nonce('h5vp_dashboard')
			])); ?>">
			</div>
		<?php }

		function setupPage()
		{
			$settings = \H5VP\Model\Settings::get();
			?>
			<div id='h5vpOnboarding' data-info="<?php echo esc_attr(wp_json_encode([
				'version' => H5VP_VER,
				'adminUrl' => admin_url(),
				'dashboardUrl' => admin_url('edit.php?post_type=videoplayer&page=html5-video-player'),
				'ajaxAction' => \H5VP\Model\Onboarding::AJAX_ACTION,
				// Must be created for the same action the handler verifies.
				'nonce' => wp_create_nonce(\H5VP\Model\Onboarding::AJAX_ACTION),
				// Every save posts the full value set, so a toggle that isn't
				// seeded here would start off and silently switch the real
				// setting off on the next step.
				'values' => [
					'editor' => isset($settings['h5vp_onboarding_editor']) ? (string) $settings['h5vp_onboarding_editor'] : '',
					'primary_color' => isset($settings['h5vp_player_primary_color']) ? (string) $settings['h5vp_player_primary_color'] : '#00b2ff',
					'pause_others' => !empty($settings['h5vp_pause_other_player']),
					// Defaults to on in Field\Settings, so absent means enabled.
					'gutenberg_enable' => !isset($settings['h5vp_gutenberg_enable']) || !empty($settings['h5vp_gutenberg_enable']),
				],
			])); ?>">
			</div>
		<?php }

	}
	H5VPAdmin::getInstance();
}
