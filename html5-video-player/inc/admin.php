<?php
if (! defined('ABSPATH')) exit;

if (!class_exists('H5APAdmin')) {
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
			if (str_contains($hook, 'html5-video-player')) {
				wp_enqueue_style('h5ap-admin-style', H5VP_PRO_PLUGIN_DIR . 'build/dashboard.css', [], H5VP_PRO_VER);

				wp_enqueue_script('h5ap-admin-script', H5VP_PRO_PLUGIN_DIR . 'build/dashboard.js', ['react', 'react-dom',  'wp-components', 'wp-i18n', 'wp-api', 'wp-util', 'lodash', 'wp-media-utils', 'wp-data', 'wp-core-data', 'wp-api-request'], H5VP_PRO_VER, true);
				wp_localize_script('h5ap-admin-script', 'h5apDashboard', [
					'dir' => H5VP_PRO_PLUGIN_DIR,
				]);
			}
		}

		function adminMenu()
		{
			add_submenu_page(
				'edit.php?post_type=videoplayer',
				__('Demo & Help', 'h5ap'),
				__('Demo & Help', 'h5ap'),
				'manage_options',
				'html5-video-player',
				[$this, 'dashboardPage'],
				20
			);
		}

		function dashboardPage()
		{ ?>
			<div id='h5vpAdminDashboard' data-info=<?php echo esc_attr(wp_json_encode([
														'version' => H5VP_PRO_VER,
														'isPremium' => h5vp_fs()->can_use_premium_code(),
														'hasPro' => true
													])); ?>></div>
		<?php }

		function upgradePage()
		{ ?>
			<div id='h5vpAdminUpgrade'>Coming soon...</div>
<?php }
	}
	H5VPAdmin::getInstance();
}
