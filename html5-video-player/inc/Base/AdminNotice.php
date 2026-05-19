<?php

namespace H5VP\Base;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Dismissible admin notice announcing that the legacy [video] shortcode has
 * been removed and replaced by [html5_video].
 *
 * The dismissal is stored per user, so once an editor closes the notice it
 * stays closed for them on every screen.
 */
class AdminNotice
{
    /** User meta key that records the dismissal for the current user. */
    const DISMISS_META_KEY = 'h5vp_dismissed_video_shortcode_notice';

    /** AJAX action used to persist the dismissal. */
    const DISMISS_ACTION = 'h5vp_dismiss_video_shortcode_notice';

    public function register()
    {
        add_action('admin_notices', [$this, 'render']);
        add_action('wp_ajax_' . self::DISMISS_ACTION, [$this, 'dismiss']);
    }

    /**
     * Output the notice on admin screens for users who can author content.
     */
    public function render()
    {
        if (!current_user_can('edit_posts')) {
            return;
        }

        if (get_user_meta(get_current_user_id(), self::DISMISS_META_KEY, true)) {
            return;
        }

        $message = sprintf(
            /* translators: 1: removed shortcode, 2: replacement shortcode. */
            __('HTML5 Video Player: the %1$s shortcode has been removed. Please use %2$s instead.', 'html5-video-player'),
            '<code>[video]</code>',
            '<code>[html5_video]</code>'
        );

        printf(
            '<div class="notice notice-warning is-dismissible" data-h5vp-notice="%1$s" data-h5vp-nonce="%2$s"><p>%3$s</p></div>',
            esc_attr(self::DISMISS_ACTION),
            esc_attr(wp_create_nonce(self::DISMISS_ACTION)),
            wp_kses($message, ['code' => []])
        );

        $this->print_dismiss_script();
    }

    /**
     * Persist the dismissal when the user clicks the notice's close button.
     */
    public function dismiss()
    {
        check_ajax_referer(self::DISMISS_ACTION, 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(null, 403);
        }

        update_user_meta(get_current_user_id(), self::DISMISS_META_KEY, 1);
        wp_send_json_success();
    }

    /**
     * Inline script that catches the core "X" dismiss click and reports it
     * back so the notice does not return on the next page load.
     */
    private function print_dismiss_script()
    {
        ?>
        <script>
            (function () {
                var notice = document.querySelector('[data-h5vp-notice]');
                if (!notice) {
                    return;
                }
                notice.addEventListener('click', function (event) {
                    if (!event.target.closest('.notice-dismiss')) {
                        return;
                    }
                    var data = new FormData();
                    data.append('action', notice.getAttribute('data-h5vp-notice'));
                    data.append('nonce', notice.getAttribute('data-h5vp-nonce'));
                    fetch(window.ajaxurl, { method: 'POST', body: data, credentials: 'same-origin', keepalive: true });
                });
            })();
        </script>
        <?php
    }
}
