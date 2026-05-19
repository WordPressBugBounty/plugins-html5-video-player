<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly


if (!function_exists('h5vp_get_option')) {
    function h5vp_get_option($key = 'h5vp_option')
    {
        $option = get_option($key);

        return function ($key, $default = null, $is_boolean = false) use ($option) {
            if (isset($option[$key])) {
                if ($is_boolean) {
                    return $option[$key] === '1';
                }
                return $option[$key];
            }
            return $default;
        };
    }
}

if (!function_exists(('h5vp_process_block_attributes'))) {
    function h5vp_process_block_attributes($attributes)
    {
        $option = h5vp_get_option('h5vp_option');

        $attributes['features']['passwordProtected']['password'] = null;

        // Block attributes can originate from shortcodes / imports. Force a safe
        // URL scheme on source + poster so a javascript: URI can never reach
        // view.js and become a stored-XSS vector.
        if (!empty($attributes['source'])) {
            $attributes['source'] = esc_url_raw((string) $attributes['source']);
        }
        if (!empty($attributes['poster'])) {
            $attributes['poster'] = esc_url_raw((string) $attributes['poster']);
        }

        if (isset($attributes['styles'])) {
            $attributes['styles']['.plyr'] = [
                '--plyr-color-main' => $option('h5vp_player_primary_color', '#00b2ff')
            ];
        }

        $attributes['skin'] = 'default';
        unset($attributes['quality']);
        unset($attributes['qualities']);

        if (class_exists('\H5VP\Model\Video')) {
            $videoInstance = new \H5VP\Model\Video();
            $attributes['video_id'] = $videoInstance->get_id(['src' => $attributes['source']]);
        }

        return $attributes;
    }
}


if (!function_exists('h5vp_convert_duration_to_iso8601')) {
    function h5vp_convert_duration_to_iso8601($duration)
    {
        // If input is already numeric, treat as seconds
        if (is_numeric($duration)) {
            $hours = floor($duration / 3600);
            $minutes = floor(($duration % 3600) / 60);
            $seconds = $duration % 60;
        } else {
            // Parse HH:MM:SS, MM:SS, or SS format
            $parts = array_reverse(explode(':', $duration));
            $seconds = isset($parts[0]) ? intval($parts[0]) : 0;
            $minutes = isset($parts[1]) ? intval($parts[1]) : 0;
            $hours = isset($parts[2]) ? intval($parts[2]) : 0;
        }

        // Build ISO 8601 string
        $iso = 'PT';
        if ($hours > 0)
            $iso .= $hours . 'H';
        if ($minutes > 0)
            $iso .= $minutes . 'M';
        if ($seconds > 0 || $iso === 'PT')
            $iso .= $seconds . 'S'; // Always include seconds

        return $iso;
    }
}

// h5vp_get_post_meta
if (!function_exists('h5vp__get_post_meta')) {
    function h5vp__get_post_meta($post_id, $key, $single = true)
    {
        $meta = get_post_meta($post_id, $key, $single);
        return function ($key, $default = null, $is_boolean = false) use ($meta) {
            if (isset($meta[$key])) {
                if ($is_boolean) {
                    return $meta[$key] === '1';
                }
                return $meta[$key];
            }
            return $default;
        };
    }
}


if (!function_exists('h5vp_getPostMeta')) {
    function h5vp_getPostMeta($id, $key)
    {
        $meta = get_post_meta($id, $key, true);
        return function ($key, $default = null, $is_boolean = false, $key2 = null) use ($meta) {
            if ($key === 'all') {
                return $meta;
            }
            if ($key2) {
                $value = isset($meta[$key][$key2]) ? $meta[$key][$key2] : $default;
            } else {
                $value = isset($meta[$key]) ? $meta[$key] : $default;
            }
            if ($is_boolean) {
                return $value == '1';
            }
            return $value;
        };
    }
}
