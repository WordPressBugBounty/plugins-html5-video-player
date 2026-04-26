<?php

namespace H5VP\Features;

use H5VP\PostType\CaptureEmail;
use H5VP\Model\Settings;

if (!defined('ABSPATH'))
    exit;

class EmailCapture
{
    /**
     * Supported integration types.
     */
    private const INTEGRATION_WEBHOOKS = 'webhooks';
    private const INTEGRATION_MAILCHIMP = 'mailchimp';
    private const INTEGRATION_MAILERLITE = 'mailerlite';
    private const INTEGRATION_ACTIVE_CAMPAIGN = 'activecampaign';
    private const INTEGRATION_FLUENTCRM = 'fluentcrm';


    /**
     * Register AJAX hooks for email capture.
     */
    public function register(): void
    {
        // Email capture — visitors (non-logged-in users) only.
        add_action('wp_ajax_nopriv_h5vp_email_capture', [$this, 'handle_email_capture']);

        // Integration list — administrators only.
        add_action('wp_ajax_h5vp_get_email_capture_integrations', [$this, 'handle_get_integrations']);
    }

    // ─── Public AJAX Handlers ────────────────────────────────────────

    /**
     * AJAX: Capture an email submitted by a visitor.
     */
    public function handle_email_capture(): void
    {
        check_ajax_referer('wp_ajax', 'nonce');

        $params = $this->sanitize_capture_params();

        if (empty($params['email']) || !is_email($params['email'])) {
            wp_send_json_error('A valid email address is required.');
        }

        try {
            $settings = $this->get_capture_settings($params['preset_id']);

            if (empty($settings)) {
                $settings = $this->get_capture_settings_by_video_id_for_classic($params['classic_video_id']);
            }

            try {
                // add email to EmailCapture post type
                (new CaptureEmail())->save($params);
            } catch (\Throwable $th) {
                wp_send_json_error($th->getMessage());
            }

            $integration = $settings['integration'] ?? 'none';

            if (empty($integration) || $integration === 'none') {
                wp_send_json_success('Email captured successfully.');
            }

            $this->dispatch_integration($integration, $settings, $params['email']);

            wp_send_json_success('Email captured successfully.');
        } catch (\Throwable $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX: Return available integrations for the admin panel.
     */
    public function handle_get_integrations(): void
    {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'wp_ajax')) {
            wp_send_json_error('Invalid request.');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized.');
        }

        if (!function_exists('h5vp_get_option')) {
            wp_send_json_error('Plugin options are unavailable.');
        }

        $get_option = h5vp_get_option('h5vp_option');

        wp_send_json_success([
            'webhooks' => $get_option('h5vp_webhooks', []),
            'mailchimp_audiences' => \H5VP\Intregration\Mailchimp::get_audiences(),
            'mailerlite_groups' => \H5VP\Intregration\MailerLite::get_groups(),
            'active_campaign_lists' => \H5VP\Intregration\ActiveCampaign::get_lists(),
            'fluent_crm' => \H5VP\Intregration\FluentCRM::get_integrations_data(),

        ]);
    }

    // ─── Private Helpers ─────────────────────────────────────────────

    /**
     * Sanitize and return the incoming POST parameters.
     *
     * @return array{email: string, video_id: int, post_id: int, preset_id: string}
     */
    private function sanitize_capture_params(): array
    {
        return [
            'email' => sanitize_email(wp_unslash($_POST['email'] ?? '')),
            'video_id' => absint($_POST['video_id'] ?? 0),
            'classic_video_id' => absint($_POST['classic_video_id'] ?? 0),
            'post_id' => absint($_POST['post_id'] ?? 0),
            'preset_id' => sanitize_text_field(wp_unslash($_POST['preset_id'] ?? '')),
        ];
    }

    /**
     * Retrieve email-capture settings for a given preset.
     *
     * @param string $preset_id
     * @return array The email capture settings, or an empty array.
     */
    private function get_capture_settings(string $preset_id): array
    {
        $preset_model = new \H5VP\Model\Preset();
        return $preset_model->get_email_capture_settings($preset_id);
    }

    /**
     * Route the captured email to the correct integration handler.
     *
     * @param string $integration Integration type identifier.
     * @param array  $settings    Email-capture settings from the preset.
     * @param string $email       The captured email address.
     */
    private function dispatch_integration(string $integration, array $settings, string $email): void
    {
        // wp_send_json_success($integration);
        switch ($integration) {
            case self::INTEGRATION_WEBHOOKS:
                $this->send_to_webhook($settings, $email);
                break;

            case self::INTEGRATION_MAILCHIMP:
                $this->send_to_mailchimp($settings, $email);
                break;

            case self::INTEGRATION_MAILERLITE:
                $this->send_to_mailerlite($settings, $email);
                break;

            case self::INTEGRATION_ACTIVE_CAMPAIGN:
                $this->send_to_active_campaign($settings, $email);
                break;

            case self::INTEGRATION_FLUENTCRM:
                $this->send_to_fluentcrm($settings, $email);
                break;
        }
    }

