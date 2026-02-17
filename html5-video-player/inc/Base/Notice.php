<?php

namespace H5VP\Base;

if (! defined('ABSPATH')) exit; // Exit if accessed directly

class Notice
{
    private static $_instance = null;

    public function __construct()
    {
        add_action('wp_ajax_h5vp_dismiss_aws_notice', [$this, 'dismiss_aws_notice']);
        $this->aws_notice();
        add_action('admin_notices', [$this, 'upgrade_notice']);
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
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
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

    function upgrade_notice()
    {
        $page = get_current_screen();
        $is_videos_page = $page->base == 'edit' && $page->post_type == 'videoplayer';
        $is_settings_page = $page->base == 'videoplayer_page_html5vp_settings';
        $is_quick_player_page = $page->base == 'videoplayer_page_html5vp_quick_player';

        if (!h5vp_fs()->can_use_premium_code() && ($is_settings_page || $is_videos_page || $is_quick_player_page)) {
?>
            <style>

            </style>
            <div class="h5vp_upgrade_notice <?php echo esc_attr($is_videos_page ? 'pdfposters' : 'settings') ?> ">
                <div class="flex">
                    <svg height="30" viewBox="0 0 34 34" width="30" xmlns="http://www.w3.org/2000/svg">
                        <path id="XMLID_1341_" d="m27.5 1h-21c-3.04 0-5.5 2.47-5.5 5.5v21c0 3.03 2.46 5.5 5.5 5.5h21c3.03 0 5.5-2.47 5.5-5.5v-21c0-3.03-2.47-5.5-5.5-5.5zm-14.5 3h1.5c.82 0 1.5.67 1.5 1.5s-.68 1.5-1.5 1.5h-1.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5zm8.58 12.19c.26.19.42.49.42.81 0 .33-.16.63-.42.82l-7 5c-.18.12-.38.18-.58.18-.16 0-.32-.03-.46-.11-.33-.17-.54-.51-.54-.89v-10c0-.37.21-.71.54-.88s.73-.15 1.04.07zm-13.58 13.81h-1.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5h1.5c.82 0 1.5.67 1.5 1.5s-.68 1.5-1.5 1.5zm0-23h-1.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5h1.5c.82 0 1.5.67 1.5 1.5s-.68 1.5-1.5 1.5zm6.5 23h-1.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5h1.5c.82 0 1.5.67 1.5 1.5s-.68 1.5-1.5 1.5zm6.5 0h-1.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5h1.5c.82 0 1.5.67 1.5 1.5s-.68 1.5-1.5 1.5zm0-23h-1.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5h1.5c.82 0 1.5.67 1.5 1.5s-.68 1.5-1.5 1.5zm6.5 23h-1.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5h1.5c.82 0 1.5.67 1.5 1.5s-.68 1.5-1.5 1.5zm0-23h-1.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5h1.5c.82 0 1.5.67 1.5 1.5s-.68 1.5-1.5 1.5z" />
                    </svg>
                    <h3>HTML5 Video Player</h3>
                </div>
                <p>No-Code Video Player Plugin – Trusted by 30,000+ Websites Worldwide.</p>
                <div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=html5-video-player#/pricing')) ?>" class="button button-primary" target="_blank">Upgrade To Pro <svg enable-background="new 0 0 515.283 515.283" height="16" viewBox="0 0 515.283 515.283" width="16" xmlns="http://www.w3.org/2000/svg">
                            <g>
                                <g>
                                    <g>
                                        <g>
                                            <path d="m372.149 515.283h-286.268c-22.941 0-44.507-8.934-60.727-25.155s-25.153-37.788-25.153-60.726v-286.268c0-22.94 8.934-44.506 25.154-60.726s37.786-25.154 60.727-25.154h114.507c15.811 0 28.627 12.816 28.627 28.627s-12.816 28.627-28.627 28.627h-114.508c-7.647 0-14.835 2.978-20.241 8.384s-8.385 12.595-8.385 20.242v286.268c0 7.647 2.978 14.835 8.385 20.243 5.406 5.405 12.594 8.384 20.241 8.384h286.267c7.647 0 14.835-2.978 20.242-8.386 5.406-5.406 8.384-12.595 8.384-20.242v-114.506c0-15.811 12.817-28.626 28.628-28.626s28.628 12.816 28.628 28.626v114.507c0 22.94-8.934 44.505-25.155 60.727-16.221 16.22-37.788 25.154-60.726 25.154zm-171.76-171.762c-7.327 0-14.653-2.794-20.242-8.384-11.179-11.179-11.179-29.306 0-40.485l237.397-237.398h-102.648c-15.811 0-28.626-12.816-28.626-28.627s12.815-28.627 28.626-28.627h171.761c3.959 0 7.73.804 11.16 2.257 3.201 1.354 6.207 3.316 8.837 5.887.001.001.001.001.002.002.019.019.038.037.056.056.005.005.012.011.017.016.014.014.03.029.044.044.01.01.019.019.029.029.011.011.023.023.032.032.02.02.042.041.062.062.02.02.042.042.062.062.011.01.023.023.031.032.011.01.019.019.029.029.016.015.03.029.044.045.005.004.012.011.016.016.019.019.038.038.056.057 0 .001.001.001.002.002 2.57 2.632 4.533 5.638 5.886 8.838 1.453 3.43 2.258 7.2 2.258 11.16v171.761c0 15.811-12.817 28.627-28.628 28.627s-28.626-12.816-28.626-28.627v-102.648l-237.4 237.399c-5.585 5.59-12.911 8.383-20.237 8.383z" fill="rgba(255, 255, 255, 1)" />
                                        </g>
                                    </g>
                                </g>
                            </g>
                        </svg></a>
                </div>
            </div>
<?php
        }
    }
}
