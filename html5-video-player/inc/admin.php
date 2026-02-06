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

			add_submenu_page(
				'html5-video-player',
				__('Add New', 'h5vp'),
				__(' &#8627; Add New', 'h5vp'),
				'edit_posts',
				'html5-video-player-add-new',
				[$this, 'redirectToAddNew'],
				2
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

		/**	
		 * Redirect to add new Model Viewer
		 * */
		function redirectToAddNew()
		{
			if (function_exists('headers_sent') && headers_sent()) {
			?>
				<script>
					window.location.href = "<?php echo esc_url(admin_url('post-new.php?post_type=videoplayer')); ?>";
				</script>
			<?php
			} else {
				wp_redirect(admin_url('post-new.php?post_type=videoplayer'));
			}
		}
	}
	new H5VPAdmin;
}
