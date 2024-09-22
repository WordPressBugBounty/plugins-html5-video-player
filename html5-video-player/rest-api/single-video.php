<?php
function h5vp_get_post_meta($id, $key, $default = null)
{
    if (metadata_exists('post', $id, $key)) {
        $value = get_post_meta($id, $key, true);
        if ($value != '') {
            return $value;
        } else {
            return $default;
        }
    } else {
        return $default;
    }
}

if(!class_exists('GET_SINGLE_VIDEO_ENDPOINTS')){
class GET_SINGLE_VIDEO_ENDPOINTS{
    public $route = null;
    function __construct(){
        $this->route = '/singlevideo(?:/(?P<id>\d+))?';
        add_action('rest_api_init', [$this, 'single_video_rest_api_endpoint']);
    }

    public function single_video_rest_api_endpoint(){
        register_rest_route( 'video/v1',
        $this->route,
         [
             'methods' => 'GET',
             'callback' => [$this, 'single_video_rest_api_endpoint_callback'],
             'permission_callback' => '__return_true',
         ] );
        
    }

    function single_video_rest_api_endpoint_callback(WP_REST_Request $request){
        $response = [];
        $params = $request->get_params();
        $id = $params['id'];

        $video = $this->get_video($id);
       

        return new WP_REST_Response($video);
    }


    public function get_video( $id = 2038){
        $result = [];
        $streaming = (boolean)h5vp_get_post_meta($id, 'h5vp_video_streaming', '0');
        $streamingType = h5vp_get_post_meta($id, 'h5vp_streaming_type', 'hls');
    
        $tagUrl = get_post_meta($id, 'h5vp_ad_tagUrl', true) ?? false;
            $ads = [];
            if($tagUrl){
                $tagUrl = str_replace('&amp;', '&', $tagUrl);
                $ads = ['enabled' => true,'tagUrl' => trim($tagUrl)];
            }else { $ads = ['enabled' => false];}

            $controls = [
                'play-large' => h5vp_get_post_meta($id, 'h5vp_hide_large_play_btn', 'show') ,
                'restart' => h5vp_get_post_meta($id, 'h5vp_hide_restart_btn', 'mobile'),
                'rewind' => h5vp_get_post_meta($id, 'h5vp_hide_rewind_btn', 'mobile'),
                'play' => h5vp_get_post_meta($id, 'h5vp_hide_play_btn', 'show') ,
                'fast-forward' => h5vp_get_post_meta($id, 'h5vp_hide_fast_forward_btn', 'mobile') ,
                'progress' => h5vp_get_post_meta($id, 'h5vp_hide_video_progressbar', 'show'),
                'current-time' => h5vp_get_post_meta($id, 'h5vp_hide_current_time', 'show'),
                'duration' => h5vp_get_post_meta($id, 'h5vp_hide_video_duration', 'mobile'),
                'mute' => h5vp_get_post_meta($id, 'h5vp_hide_mute_btn', 'show') ,
                'volume' => h5vp_get_post_meta($id, 'h5vp_hide_volume_control', 'show'),
                'captions' => 'show',
                'settings' => h5vp_get_post_meta($id, 'h5vp_hide_Setting_btn', 'show'),
                'pip' => h5vp_get_post_meta($id, 'h5vp_hide_pip_btn', 'mobile'),
                'airplay' => h5vp_get_post_meta($id, 'h5vp_hide_airplay_btn', 'mobile') ,
                'download' => h5vp_get_post_meta($id, 'h5vp_hide_downlaod_btn', 'mobile') ,
                'fullscreen' => h5vp_get_post_meta($id, 'h5vp_hide_fullscreen_btn', 'show'),
            ];

            $options = [
                'controls' => $controls,
                'tooltips' => [
                    'controls' => true,
                    'seek' => true,
                ],
                'seekTime' => (int)h5vp_get_post_meta($id, 'h5vp_seek_time_playerio', '10'),
                'loop' => [
                    'active' => (boolean)(h5vp_get_post_meta($id, 'h5vp_repeat_playerio', 'once') == 'loop') ?? false
                ],
                'autoplay' => (boolean)h5vp_get_post_meta($id, 'h5vp_auto_play_playerio', '0'),
                'muted' => (boolean)h5vp_get_post_meta($id, 'h5vp_muted_playerio', '0'),
                'hideControls' => (boolean)h5vp_get_post_meta($id, 'h5vp_auto_hide_control_playerio', '1'),
                'resetOnEnd' => (boolean)h5vp_get_post_meta($id, 'h5vp_reset_on_end_playerio', '1'),
                'ads' => $ads,
                'captions' => [
                    'active' => true,
                    'update' => true,
                ]

            ];

            // $options = json_encode($options);
            
            $provider = h5vp_get_post_meta($id, 'h5vp_video_source', 'library');
            $streaming = (boolean)h5vp_get_post_meta($id, 'h5vp_video_streaming', '0');
            $protected = (boolean)h5vp_get_post_meta($id, 'h5vp_password_protected', '0');
            $video = [];
            if(!$streaming){
                if($provider == 'library' || $provider == 'amazons3'){
                    $video = [
                        'source' => h5vp_get_post_meta($id, 'h5vp_video_link'),
                        'poster' => h5vp_get_post_meta($id, 'h5vp_video_thumbnails'),
                        'quality' => h5vp_get_post_meta($id, 'h5vp_quality_playerio', '720'),
                        'subtitle' => h5vp_get_post_meta($id, 'h5vp_subtitle_playerio'),
                    ];
                }else {
                    $video =  [
                        'source' => h5vp_get_post_meta($id, 'h5vp_video_link_youtube_vimeo'),
                        'poster' => h5vp_get_post_meta($id, 'h5vp_video_thumbnails'),
                    ];
                }
            }else {
                $video =  [
                    'source' => h5vp_get_post_meta($id, 'h5vp_video_link_hlsdash'),
                    'poster' => h5vp_get_post_meta($id, 'h5vp_video_thumbnails'),
                ];
            }

            $infos = [
                'id' => $id,
                'startTime' => (int)h5vp_get_post_meta($id, 'h5vp_start_time', '0'),
                'popup' => (boolean)h5vp_get_post_meta($id, 'h5vp_popup', '0'),
                'sticky' => h5vp_get_post_meta($id, 'h5vp_sticky_mode', '0'),
                'protected' => $protected,
                // 'video' => $video,
                'provider' => $provider,
                'streaming' => $streaming,
                'streamingType' => h5vp_get_post_meta($id, 'h5vp_streaming_type', 'hls'),
                'disableDownload' => (boolean)h5vp_get_post_meta($id, 'h5vp_disable_downlaod', false),
                'disablePause' => (boolean)h5vp_get_post_meta($id, 'h5vp_disable_pause', '0'),
            ];

            if($protected){
                $infos['video'] = [];
            }else {
                $infos['video'] = $video;
            }

            // $infos = json_encode($infos);

        $result = [
            'options' => $options,
            'infos' => $infos,
            'provider' => $provider
        ];

        

        return $result;
    }

    public function h5vp_get_post_meta($id, $key, $default = null){
        if (metadata_exists('post', $id, $key)) {
            $value = get_post_meta($id, $key, true);
            if ($value != '') {
                return $value;
            } else {
                return $default;
            }
        } else {
            return $default;
        }
    }

 
}
new GET_SINGLE_VIDEO_ENDPOINTS();
}