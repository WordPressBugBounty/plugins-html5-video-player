<?php
require_once ABSPATH . "wp-admin/includes/plugin-install.php";

// function h5vp_free_plugin_loaded(){
//     $screen = get_current_screen();
//     print_r($screen);
//     wp_enqueue_script('plugin-install');
//     wp_enqueue_script('updates');
// }
// add_action('init', 'h5vp_free_plugin_loaded');
//$table->display();
if (!class_exists('H5VP_BPlugins_Free_plugins')) {
    class H5VP_BPlugins_Free_plugins
    {

        public function __construct()
        {
            add_action('admin_menu', array($this, 'bplugins_free_plugins_menu'));
        }
        public function bplugins_free_plugins_menu()
        {
            // add_submenu_page(
            //     'edit.php?post_type=videoplayer',
            //     'Our Free Plugins',
            //     'Our Free Plugins',
            //     'manage_options',
            //     '/plugin-install.php?s=abuhayat&tab=search&type=author'
            // );
        }

    }
}
new H5VP_BPlugins_Free_plugins();