    /**
     * Forward the email to a configured webhook endpoint.
     *
     * @param array  $settings Email-capture settings.
     * @param string $email    The captured email address.
     */
    private function send_to_webhook(array $settings, string $email): void
    {
        $webhook_url = $settings['webhook']['url'] ?? '';

        if (empty($webhook_url)) {
            return;
        }

        $preset_model = new \H5VP\Model\Preset();
        $webhook = $preset_model->get_webhook_by_url($webhook_url);

        if (empty($webhook) || empty($webhook['url'])) {
            return;
        }

        $email_name = $webhook['email_name'] ?? 'email';
        $method = $webhook['method'] ?? 'POST';
        $headers = $this->build_webhook_headers($webhook);

        wp_remote_request($webhook['url'], [
            'method' => $method,
            'body' => wp_json_encode([$email_name => $email]),
            'headers' => $headers,
        ]);
    }

    /**
     * Build an associative header array from the webhook configuration.
     *
     * @param array $webhook The webhook configuration.
     * @return array<string, string>
     */
    private function build_webhook_headers(array $webhook): array
    {
        $headers = [];

        if (!empty($webhook['headers']) && is_array($webhook['headers'])) {
            foreach ($webhook['headers'] as $header) {
                if (!empty($header['key'])) {
                    $headers[$header['key']] = $header['value'] ?? '';
                }
            }
        }

        return $headers;
    }

    /**
     * Add the email as a subscriber on Mailchimp.
     *
     * @param array  $settings Email-capture settings.
     * @param string $email    The captured email address.
     */
    private function send_to_mailchimp(array $settings, string $email): void
    {
        $audience_id = $settings['mailchimp_audience_id'] ?? '';

        if (empty($audience_id)) {
            wp_send_json_error('Mailchimp audience is not configured.');
        }

        $mailchimp = new \H5VP\Intregration\Mailchimp();
        $result = $mailchimp->add_subscriber($audience_id, $email);

        wp_send_json_success($result);
    }

    private function send_to_mailerlite(array $settings, string $email): void
    {
        $group_id = $settings['mailerlite_group_id'] ?? '';

        if (empty($group_id)) {
            wp_send_json_error('MailerLite group is not configured.');
        }

        $mailerlite = new \H5VP\Intregration\MailerLite();
        $result = $mailerlite->add_subscriber($email, [$group_id]);

        wp_send_json_success($result);
    }

    private function send_to_active_campaign(array $settings, string $email): void
    {
        $list_id = $settings['activecampaign_list_id'] ?? '';

        if (empty($list_id)) {
            wp_send_json_error('ActiveCampaign list is not configured.');
        }

        $active_campaign = new \H5VP\Intregration\ActiveCampaign();
        $result = $active_campaign->add_subscriber($email, $list_id);
        wp_send_json_success($result);

    }

    private function send_to_fluentcrm(array $settings, string $email): void
    {
        $list_ids = $settings['fluentCRM']['list_ids'] ?? [];
        $tag_ids = $settings['fluentCRM']['tag_ids'] ?? [];

        if (empty($list_ids) && empty($tag_ids)) {
            wp_send_json_error('FluentCRM list or tags are not configured.');
        }

        $fluentcrm = new \H5VP\Intregration\FluentCRM();
        $result = $fluentcrm->add_contact($email, $list_ids, $tag_ids);
        wp_send_json_success($result['data'] ?? 'Something went wrong.');
    }

    private function get_capture_settings_by_video_id_for_classic($id): array
    {

        // find webhook by url
        $webhook_id = $this->get_post_meta($id, 'h5vp_email_capture_webhook', '');
        $webhook = $this->get_webhook_by_id($webhook_id);

        return [
            "enabled" => $this->get_post_meta($id, 'h5vp_email_capture_enabled', false, true),
            "integration" => $this->get_post_meta($id, 'h5vp_email_capture_integration', ''),
            "webhook" => $webhook,
            "mailchimp_audience_id" => $this->get_post_meta($id, 'h5vp_email_capture_mailchimp_audience_id', ''),
            // remove activecampaign_ prefix from the value
            "activecampaign_list_id" => str_replace('activecampaign_', '', $this->get_post_meta($id, 'h5vp_email_capture_activecampaign_list_id', '')),
            // remove mailerlite_ prefix from the value
            "mailerlite_group_id" => str_replace('mailerlite_', '', $this->get_post_meta($id, 'h5vp_email_capture_mailerlite_group_id', '')),
            "fluentCRM" => [
                "list_ids" => $this->get_post_meta($id, 'h5vp_email_capture_fluentcrm_list_ids', []),
                "tag_ids" => $this->get_post_meta($id, 'h5vp_email_capture_fluentcrm_tag_ids', [])
            ]
        ];
    }

    function get_post_meta($id, $key, $default = null, $is_boolean = false)
    {
        $meta = get_post_meta($id, $key, true);

        if ($is_boolean) {
            $meta = $meta == '1' ? true : false;
        }
        if ($meta == '') {
            $meta = $default;
        }
        return $meta;
    }

    public function get_webhook_by_id($id)
    {
        $webhooks = Settings::get('h5vp_webhooks', []);
        foreach ($webhooks as $webhook) {
            if ($webhook['id'] == $id) {
                return $webhook;
            }
        }
        return null;
    }
}
