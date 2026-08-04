<?php

namespace H5VP\Base;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

use H5VP\Model\Onboarding;

/**
 * Dismissible notice pointing existing installs at the guided setup.
 *
 * The activation redirect only reaches new installs. Sites that already had the
 * plugin when the wizard shipped would otherwise never learn it exists, which
 * is exactly the audience the wizard was built for.
 *
 * Dismissal is stored per user, matching AdminNotice's behaviour.
 */
class OnboardingNotice
{
    /** User meta key that records the dismissal for the current user. */
    const DISMISS_META_KEY = 'h5vp_dismissed_onboarding_notice';

    /** AJAX action used to persist the dismissal. */
    const DISMISS_ACTION = 'h5vp_dismiss_onboarding_notice';

    public function register()
    {
        add_action('admin_notices', [$this, 'render']);
        add_action('wp_ajax_' . self::DISMISS_ACTION, [$this, 'dismiss']);
    }

    /**
     * Show the notice to admins who have not run — or dismissed — the wizard.
     */
    public function render()
    {
        if (!current_user_can(Onboarding::CAPABILITY)) {
            return;
        }

        if (Onboarding::is_completed()) {
            return;
        }

        if (get_user_meta(get_current_user_id(), self::DISMISS_META_KEY, true)) {
            return;
        }

        // The wizard is its own full-screen page; showing the notice on top of
        // it would be noise.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check, no state change.
        if (isset($_GET['page']) && 'h5vp-setup' === sanitize_key(wp_unslash($_GET['page']))) {
            return;
        }

        printf(
            '<div class="notice notice-info is-dismissible" data-h5vp-notice="%1$s" data-h5vp-nonce="%2$s"><p>%3$s</p><p><a class="button button-primary" href="%4$s">%5$s</a></p></div>',
            esc_attr(self::DISMISS_ACTION),
            esc_attr(wp_create_nonce(self::DISMISS_ACTION)),
            esc_html__('New to HTML5 Video Player? Take the 1-minute guided setup — it walks you through adding your first video and setting your player defaults.', 'html5-video-player'),
            esc_url(\H5VPAdmin::setupUrl()),
            esc_html__('Start Guided Setup', 'html5-video-player')
        );

        $this->print_dismiss_script();
    }

    /**
     * Persist the dismissal when the user clicks the notice's close button.
     */
    public function dismiss()
    {
        check_ajax_referer(self::DISMISS_ACTION, 'nonce');

        if (!current_user_can(Onboarding::CAPABILITY)) {
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
                var notice = document.querySelector('[data-h5vp-notice="<?php echo esc_js(self::DISMISS_ACTION); ?>"]');
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
