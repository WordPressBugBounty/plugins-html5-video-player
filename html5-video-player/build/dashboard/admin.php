<?php

if (!class_exists('H5APAdmin')) {
	class H5VPAdmin
	{
		function __construct()
		{
			add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
			add_action('admin_menu', [$this, 'adminMenu']);
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

			add_menu_page(
				__('HTML5 Video Player', 'h5vp'),
				__('HTML5 Video Player', 'h5vp'),
				'manage_options',
				'html5-video-player',
				[$this, 'dashboardPage'],
				H5VP_PRO_PLUGIN_DIR . 'admin/img/icn.png',
				15
			);

			add_submenu_page(
				'html5-video-player',
				__('Dashboard', 'h5ap'),
				__('Dashboard', 'h5ap'),
				'manage_options',
				'html5-video-player',
				[$this, 'dashboardPage'],
				0
			);
		}

		function dashboardPage()
		{ ?>
			<div id='h5vpAdminDashboard' data-info=<?php echo esc_attr(wp_json_encode([
														'version' => H5VP_PRO_VER
													])); ?>></div>
		<?php }

		function upgradePage()
		{ ?>
			<div id='h5vpAdminUpgrade'>Coming soon...</div>
<?php }
	}
	new H5VPAdmin;
}
