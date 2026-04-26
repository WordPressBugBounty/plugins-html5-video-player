<?php

namespace H5VP\PostType;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * CaptureEmail
 *
 * Registers the `h5vp_email` custom post type, which stores one email
 * capture per post. Each post represents a single submission from a
 * video's email gate.
 *
 * Storage layout:
 *   post_title   → email address
 *   post_status  → 'publish'
 *   meta:
 *     _h5vp_name        → submitter's display name (optional)
 *     _h5vp_video_id    → ID of the video player post
 *     _h5vp_post_id     → ID of the page the player lives on
 *     _h5vp_ip_address  → visitor IP
 *     _h5vp_consent     → 1 | 0
 */
class CaptureEmail
{
    const POST_TYPE = 'h5vp_submited_email';

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    public function register(): void
    {
        add_action('init', [$this, 'register_post_type'], 50);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'column_content'], 10, 2);
    }

    public function register_post_type(): void
    {
        register_post_type(
            self::POST_TYPE,
            [
                'labels' => [
                    'name' => __('Emails', 'h5vp'),
                    'singular_name' => __('Email', 'h5vp'),
                    'all_items' => __('Emails', 'h5vp'),
                    'search_items' => __('Search Emails', 'h5vp'),
                    'not_found' => __('Email not found.', 'h5vp'),
                ],
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => 'edit.php?post_type=videoplayer', // nest under Video Players
                'show_in_rest' => false,
                'hierarchical' => false,
                'has_archive' => false,
                'rewrite' => false,
                'capability_type' => 'post',
                'capabilities' => [
                    // Make the CPT read-only in the admin: no "Add New" button.
                    'create_posts' => 'do_not_allow',
                ],
                'menu_position' => 100,
                'map_meta_cap' => true,
                'supports' => ['title'], // title = email address
                'menu_icon' => 'dashicons-email-alt',
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Admin list columns
    // -------------------------------------------------------------------------

    public function columns(array $columns): array
    {
        return [
            'cb' => $columns['cb'],
            'title' => __('Email', 'h5vp'),
            'h5vp_name' => __('Name', 'h5vp'),
            'h5vp_video_id' => __('Video ID', 'h5vp'),
            'h5vp_post_id' => __('Page ID', 'h5vp'),
            'h5vp_consent' => __('Consent', 'h5vp'),
            'date' => __('Captured At', 'h5vp'),
        ];
    }

    public function column_content(string $column, int $post_id): void
    {
        switch ($column) {
            case 'h5vp_name':
                echo esc_html(get_post_meta($post_id, '_h5vp_name', true) ?: '—');
                break;
            case 'h5vp_video_id':
                $vid = (int) get_post_meta($post_id, '_h5vp_video_id', true);
                echo $vid ? esc_html($vid) : '—';
                break;
            case 'h5vp_post_id':
                $pid = (int) get_post_meta($post_id, '_h5vp_post_id', true);
                echo $pid ? esc_html($pid) : '—';
                break;
            case 'h5vp_consent':
                echo get_post_meta($post_id, '_h5vp_consent', true) ? '✔' : '✘';
                break;
        }
    }

    // -------------------------------------------------------------------------
    // CRUD — called by the Ajax handler
    // -------------------------------------------------------------------------

    /**
     * Save a new email capture as a CPT post.
     *
     * @param array $params {
     *     @type string $email    Required. The visitor's email address.
     *     @type string $name     Optional. Display name.
     *     @type int    $video_id Required. Video player post ID.
     *     @type int    $post_id  Optional. Page the player lives on.
     *     @type bool   $consent  Optional. GDPR consent flag.
     * }
     * @return array{success: bool, id?: int, message?: string}
     */
    public function save(array $params): array
    {
        $email = sanitize_email($params['email'] ?? '');
        // $name = sanitize_text_field($params['name'] ?? '');
        $video_id = absint($params['video_id'] ?? 0);
        $post_id = absint($params['post_id'] ?? 0);
        // $consent = !empty($params['consent']) ? 1 : 0;

        if (!is_email($email)) {
            return ['success' => false, 'message' => __('Invalid email address.', 'h5vp')];
        }

        if (!$video_id) {
            return ['success' => false, 'message' => __('video_id is required.', 'h5vp')];
        }

        $post_data = [
            'post_title' => $email,
            'post_status' => 'publish',
            'post_type' => self::POST_TYPE,
            'post_author' => get_current_user_id() ?: 0,
        ];

        $new_id = wp_insert_post($post_data, true);

        if (is_wp_error($new_id)) {
            return ['success' => false, 'message' => $new_id->get_error_message()];
        }

        // Store extra fields as post meta
        // update_post_meta($new_id, '_h5vp_name', $name);
        update_post_meta($new_id, '_h5vp_video_id', $video_id);
        update_post_meta($new_id, '_h5vp_post_id', $post_id);
        update_post_meta($new_id, '_h5vp_ip_address', $this->get_ip());
        // update_post_meta($new_id, '_h5vp_consent', $consent);

        return ['success' => true, 'id' => $new_id];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Safely resolve the visitor's IP address.
     */
    private function get_ip(): string
    {
        $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

        foreach ($keys as $key) {
            $ip = sanitize_text_field(wp_unslash($_SERVER[$key] ?? ''));
            if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '';
    }
}
