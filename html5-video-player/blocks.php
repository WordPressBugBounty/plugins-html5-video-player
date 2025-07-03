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
            add_action('wp_ajax_watermark_data', [$this, 'watermark_data_ajax']);
            add_action('wp_ajax_nopriv_watermark_data', [$this, 'watermark_data_ajax']);
        }

        function register_block()
        {
            register_block_type(H5VP_PRO_PLUGIN_PATH . 'build/blocks/parent');
            register_block_type(H5VP_PRO_PLUGIN_PATH . 'build/blocks/video');
            register_block_type(H5VP_PRO_PLUGIN_PATH . 'build/blocks/youtube');
            register_block_type(H5VP_PRO_PLUGIN_PATH . 'build/blocks/vimeo');
        }

        function enqueue_script()
        {
            wp_register_script('html5-player-blocks', plugin_dir_url(__FILE__) . 'build/editor.js', array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'jquery', 'bplugins-plyrio'), H5VP_PRO_VER, true);

            wp_register_script('bplugins-plyrio', plugin_dir_url(__FILE__) . 'public/js/plyr-v3.7.8.js', array(), H5VP_PRO_VER, false);

            wp_register_script('h5vp-hls', H5VP_PRO_PLUGIN_DIR . 'public/js/hls.min.js', array('bplugins-plyrio'), H5VP_PRO_VER, true);
            wp_register_script('h5vp-dash', H5VP_PRO_PLUGIN_DIR . 'public/js/dash.min.js', array('bplugins-plyrio'), H5VP_PRO_VER, true);

            wp_register_style('bplugins-plyrio', plugin_dir_url(__FILE__) . 'public/css/h5vp.css', array(), H5VP_PRO_VER, 'all');
            wp_register_style('h5vp-editor', plugin_dir_url(__FILE__) . 'build/editor.css', array(), H5VP_PRO_VER, 'all');

            wp_register_style('html5-player-video-style', plugin_dir_url(__FILE__) . 'build/frontend.css', array('bplugins-plyrio'), H5VP_PRO_VER);


            $localize_data = [
                'siteUrl' => site_url(),
                'userId' => get_current_user_id(),
                'isPipe' => (bool) h5vp_fs()->can_use_premium_code(),
                'hls' => H5VP_PRO_PLUGIN_DIR . 'public/js/hls.min.js',
                'dash' => H5VP_PRO_PLUGIN_DIR . 'public/js/dash.min.js',
                'nonce' => wp_create_nonce('wp_ajax')
            ];

            wp_localize_script('bplugins-plyrio', 'h5vpBlock', $localize_data);

            wp_localize_script('html5-player-video-view-script', 'h5vpBlock', $localize_data);
        }

        public function watermark_data_ajax()
        {

            if (!wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'wp_ajax')) {
                wp_send_json_error('invalid request');
            }

            $user = wp_get_current_user();

            wp_send_json_success([
                'user' => [
                    'email' => $user->data->user_email ?? '',
                    'name' => $user->data->display_name ?? '',
                ]
            ]);
        }

        function getWatermarkPosition($position) {}
    }



    new H5VP_Block();
}
