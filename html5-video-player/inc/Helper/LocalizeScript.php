<?php
namespace H5VP\Helper;
// require_once(__DIR__.'/Functions.php');
use H5VP\Helper\Functions;

class LocalizeScript{

    public static function translatedText(){
       return [
            'restart' => Functions::getOptionDeep("h5vp_option", "h5vp_top_restart", 'Restart'),
            'rewind' => Functions::getOptionDeep("h5vp_option", "h5vp_top_rewind", 'Rewind {seektime}s'),
            'play' => Functions::getOptionDeep("h5vp_option", "h5vp_top_play", 'Play'),
            'pause' => Functions::getOptionDeep("h5vp_option", "h5vp_top_pause", 'Pause'),
            'fastForward' => Functions::getOptionDeep("h5vp_option", "h5vp_top_forward_seektime", 'Forward {seektime}s'),
            'seek' => Functions::getOptionDeep("h5vp_option", "h5vp_top_seek", 'Seek'),
            'seekLabel' => Functions::getOptionDeep("h5vp_option", "h5vp_top_current_time_of_duration", '{currentTime} of {duration}'),
            'played' => Functions::getOptionDeep("h5vp_option", "h5vp_top_played", 'Played'),
            'buffered' => Functions::getOptionDeep("h5vp_option", "h5vp_top_buffered", 'Buffered'),
            'currentTime' => Functions::getOptionDeep("h5vp_option", "h5vp_top_current_time", 'Current time'),
            'duration' => Functions::getOptionDeep("h5vp_option", "h5vp_top_duration", 'Duration'),
            'volume' => Functions::getOptionDeep("h5vp_option", "h5vp_top_volume", 'Volume'),
            'mute' => Functions::getOptionDeep("h5vp_option", "h5vp_top_mute", 'Mute'),
            'unmute' => Functions::getOptionDeep("h5vp_option", "h5vp_top_unmute", 'Unmute'),
            'enableCaptions' => Functions::getOptionDeep("h5vp_option", "h5vp_top_enable_captions", 'Enable captions'),
            'disableCaptions' => Functions::getOptionDeep("h5vp_option", "h5vp_top_disable_captions", 'Disable captions'),
            'download' => Functions::getOptionDeep("h5vp_option", "h5vp_top_downlaod", 'Download'),
            'enterFullscreen' => Functions::getOptionDeep("h5vp_option", "h5vp_top_enter_fullscreen", 'Enter fullscreen'),
            'exitFullscreen' => Functions::getOptionDeep("h5vp_option", "h5vp_top_exit_fullscreen", 'Exit fullscreen'),
            'frameTitle' => Functions::getOptionDeep("h5vp_option", "h5vp_top_player_for_title", 'Player for {title}'),
            'captions' => Functions::getOptionDeep("h5vp_option", "h5vp_top_captions", 'Captions'),
            'settings' => Functions::getOptionDeep("h5vp_option", "h5vp_top_settings", 'Settings'),
            'pip' => Functions::getOptionDeep("h5vp_option", "h5vp_top_pip", 'PIP'),
            'menuBack' => Functions::getOptionDeep("h5vp_option", "h5vp_top_go_back_to_previews_menu", 'Go back to previous menu'),
            'speed' => Functions::getOptionDeep("h5vp_option", "h5vp_top_speed", 'Speed'),
            'normal' => Functions::getOptionDeep("h5vp_option", "h5vp_top_normal", 'Normal'),
            'quality' => Functions::getOptionDeep("h5vp_option", "h5vp_top_quality", 'Quality'),
            'loop' => Functions::getOptionDeep("h5vp_option", "h5vp_top_loop", 'Loop'),
            'start' => Functions::getOptionDeep("h5vp_option", "h5vp_top_start", 'Start'),
            'end' => Functions::getOptionDeep("h5vp_option", "h5vp_top_end", 'End'),
            'all' => Functions::getOptionDeep("h5vp_option", "h5vp_top_all", 'All'),
            'reset' => Functions::getOptionDeep("h5vp_option", "h5vp_top_reset", 'Reset'),
            'disabled' => Functions::getOptionDeep("h5vp_option", "h5vp_top_disabled", 'Disabled'),
            'enabled' => Functions::getOptionDeep("h5vp_option", "enabled", 'Enabled'),
            'advertisement' => Functions::getOptionDeep("h5vp_option", "h5vp_top_ad", 'Ad'),
            'qualityBadge' => [
                '2160' => '4K',
                '1440' => 'HD',
                '1080' => 'HD',
                '720' => 'HD',
                '576' => 'SD',
                '480' => 'SD',
            ],
        ];
    }

    public static function quickPlayer(){
        $controls = [
            'play-large' => Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_large_play_btn_quick', 'show'),
            'restart' => Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_restart_btn_quick', 'mobile'),
            'rewind' => Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_rewind_btn_quick', 'mobile'),
            'play' => Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_play_btn_quick', 'show') ,
            'fast-forward' => Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_fast_forward_btn_quick', 'mobile') ,
            'progress' => Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_video_progressbar_quick', 'show'),
            'current-time' => Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_current_time_quick', 'show'),
            'duration' => Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_video_duration_quick', 'mobile'),
            'mute' => Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_mute_btn_quick', 'show') ,
            'volume' => Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_volume_control_quick', 'show'),
            'captions' => 'show',
            'settings' => Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_Setting_btn_quick', 'show'),
            'pip' => Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_pip_btn_quick', 'mobile'),
            'airplay' => Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_airplay_btn_quick', 'mobile') ,
            'download' => Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_downlaod_btn_quick', 'mobile') ,
            'fullscreen' => Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_fullscreen_btn_quick', 'show'),
        ];
    
        $options = [
            'controls' => $controls,
            'tooltips' => [
                'controls' => true,
                'seek' => true,
            ],
            'seekTime' => (int)Functions::getOptionDeep('h5vp_quick', 'h5vp_seek_time_quick', '10'),
            'hideControls' => (boolean)Functions::getOptionDeep('h5vp_quick', 'h5vp_auto_hide_control_quick', '1'),
            // 'loop' => [
                // 'active' => (boolean)Functions::getOptionDeep('h5vp_quick', 'h5vp_repeat_quick', 'once', 'once')
            // ]
        ];
    
        $infos = [
            'videoWidth' => Functions::getOptionDeep('h5vp_quick', 'h5vp_player_width_quick', 0)
        ];
    
        $h5vp_all_video_quick = Functions::getOptionDeep('h5vp_quick', 'h5vp_all_video_quick', '0');

        $user = wp_get_current_user();

       return array(
            'ajax_url' => admin_url( 'admin-ajax.php'),
            'quickPlayer' => ['options' => $options, 'infos' => $infos],
            'globalWorking' => $h5vp_all_video_quick,
            'user' => [
                'email' => $user->data->user_email ?? '',
                'name' => $user->data->display_name ?? '',
            ]
       );
    }
}