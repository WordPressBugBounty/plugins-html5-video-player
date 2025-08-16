<?php

namespace H5VP\Base;

class Menu
{

    public function register()
    {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function register_menu()
    {
        add_submenu_page('hide', 'Choose Preferred Editor', 'Choose Preferred Editor', 'manage_options', 'choose-preferred-editor', array($this, 'choose_preferred_editor'));
    }

    public function enqueue_scripts($hook)
    {
        if ($hook === 'admin_page_choose-preferred-editor') {
            wp_enqueue_script('h5vp-choose-preferred-editor', H5VP_PRO_PLUGIN_DIR . 'build/choose-preferred-editor/index.js', array('react', 'react-dom', 'wp-util'), H5VP_PRO_VER, true);
            wp_enqueue_script('h5vp-tailwind', 'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4', array(), H5VP_PRO_VER, true);
            wp_enqueue_style('h5vp-choose-preferred-editor', H5VP_PRO_PLUGIN_DIR . 'build/choose-preferred-editor/index.css', array(), H5VP_PRO_VER, 'all');

            wp_localize_script('h5vp-choose-preferred-editor', 'h5vp_choose_preferred_editor', [
                'nonce' => wp_create_nonce('h5vp_security_key'),
            ]);
        }
    }

    public function choose_preferred_editor()
    {
?>
        <div class="" id="h5vp-choose-preferred-editor"></div>
<?php
    }
}
