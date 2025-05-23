<?php

namespace H5VP\Helper;

class DefaultArgs
{

    public static function parsePlaylistArgs($data = [])
    {
        $default = self::gePlaylistDefaultArgs();
        $data = wp_parse_args($data, $default);
        $data['options'] = wp_parse_args($data['options'], $default['options']);
        $data['infos'] = wp_parse_args($data['infos'], $default['infos']);
        $data['template'] = wp_parse_args($data['template'], $default['template']);
        $data['template']['videos'] = wp_parse_args($data['template']['videos'], $default['template']['videos']);

        return $data;
    }

    public static function gePlaylistDefaultArgs()
    {

        $controls = [
            'play-large' => 'show',
            'restart' => 'mobile',
            'rewind' => 'mobile',
            'play' => 'show',
            'fast-forward' => 'mobile',
            'progress' => 'show',
            'current-time' => 'show',
            'duration' => 'mobile',
            'mute' => 'show',
            'volume' => 'show',
            'captions' => 'show',
            'settings' => 'show',
            'pip' => 'mobile',
            'airplay' => 'mobile',
            'download' => 'mobile',
            'fullscreen' => 'show',
        ];

        $options = [
            'controls' => $controls,
            'muted' => false,
            'seekTime' => 10,
            'hideControls' => true,
            'resetOnEnd' => true,
        ];

        $infos = [
            'id' => 0,
            'loop' => 'yes',
            'next' => 'yes',
            'viewType' => 'simplelist',
            'carouselItems' => 3,
            'provider' => 'self-hosted',
            'slideVideos' => true,
        ];

        $template = [
            'videos' => [],
            'width' => '100%',
            'skin' => 'simplelist',
            'arrowSize' => '25px',
            'arrowColor' => '#222',
            'textColor' => '#222',
            'provider' => 'self-hosted',
            'brandColor' => self::brandColor(),
            'slideVideos' => true,
            'column' => 3,
            'listBG' => '#fff',
            'listHoverBG' => '#333',
            'modern' => 'imageText',
            'borderWidth' => '7px',
            'borderColor' => '#fff',
            'playsinline' => false
        ];

        return [
            'options' => $options,
            'infos' => $infos,
            'template' => $template,
            'uniqueId' => '',
            'CSS' => '',
        ];
    }

    public static function brandColor()
    {
        $brandColor = get_option('h5vp_option', ['h5vp_player_primary_color' => '#1ABAFF']);
        if (isset($brandColor['h5vp_player_primary_color']) && !empty($brandColor['h5vp_player_primary_color'])) {
            return $brandColor['h5vp_player_primary_color'];
        } else {
            return '#1ABAFF';
        }
    }
}
