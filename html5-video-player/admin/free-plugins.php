<?php

if (!class_exists('GSPlugins_logo_free')) {

    class GSPlugins_logo_free
    {
        protected $posttype = 'videoplayer';

        /**
         * Singleton Instance
         *
         * @access
         */
        private static $_instance;

        public function __construct()
        {
            add_action('admin_menu', array($this, 'gs_plugins_free_menu'));
        }

        /**
         * Get class singleton instance
         *
         * @return Class Instance
         */
        public static function get_instance()
        {
            if (!self::$_instance instanceof GSPlugins_logo_free) {
                self::$_instance = new GSPlugins_logo_free();
            }

            return self::$_instance;
        }

        public function gs_plugins_free_menu()
        {

            add_submenu_page(
                'edit.php?post_type=videoplayer',
                'GS Plugins',
                'GS Plugins Lite',
                'manage_options',
                'gs-plugins-free'
            );
        }
    }
    $gsplugin_free = GSPlugins_logo_free::get_instance();
}