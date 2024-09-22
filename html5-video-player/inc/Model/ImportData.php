<?php
namespace H5VP\Model;

class ImportData{

    public static function importMeta(){
        if(!current_user_can('edit_posts')){
            return false;
        }
        $controls_meta = [
            'play-large' => 'h5vp_hide_large_play_btn',
            'restart' => 'h5vp_hide_restart_btn',
            'play' => 'h5vp_hide_play_btn',
            'rewind' => 'h5vp_hide_rewind_btn',
            'fast-forward' => 'h5vp_hide_fast_forward_btn',
            'progress' => 'h5vp_hide_video_progressbar',
            'current-time' => 'h5vp_hide_current_time',
            'duration' => 'h5vp_hide_video_duration',
            'mute' => 'h5vp_hide_mute_btn',
            'volume' => 'h5vp_hide_volume_control',
            'settings' => 'h5vp_hide_Setting_btn',
            'pip' => 'h5vp_hide_pip_btn',
            'airplay' => 'h5vp_hide_airplay_btn',
            'fullscreen' => 'h5vp_hide_fullscreen_btn',
            'download' => 'h5vp_hide_downlaod_btn',
        ];

        $meta_keys = [
            '_ahp_video-file' => 'h5vp_video_link',
            '_ahp_video-poster' => 'h5vp_video_thumbnails',
            '_ahp_video-repeat' => 'h5vp_repeat_playerio',
            '_ahp_video-muted' => 'h5vp_muted_playerio',
            '_ahp_video-autoplay' => 'h5vp_auto_play_playerio',
            '_ahp_video-control' => 'h5vp_auto_hide_control_playerio',
            '_ahp_video-size' => 'h5vp_player_width_playerio',
            '_ahp_seek_time' => 'h5vp_seek_time_playerio',
            '_ahp_preload' => 'h5vp_preload_playerio',
            'blog_group' => 'h5vp_quality_playerio',
            'caption_group' => 'h5vp_subtitle_playerio',
            // controls
            '_ahp_hide_large_play' => 'h5vp_hide_large_play_btn',
            '_ahp_restart_button' => 'h5vp_hide_restart_btn',
            '_ahp_play_button' => 'h5vp_hide_play_btn',
            '_ahp_rewind_button' => 'h5vp_hide_rewind_btn',
            '_ahp_fast_forward' => 'h5vp_hide_fast_forward_btn',
            '_ahp_progress' => 'h5vp_hide_video_progressbar',
            '_ahp_current_time' => 'h5vp_hide_current_time',
            '_ahp_duration' => 'h5vp_hide_video_duration',
            '_ahp_mute_button' => 'h5vp_hide_mute_btn',
            '_ahp_volume_button' => 'h5vp_hide_volume_control',
            '_ahp_setting_button' => 'h5vp_hide_Setting_btn',
            '_ahp_pip_button' => 'h5vp_hide_pip_btn',
            '_ahp_airplay_button' => 'h5vp_hide_airplay_btn',
            '_ahp_fullscreen_button' => 'h5vp_hide_fullscreen_btn',
            '_ahp_download_button' => 'h5vp_hide_downlaod_btn',
            '_ahp_hide_shadow' => 'h5vp_hide_control_shadow',
        ];
        $videos = new \WP_Query(array(
            'post_type' => 'videoplayer',
            'post_status' => 'any',
            'posts_per_page' => -1
        ));
        
        while ($videos->have_posts()): $videos->the_post();
            $id = \get_the_ID();
            foreach ($meta_keys as $old_meta => $new_meta) {
                if (\metadata_exists('post', $id, $old_meta) && \metadata_exists('post', $id, $new_meta) == false) {
                    if (\get_post_meta($id, $old_meta, true) == 'on') {
                        \update_post_meta($id, $new_meta, 'hide');
                    } else {
                        \update_post_meta($id, $new_meta, \get_post_meta($id, $old_meta, true));
                    }
                }
            }
            $controls = get_post_meta($id, 'h5vp_controls', true);
            if(is_array($controls)){
                foreach($controls_meta as $key => $value){
                    if(\metadata_exists('post', $id, $value) == false){
                        if(in_array($key, $controls)){
                            \update_post_meta($id, $value, 'show');
                        }else {
                            \update_post_meta($id, $value, 'hide');
                        }
                    }
                }
            }
        endwhile;
    }
}