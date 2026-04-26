<?php

namespace H5VP\Model;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

use H5VP\Helper\Functions as Utils;

class Video
{
    protected static $table_name = '';

    public function verifyUser($capability = 'edit_posts')
    {
        if (!current_user_can($capability)) {
            return wp_send_json_error('403 Forbidden');
        }
    }

    public function test()
    {
        wp_send_json_success('working fine');
    }

    /**
     * Insert a video into the custom DB if it does not already exist.
     * Designed for use in save_post hooks where verifyUser / wp_send_json are inappropriate.
     *
     * @param array $args  Must contain at least 'src'. Optional: 'title', 'type'.
     * @return int|null     The video row ID, or null on failure.
     */
    public function createIfNotExists($args)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'h5vp_videos';

        $args = wp_parse_args($args, [
            'title' => '',
            'src' => '',
            'type' => 'library',
            'post_id' => null,
            'user_id' => get_current_user_id(),
            'created_at' => wp_date("Y-m-d H:i:s", current_time("U")),
        ]);

        $src = esc_url($args['src']);
        if (empty($src)) {
            return null;
        }
        $args['src'] = $src;

        // Extract external_id for YouTube/Vimeo
        $external_id = null;
        if ($args['type'] === 'youtube') {
            if (preg_match("/watch\?v=([\w-]+)/i", $args['src'], $match)) {
                $external_id = $match[1];
            } elseif (preg_match("/youtu\.be\/([\w-]+)/i", $args['src'], $match)) {
                $external_id = $match[1];
            } elseif (preg_match("/youtube\.com\/embed\/([\w-]+)/i", $args['src'], $match)) {
                $external_id = $match[1];
            }
        } elseif ($args['type'] === 'vimeo') {
            if (preg_match("/vimeo\.com\/([\w]+)/i", $args['src'], $match)) {
                $external_id = $match[1];
            }
        }

        if ($external_id) {
            $args['external_id'] = $external_id;
        }

