<?php

namespace H5VP\Block;

if (!defined('ABSPATH')) {
    return;
}

if (!class_exists('H5VP_Block')) {
    class H5VP_Block
    {
        function __construct()
        {
            add_action('init', [$this, 'register_block']);
            add_action('enqueue_block_assets', [$this, 'enqueue_script']);
        }

        function register_block()
        {
            register_block_type(H5VP_PLUGIN_PATH . 'build/blocks/parent');
            register_block_type(H5VP_PLUGIN_PATH . 'build/blocks/video');
            register_block_type(H5VP_PLUGIN_PATH . 'build/blocks/youtube');
            register_block_type(H5VP_PLUGIN_PATH . 'build/blocks/vimeo');
        }

        function enqueue_script()
        {
            wp_register_script('html5-player-blocks', plugin_dir_url(__FILE__) . 'build/editor.js', ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'jquery', 'bplugins-plyrio'], H5VP_VER, true);

            wp_register_script('bplugins-plyrio', plugin_dir_url(__FILE__) . 'public/js/plyr-v3.8.4.polyfilled.js', [], '3.8.3', false);
            wp_register_script('h5vp-view', plugin_dir_url(__FILE__) . 'build/blocks/view.js', ['react', 'react-dom', 'wp-util', 'bplugins-plyrio'], H5VP_VER, false);
            wp_register_script('h5vp-blocks', plugin_dir_url(__FILE__) . 'build/blocks/blocks.js', [], H5VP_VER, false);

            wp_register_style('bplugins-plyrio', plugin_dir_url(__FILE__) . 'public/css/h5vp.css', [], H5VP_VER, 'all');
            wp_register_style('h5vp-view', plugin_dir_url(__FILE__) . 'build/blocks/view.css', ['bplugins-plyrio'], H5VP_VER, 'all');
            wp_register_style('h5vp-blocks', plugin_dir_url(__FILE__) . 'build/blocks/blocks.css', [], H5VP_VER, 'all');
            wp_register_style('h5vp-editor', plugin_dir_url(__FILE__) . 'build/editor.css', [], H5VP_VER, 'all');

            wp_register_style('html5-player-video-style', plugin_dir_url(__FILE__) . 'build/frontend.css', ['bplugins-plyrio'], H5VP_VER);

            $get_option = h5vp_get_option();

            $localize_data = [
                'siteUrl' => site_url(),
                'userId' => get_current_user_id(),
                'pauseOther' => (bool) \H5VP\Helper\Functions::getOptionDeep("h5vp_option", "h5vp_pause_other_player", false),
                'plugin_url' => H5VP_PLUGIN_DIR,
                'brandColor' => $get_option('h5vp_player_primary_color', '#00b2ff'),
                'postId' => is_singular() ? (int) get_the_ID() : 0,
            ];

            if (current_user_can('edit_posts')) {
                $localize_data['adminUrl'] = admin_url();
                $localize_data['editorNonce'] = wp_create_nonce('h5vp_ajax_handler');
            }

            wp_localize_script('h5vp-blocks', 'h5vpBlock', $localize_data);
            wp_localize_script('h5vp-view', 'h5vpBlock', $localize_data);

        }

    }

    new H5VP_Block();
}
