<?php

namespace H5VP\Base;

class Notice
{
    private static $_instance = null;

    public function __construct()
    {
        add_action('wp_ajax_h5vp_dismiss_aws_notice', [$this, 'dismiss_aws_notice']);
        $this->aws_notice();
    }

    public static function instance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function aws_notice()
    {
        if (h5vp_fs()->can_use_premium_code()) {
            // show notice if post type is 'videoplayer'
            if (isset($_GET['post_type']) && $_GET['post_type'] === 'videoplayer') {
                add_action('admin_notices', function () {
                    $is_dismissed = get_user_meta(get_current_user_id(), 'h5vp_aws_notice_dismissed', true);
                    if ($is_dismissed !== 'dismissed' && !defined('BPLUGINS_S3_VERSION')) {
                        // required bPlugins aws s3 extension
                        echo wp_kses_post('<div class="h5vp_aws_notice notice notice-error is-dismissible" data-nonce="' . esc_attr(wp_create_nonce('wp_ajax')) . '"><p>' . __('"bPlugins AWS S3 Extension" is Required to work AWS S3 features. Please contact support to get the extension. ', 'h5vp') . ' <a target="_blank" href="https://bplugins.com/support">Support</a></p></div>');
                    }
                });
            }
        }
    }

    public function dismiss_aws_notice()
    {
        $nonce = sanitize_text_field($_POST['nonce']);
        if (!wp_verify_nonce($nonce, 'wp_ajax')) {
            wp_send_json_error('invalid request');
        }
        $user_id = get_current_user_id();
        try {
            $result = update_user_meta($user_id, 'h5vp_aws_notice_dismissed', 'dismissed');
            wp_send_json_success(get_user_meta($result));
        } catch (\Throwable $th) {
            wp_send_json_error($th->getMessage());
        }
    }
}
