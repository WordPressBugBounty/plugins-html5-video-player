<?php

namespace H5VP\Model;

// use same ajax endpoint as in Gutenberg Block

class Preset
{
    public $id;
    public $name;
    public $settings;

    public function __construct()
    {
        add_action('wp_ajax_h5vp_handle_presets', [$this, 'handle_presets']);
    }

    public function handle_presets()
    {
        $this->verify_request();
        $action = sanitize_text_field(wp_unslash($_POST['task'] ?? ''));
        switch ($action) {
            case 'get_presets':
                $this->get_presets();
                break;
            case 'get_preset':
                $this->get_preset();
                break;
            case 'save_preset':
                $this->save_preset();
                break;
            case 'delete_preset':
                $this->delete_preset();
                break;
            default:
                wp_send_json_error('Invalid action');
                break;
        }
    }

    public function get_presets()
    {
        $this->verify_request();
        // cache - wp_cache_get()
        $presets = wp_cache_get('h5vp_presets', 'h5vp_presets');
        if ($presets) {
            wp_send_json_success($presets);
        }

        global $wpdb;
        $presets = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}h5vp_presets"
            )
        );
        wp_cache_set('h5vp_presets', $presets, 'h5vp_presets', 120);
        wp_send_json_success($presets);
    }


    // get preset by id, cache data
    public function get_preset()
    {
        $this->verify_request();
        $id = sanitize_text_field(wp_unslash($_POST['id'] ?? ''));
        $preset = $this->get_preset_by_id($id);
        wp_send_json_success($preset);
    }

    public function get_preset_by_id($id)
    {
        // Check built-in default presets first (string IDs like "default", "minimal", etc.)
        $default = $this->get_default_preset_by_id($id);
        if ($default) {
            return $default;
        }

        $preset = wp_cache_get('h5vp_preset_' . $id, 'h5vp_presets');
        if ($preset) {
            return $preset;
        }
        global $wpdb;
        $preset = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}h5vp_presets WHERE id = %d",
                $id
            )
        );

        wp_cache_set('h5vp_preset_' . $id, $preset, 'h5vp_presets', 120);

        return $preset;
    }

    public function save_preset()
    {
        $this->verify_request();

        $id = sanitize_text_field(wp_unslash($_POST['id'] ?? ''));
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $settings = sanitize_text_field(wp_unslash($_POST['settings'] ?? ''));

        if (!$name) {
            wp_send_json_error('Preset Name is missing');
        }

        if ($id) {
            $this->update_preset($id, $name, $settings);
        } else {
            $id = $this->create_preset($name, $settings);
        }

        global $wpdb;
        if ($wpdb->last_error) {
            wp_send_json_error($wpdb->last_error);
        }

        wp_send_json_success('Preset saved successfully');
    }

    public function create_preset($name, $settings)
    {
        global $wpdb;
        try {
            //code...
            $wpdb->insert(
                $wpdb->prefix . 'h5vp_presets',
                [
                    'name' => $name,
                    'settings' => $settings,
                    'created_by' => get_current_user_id(),
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ],
                [
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                ]
            );
            if ($wpdb->insert_id) {
                return $wpdb->insert_id;
            } else {
                wp_send_json_error($wpdb->last_error);
            }
        } catch (\Throwable $th) {
            wp_send_json_error($th->getMessage());
        }
    }

    public function update_preset($id, $name, $settings)
    {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'h5vp_presets',
            [
                'name' => $name,
                'settings' => $settings,
                'updated_at' => current_time('mysql'),
            ],
            [
                'id' => $id
            ],
            [
                '%s',
                '%s',
                '%s',
            ]
        );
    }

    public function delete_preset()
    {
        $this->verify_request();

        $id = sanitize_text_field(wp_unslash($_POST['id'] ?? ''));

        if (empty($id)) {
            wp_send_json_error('Invalid Request. status: pinf');
        }

        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'h5vp_presets',
            [
                'id' => $id,
                'created_by' => get_current_user_id(),
            ],
            [
                '%d',
                '%d',
            ]
        );

        wp_send_json_success('Preset deleted successfully');
    }

    public function verify_request()
    {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_security_code'] ?? '')), 'wp_ajax')) {
            wp_send_json_error('invalid request');
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Invalid Authorization');
        }
    }

    /**
     * Return a single default preset by its string ID, or null.
     *
     * @param  string $id  e.g. "default", "minimal", "simple", "course"
     * @return object|null
     */
    public function get_default_preset_by_id($id)
    {
        $defaults = $this->get_default_presets();
        return $defaults[$id] ?? null;
    }

    /**
     * Return the built-in default presets keyed by their string ID.
     * The data mirrors the frontend DEFAULT_PRESETS in constants.tsx.
     *
     * @return array<string, object>
     */
    public function get_default_presets()
    {
        static $defaults = null;

        if ($defaults !== null) {
            return $defaults;
        }

        $raw = [
            [
                'id' => 'default',
                'name' => 'Default',
                'settings' => [
                    'options' => [
                        'controls' => ["play-large", "play", "progress", "current-time", "duration", "mute", "volume", "settings", "fullscreen"],
                        'playsinline' => false,
                        'hideControls' => true,
                        'seekTime' => 10,
                        'preload' => '',
                        'speed' => ['options' => [0.5, 1, 1.5, 2]],
                        'ratio' => null,
                    ],
                    'preload' => 'metadata',
                    'features' => ['saveState' => true],
                    'skin' => 'default',
                ],
            ],
            [
                'id' => 'minimal',
                'name' => 'Minimal',
                'settings' => [
                    'options' => [
                        'controls' => ['play-large'],
                        'playsinline' => true,
                        'hideControls' => true,
                        'seekTime' => 10,
                        'preload' => '',
                        'speed' => ['options' => [0.5, 1, 1.5, 2]],
                        'ratio' => null,
                    ],
                    'preload' => 'metadata',
                    'features' => ['saveState' => false],
                    'skin' => 'default',
                ],
            ],
            [
                'id' => 'simple',
                'name' => 'Simple',
                'settings' => [
                    'options' => [
                        'controls' => ["play-large", "play", "progress", "current-time", "mute", "fullscreen"],
                        'playsinline' => true,
                        'hideControls' => true,
                        'seekTime' => 10,
                        'preload' => '',
                        'speed' => ['options' => [0.5, 1, 1.5, 2]],
                        'ratio' => null,
                    ],
                    'preload' => 'metadata',
                    'features' => ['saveState' => false],
                    'skin' => 'default',
                ],
            ],
            [
                'id' => 'course',
                'name' => 'Course',
                'settings' => [
                    'options' => [
                        'controls' => ["play-large", "rewind", "play", "fast-forward", "progress", "current-time", "duration", "mute", "volume", "captions", "settings", "pip", "airplay", "fullscreen"],
                        'playsinline' => false,
                        'hideControls' => true,
                        'seekTime' => 10,
                        'preload' => '',
                        'speed' => ['options' => [0.5, 0.75, 1, 1.25, 1.5, 2]],
                        'ratio' => '21:9',
                    ],
                    'preload' => 'metadata',
                    'features' => ['saveState' => true],
                    'skin' => 'default',
                    'styles' => [
                        'plyr_wrapper' => [
                            'width' => '100%',
                            'borderRadius' => '12px',
                            'overflow' => 'hidden',
                        ],
                    ],
                ],
            ],
        ];

        $defaults = [];
        foreach ($raw as $item) {
            $obj = new \stdClass();
            $obj->id = $item['id'];
            $obj->name = $item['name'];
            $obj->settings = wp_json_encode($item['settings']);
            $defaults[$item['id']] = $obj;
        }

        return $defaults;
    }

    public function get_webhook_by_preset_id($id)
    {
        // get preset by id
        $preset = $this->get_preset_by_id($id);
        if (empty($preset)) {
            return [];
        }

        $settings = json_decode($preset->settings, true);
        $url = $settings['features']['emailCapture']['webhook']['url'] ?? '';
        $get_option = h5vp_get_option('h5vp_option');
        $webhooks = $get_option('h5vp_webhooks', []);
        // find webhook by url
        foreach ($webhooks as $webhook) {
            if ($webhook['url'] === $url) {
                return $webhook;
            }
        }
        return [];
    }
    public function get_webhook_by_url($url)
    {
        $get_option = h5vp_get_option('h5vp_option');
        $webhooks = $get_option('h5vp_webhooks', []);
        // find webhook by url
        foreach ($webhooks as $webhook) {
            if ($webhook['url'] === $url) {
                return $webhook;
            }
        }
        return [];
    }

    public function get_email_capture_settings($id)
    {
        $preset = $this->get_preset_by_id($id);
        if (empty($preset)) {
            return [];
        }

        $settings = json_decode($preset->settings, true);
        return $settings['features']['emailCapture'] ?? [];
    }

}