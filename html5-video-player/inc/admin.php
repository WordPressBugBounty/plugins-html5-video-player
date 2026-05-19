<?php
if (!defined('ABSPATH'))
	exit;

if (!class_exists('H5VPAdmin')) {
	class H5VPAdmin
	{
		protected static $_instance = null;
		function __construct()
		{
			add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
			add_action('admin_menu', [$this, 'adminMenu'], 20);
		}

		public static function getInstance()
		{
			if (null === self::$_instance) {
				self::$_instance = new self;
			}
			return self::$_instance;
		}

		function adminEnqueueScripts($hook)
		{
			if (strpos($hook, 'html5-video-player') !== false) {
				wp_enqueue_style('h5vp-admin-style', H5VP_PLUGIN_DIR . 'build/dashboard.css', [], H5VP_VER);

				wp_enqueue_script('h5vp-admin-script', H5VP_PLUGIN_DIR . 'build/dashboard.js', ['react', 'react-dom', 'wp-components', 'wp-i18n', 'wp-api', 'wp-util', 'lodash', 'wp-media-utils', 'wp-data', 'wp-core-data', 'wp-api-request'], H5VP_VER, true);
				wp_localize_script('h5vp-admin-script', 'h5vpDashboard', [
					'dir' => H5VP_PLUGIN_DIR,
				]);
			}
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
		}

		function dashboardPage()
		{ ?>
			<div id='h5vpAdminDashboard' data-info="<?php echo esc_attr(wp_json_encode([
				'version' => H5VP_VER,
				'nonce' => wp_create_nonce('h5vp_dashboard')
			])); ?>">
			</div>
		<?php }

	}
	H5VPAdmin::getInstance();
}
