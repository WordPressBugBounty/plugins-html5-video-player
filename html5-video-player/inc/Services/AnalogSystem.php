<?php

namespace H5VP\Services;

class AnalogSystem
{
    static function parsePlaylistData($id)
    {
        $videos = self::get_videos($id, 'h5vp_playlist', []);
        $meta = get_post_meta($id, 'h5vp_playlist', true);
        $options_meta = get_post_meta($id, 'h5vp_playlist_options', true);
        //GPM = get playlist metadat
        $controls = [
            'play-large' => self::GPM($id, 'h5vp_hide_large_play_btn', 'show'),
            'restart' => self::GPM($id, 'h5vp_hide_restart_btn', 'mobile'),
            'rewind' => self::GPM($id, 'h5vp_hide_rewind_btn', 'mobile'),
            'play' => self::GPM($id, 'h5vp_hide_play_btn', 'show'),
            'fast-forward' => self::GPM($id, 'h5vp_hide_fast_forward_btn', 'mobile'),
            'progress' => self::GPM($id, 'h5vp_hide_video_progressbar', 'show'),
            'current-time' => self::GPM($id, 'h5vp_hide_current_time', 'show'),
            'duration' => self::GPM($id, 'h5vp_hide_video_duration', 'mobile'),
            'mute' => self::GPM($id, 'h5vp_hide_mute_btn', 'show'),
            'volume' => self::GPM($id, 'h5vp_hide_volume_control', 'show'),
            'captions' => 'show',
            'settings' => self::GPM($id, 'h5vp_hide_Setting_btn', 'show'),
            'pip' => self::GPM($id, 'h5vp_hide_pip_btn', 'mobile'),
            'airplay' => self::GPM($id, 'h5vp_hide_airplay_btn', 'mobile'),
            'download' => self::GPM($id, 'h5vp_hide_downlaod_btn', 'mobile'),
            'fullscreen' => self::GPM($id, 'h5vp_hide_fullscreen_btn', 'show'),
        ];

        $controls = array_filter($controls, function ($control) {
            return $control != 'hide';
        });

        $controls = array_keys($controls);

        $options = [
            'controls' => $meta['h5vp_controls'] ?? $controls,
            'muted' => (bool)self::GPM($id, 'h5vp_muted_playerio', '0', true),
            'seekTime' => (int)self::GPM($id, 'h5vp_seek_time_playerio', '10'),
            'hideControls' => (bool)self::GPM($id, 'h5vp_auto_hide_control_playerio', '1', true),
            'resetOnEnd' => true,
            'autoplayNextVideo' => $options_meta['h5vp_play_nextvideo'] === 'yes' ?? false
        ];

        return [
            'uniqueId' => 'h5vp_playlist_' . uniqid(),
            'options' => $options,
            'videos' => $videos,
            'playlistType' => $options_meta['h5vp_playlist_view_type'],
            'styles' => [
                // color should use by define css variable
                'h5vp_playlist_container' => [
                    // 'background' =>self::GPML($id, 'listbg', '#fff'),
                    'width' => $options_meta['h5vp_player_width_playerio'] ? $options_meta['h5vp_player_width_playerio'] . 'px' : '100%',
                    'max-width' => '100%',
                    // 'color' => self::GPML($id, 'text_color', '#333')
                ],
                // 'h5vp_playlist_container .video-item:hover, .h5vp_playlist_container .video-item.item-active' => [
                //     'background' =>self::GPML($id, 'listhoverbg', '#333'),
                //     'color' => self::GPML($id, 'text_hover_color', '#fff'),
                // ],
                // ".h5vp_playlist_container video-item .video-block__title, .h5vp_playlist .simplelist li a " => [
                //     'color' => self::GPML($id, 'text_color', '#333')
                // ],
                // ".h5vp_playlist_container .item-active .video-block__title, .h5vp_playlist_container .video-item:hover .video-block__title, .h5vp_playlist .simplelist .item-active li a, .h5vp_playlist .simplelist .video-item:hover li a" => [
                //     'color' => self::GPML($id, 'text_hover_color', '#333')
                // ]
                // 'video-block__title' => [
                //     'color' =>self::GPML($id, 'text_color', '#333')
                // ],
                // 'video-block__content' => [
                //     'color' =>self::GPML($id, 'text_color', '#333')
                // ]
            ]
        ];
    }

    public static function GPM($id, $key, $default = false, $true = false)
    {
        $meta = metadata_exists('post', $id, 'h5vp_playlist_options') ? get_post_meta($id, 'h5vp_playlist_options', true) : '';
        if (isset($meta[$key]) && $meta != '') {
            if ($true == true) {
                if ($meta[$key] == '1') {
                    return true;
                } else if ($meta[$key] == '0') {
                    return false;
                }
            } else {
                return $meta[$key];
            }
        }

        return $default;
    }

    // get playlist meta latest
    public static function GPML($id, $key, $default = false, $true = false)
    {
        $meta = metadata_exists('post', $id, 'h5vp_playlist') ? get_post_meta($id, 'h5vp_playlist', true) : '';
        if (isset($meta[$key]) && $meta[$key] != '') {
            if ($true == true) {
                if ($meta[$key] == '1') {
                    return true;
                } else if ($meta[$key] == '0') {
                    return false;
                }
            } else {
                return $meta[$key];
            }
        }

        return $default;
    }

    private static function get_videos($id, $key, $default = null, $true = false)
    {
        $meta = metadata_exists('post', $id, 'h5vp_playlist') ? get_post_meta($id, 'h5vp_playlist', true) : '';
        if (isset($meta[$key]) && $meta[$key] != '' && $true == true) {
            return true;
        } elseif (isset($meta[$key]) && $meta[$key] != '') {
            return $meta[$key];
        } else {
            return $default;
        }
    }
}