        // Check uniqueness: for external videos try external_id + post_id first,
        // then fall back to external_id only. For library videos, check by src.
        $existing = null;
        if ($external_id && $args['post_id']) {
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_name WHERE external_id=%s AND post_id=%d",
                $external_id,
                $args['post_id']
            ));
            // Fall back to external_id-only (might exist without post_id)
            if (!$existing) {
                $existing = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE external_id=%s AND (post_id IS NULL OR post_id=%d)",
                    $external_id,
                    $args['post_id']
                ));
            }
        } elseif ($external_id) {
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_name WHERE external_id=%s",
                $external_id
            ));
        } else {
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_name WHERE src=%s",
                $args['src']
            ));
        }

        if ($existing) {
            // Update post_id if provided
            if ($args['post_id']) {
                $wpdb->update($table_name, ['post_id' => $args['post_id']], ['id' => $existing->id]);
            }
            return (int) $existing->id;
        }

        // Fetch title from YouTube/Vimeo API if title is empty or just the post title
        if (empty($args['title']) || $args['title'] === get_the_title($args['post_id'] ?? 0)) {
            if ($args['type'] === 'youtube' && $external_id) {
                $args['title'] = $this->fetchYouTubeTitle($external_id, $args['title']);
            } elseif ($args['type'] === 'vimeo' && $external_id) {
                $args['title'] = $this->fetchVimeoTitle($external_id, $args['title']);
            }
        }

        $wpdb->insert($table_name, $args);
        return $wpdb->insert_id ?: null;
    }

    /**
     * Fetch YouTube video title using wp_remote_get.
     */
    private function fetchYouTubeTitle($video_id, $fallback = '')
    {
        $apikey = 'AIzaSyA6pMW1ZJBii9VewZj_cWZPjMTdfmKyVKE';
        $url = 'https://www.googleapis.com/youtube/v3/videos?id=' . $video_id . '&key=' . $apikey . '&part=snippet';
        $response = wp_remote_get($url, ['timeout' => 5]);
        if (is_wp_error($response)) {
            return $fallback;
        }
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return $fallback;
        }
        $info = json_decode($body, true);
        return $info['items'][0]['snippet']['title'] ?? $fallback;
    }

    /**
     * Fetch Vimeo video title using wp_remote_get.
     */
    private function fetchVimeoTitle($video_id, $fallback = '')
    {
        $url = 'https://vimeo.com/api/v2/video/' . $video_id . '.json';
        $response = wp_remote_get($url, ['timeout' => 5]);
        if (is_wp_error($response)) {
            return $fallback;
        }
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return $fallback;
        }
        $info = json_decode($body, true);
        return $info[0]['title'] ?? $fallback;
    }

    public function create($args)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'h5vp_videos';
        $args = wp_parse_args($args, [
            'title' => '',
            'src' => '',
            'type' => 'library',
            'post_id' => null,
            'user_id' => get_current_user_id(),
            'created_at' => wp_date("Y-m-d H:i:s", current_time("U")),
        ]);

        if (strlen($args['src']) < 13) {
            if ($args['type'] == 'youtube') {
                $args['src'] = 'https://www.youtube.com/watch?v=' . $args['src'];
            } else if ($args['type'] == 'vimeo') {
                $args['src'] = 'https://vimeo.com/' . $args['src'];
            }
        }

        // Check if a video already exists in the DB.
        $video = null;
        if ($args['post_id']) {
            $post_date = get_the_date('Y-m-d', $args['post_id']);
            if ($post_date >= '2026-04-25') {
                // New videoplayer posts: check uniqueness by post_id only.
                $video = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE post_id=%d", $args['post_id']));
            } else {
                // Old videoplayer posts: check uniqueness by post_id and src.
                $video = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE post_id=%d AND src=%s", $args['post_id'], $args['src']));
            }
        } else {
            // Other post types: check uniqueness by src.
            $video = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE src=%s", $args['src']));
        }

        if ($video) {
            $wpdb->update($table_name, [
                'src' => $args['src'],
                'title' => $args['title'],
                'type' => $args['type'],
            ], ['id' => $video->id]);
            return $video->id;
        }

        if ($args['type'] == 'youtube') {
            $videoid = '';
            if (preg_match("/watch\?v=(\w+)/i", $args['src'], $match)) {
                $videoid = $match[1];
                $apikey = 'AIzaSyA6pMW1ZJBii9VewZj_cWZPjMTdfmKyVKE';
                $json = file_get_contents('https://www.googleapis.com/youtube/v3/videos?id=' . $videoid . '&key=' . $apikey . '&part=snippet');
                $info = json_decode($json, true);
                $args['title'] = $info['items'][0]['snippet']['title'] ?? $args['title'];
                $args['external_id'] = $videoid;
            }
        }

        if ($args['type'] == 'vimeo') {
            $videoid = '';
            // http://vimeo.com/api/v2/video/50961789.json
            if (preg_match("/vimeo.com\/(\w+)/i", $args['src'], $match)) {
                $videoid = $match[1];
                $json = file_get_contents('http://vimeo.com/api/v2/video/' . $videoid . '.json');
                $info = json_decode($json, true);
                $args['title'] = $info[0]['title'] ?? $args['title'];
                $args['external_id'] = $videoid;
            }
        }

        // Remove null post_id so $wpdb->insert doesn't try to insert an invalid value
        if ($args['post_id'] === null) {
            unset($args['post_id']);
        }

        $wpdb->insert($table_name, $args);
        return $wpdb->insert_id;
    }

    public function get_id_response($args)
    {
        $id = $this->get_id($args);
        if ($id) {
            wp_send_json_success($id);
        }
        wp_send_json_error('Video not found');
    }

    public function get_id($args)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'h5vp_videos';
        $args['src'] = esc_url($args['src']);
        $video = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE src=%s", $args['src']));
        if ($video) {
            return $video->id;
        }
        return null;
    }

    public function update($args = [], $where = [])
    {
        $this->verifyUser();
        global $wpdb;
        $table_name = $wpdb->prefix . 'h5vp_videos';
        return $wpdb->update($table_name, $args, $where);
    }

    public function get_top_videos($video_ids)
    {
        global $wpdb;
        $this->verifyUser();

        if (empty($video_ids)) {
            return [];
        }

        // Ensure $video_ids is an array of integers
        if (is_string($video_ids)) {
            $video_ids = array_map('absint', explode(',', $video_ids));
        } else {
            $video_ids = array_map('absint', (array) $video_ids);
        }
        $video_ids = array_filter($video_ids);
        if (empty($video_ids)) {
            return [];
        }


        $table_name = $wpdb->prefix . 'h5vp_videos';
        $placeholders = implode(',', array_fill(0, count($video_ids), '%d'));
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT title, id, src FROM $table_name WHERE id IN ($placeholders)",
                ...$video_ids
            )
        );
    }

    public function get_all_video_id()
    {
        if (!current_user_can('edit_posts')) {
            return wp_send_json_error('403 Forbidden');
        }
        global $wpdb;

        $table_name = $wpdb->prefix . 'h5vp_videos';
        $videos = $wpdb->get_results("SELECT id FROM $table_name");
        return array_map(function ($object) {
            return $object->id;
        }, $videos);
        return $videos;
    }

    function check_password()
    {
        try {
            $key = sanitize_text_field(wp_unslash($_POST['key'] ?? ''));
            $password = sanitize_text_field(wp_unslash($_POST['password'] ?? ''));

            $data = get_option($key);

            if ($password && md5($password) === $data['pass']) {
                wp_send_json_success($data);
            } else {
                wp_send_json_success(false);
            }
        } catch (\Throwable $th) {
            wp_send_json_error($th->getMessage());
        }
    }


    function save_password()
    {
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'wp_ajax')) {
            wp_send_json_error('invalid request');
        }

        if (!current_user_can('manage_options')) {
            return false;
        }

        $key = sanitize_text_field(wp_unslash($_POST['key'] ?? ''));

        if (strpos($key, 'h5vp_') === false) {
            wp_send_json_error('403 Forbiddensdfsdf');
        }

        $data = [
            'key' => $key,
            'quality' => Utils::sanitize_array(wp_unslash($_POST['quality'] ?? [])),
            'pass' => md5(sanitize_text_field(wp_unslash($_POST['password'] ?? ''))),
            'source' => esc_url(wp_unslash($_POST['source'] ?? '')),
        ];

        try {
            //code...
            update_option($key, $data);
            wp_send_json_success('success');
        } catch (\Throwable $th) {
            //throw $th;
        }
        wp_send_json_error([$key => $data]);
    }
}
