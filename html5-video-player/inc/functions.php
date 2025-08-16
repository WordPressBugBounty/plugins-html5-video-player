<?php

use H5VP\Base\AWS;

if (!function_exists('h5vp_get_option')) {
    function h5vp_get_option($key)
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

        $aws = new AWS();
        $option = h5vp_get_option('h5vp_option');

        if ($aws->parseS3Url($attributes['source'])) {
            $attributes['source'] = $aws->get_aws_s3_url($attributes['source']);
        }
        $attributes['features']['passwordProtected']['password'] = null;
        if (isset($attributes['features']['passwordProtected']['enabled']) && $attributes['features']['passwordProtected']['enabled']) {
            unset($attributes['quality']);
            unset($attributes['qualities']);
        }

        if (isset($attributes['styles'])) {
            $attributes['styles']['.plyr'] = [
                '--plyr-color-main' => $option('h5vp_player_primary_color', '#00b2ff')
            ];
        }

        return $attributes;
    }
}


if (!function_exists('h5vp_extract_base_url')) {
    function h5vp_extract_base_url($url)
    {
        $parts = parse_url($url);

        $base_url = $parts['scheme'] . '://' . $parts['host'];

        if (!empty($parts['port'])) {
            $base_url .= ':' . $parts['port'];
        }

        if (!empty($parts['path'])) {
            $base_url .= $parts['path'];
        }

        return $base_url;
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
            $hours   = isset($parts[2]) ? intval($parts[2]) : 0;
        }

        // Build ISO 8601 string
        $iso = 'PT';
        if ($hours > 0) $iso .= $hours . 'H';
        if ($minutes > 0) $iso .= $minutes . 'M';
        if ($seconds > 0 || $iso === 'PT') $iso .= $seconds . 'S'; // Always include seconds

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

if (!function_exists('xorEncode')) {
    function xorEncode($str, $key)
    {
        $out = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $out .= chr(ord($str[$i]) ^ ord($key[$i % strlen($key)]));
        }
        return base64_encode($out);
    }
}
