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
            register_block_type(H5VP_PRO_PLUGIN_PATH . 'build/blocks/popup-trigger');
        }

        function enqueue_script()
        {
            wp_register_script('html5-player-blocks', plugin_dir_url(__FILE__) . 'build/editor.js', array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'jquery', 'bplugins-plyrio'), H5VP_PRO_VER, true);

            wp_register_script('bplugins-plyrio', plugin_dir_url(__FILE__) . 'public/js/plyr-v3.8.3.polyfilled.js', array(), '3.8.3', false);

            wp_register_script('h5vp-hls', H5VP_PRO_PLUGIN_DIR . 'public/js/hls.min.js', array('bplugins-plyrio'), H5VP_PRO_VER, true);
            wp_register_script('h5vp-dash', H5VP_PRO_PLUGIN_DIR . 'public/js/dash.all.min.js', array('bplugins-plyrio'), H5VP_PRO_VER, true);

            wp_register_style('bplugins-plyrio', plugin_dir_url(__FILE__) . 'public/css/h5vp.css', array(), H5VP_PRO_VER, 'all');
            wp_register_style('h5vp-editor', plugin_dir_url(__FILE__) . 'build/editor.css', array(), H5VP_PRO_VER, 'all');

            wp_register_style('html5-player-video-style', plugin_dir_url(__FILE__) . 'build/frontend.css', array('bplugins-plyrio'), H5VP_PRO_VER);

            $get_option = h5vp_get_option();


            $localize_data = [
                'siteUrl' => site_url(),
                'userId' => get_current_user_id(),
                'isPipe' => (bool) h5vp_fs()->can_use_premium_code(),
                'hls' => H5VP_PRO_PLUGIN_DIR . 'public/js/hls.min.js',
                'dash' => H5VP_PRO_PLUGIN_DIR . 'public/js/dash.all.min.js',
                'nonce' => wp_create_nonce('wp_ajax'),
                'plugin_url' => H5VP_PRO_PLUGIN_DIR,
                'brandColor' => $get_option('h5vp_player_primary_color')
            ];

            wp_localize_script('bplugins-plyrio', 'h5vpBlock', $localize_data);

            wp_localize_script('html5-player-video-view-script', 'h5vpBlock', $localize_data);

            $user_id = get_current_user_id();
            $post_id = is_singular() ? get_the_ID() : 0;

            // short-lived token (e.g., 6 hours)
            $issued_at = time();
            $ttl = 6 * 60 * 60;

            $token = $this->h5vp_make_analytics_token([
                'uid' => (int) $user_id,
                'pid' => (int) $post_id,
                'iat' => (int) $issued_at,
                'ttl' => (int) $ttl,
            ]);

            wp_localize_script('bplugins-plyrio', 'H5VP_ANALYTICS', [
                'endpoint' => rest_url('h5vp/v1/analytics'),
                'nonce'    => wp_create_nonce('wp_rest'), // still used for normal fetch
                'token'    => $token,                     // used for beacon
                'postId'   => $post_id ?: null,
                'userId'   => $user_id ?: null,
                'iat'      => $issued_at,
                'ttl'      => $ttl,
            ]);
            
        }

        public function watermark_data_ajax()
        {

            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce']?? '')), 'wp_ajax')) {
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

        function h5vp_make_analytics_token(array $data) {
            // token payload + signature
            $payload = wp_json_encode($data);
            $sig = hash_hmac('sha256', $payload, wp_salt('auth'));
            // base64url encode
            $b64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
            return $b64 . '.' . $sig;
        }
    }

    



    new H5VP_Block();
}
