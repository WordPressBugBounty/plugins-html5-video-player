<?php
namespace H5VP\Services;
// require_once(__DIR__.'/VideoTemplate.php');
// require_once(__DIR__.'/PlaylistTemplate.php');
// require_once(__DIR__.'/../Helper/DefaultArgs.php');

use H5VP\Helper\DefaultArgs;
use H5VP\Services\VideoTemplate;
use H5VP\Services\PlaylistTemplate;

class AnalogSystem{

    public static function html($id){
        $data  = DefaultArgs::parseArgs(self::getData($id));
        return VideoTemplate::html($data);
    }

    public static function playlistHtml($id){
        $data  = DefaultArgs::parsePlaylistArgs(self::getPlaylistData($id));
        return $data;
        return PlaylistTemplate::html_old($data);
    }

    static function parsePlaylistData($id){
        $videos = self::get_videos($id, 'h5vp_playlist', []);
        $meta = get_post_meta($id, 'h5vp_playlist', true);
        $options_meta = get_post_meta($id, 'h5vp_playlist_options', true);
        //GPM = get playlist metadat
        $controls = [
            'play-large' => self::GPM($id, 'h5vp_hide_large_play_btn', 'show'),
            'restart' => self::GPM($id, 'h5vp_hide_restart_btn', 'mobile'),
            'rewind' => self::GPM($id, 'h5vp_hide_rewind_btn', 'mobile'),
            'play' => self::GPM($id, 'h5vp_hide_play_btn', 'show') ,
            'fast-forward' => self::GPM($id, 'h5vp_hide_fast_forward_btn', 'mobile') ,
            'progress' => self::GPM($id, 'h5vp_hide_video_progressbar', 'show'),
            'current-time' => self::GPM($id, 'h5vp_hide_current_time', 'show'),
            'duration' => self::GPM($id, 'h5vp_hide_video_duration', 'mobile'),
            'mute' => self::GPM($id, 'h5vp_hide_mute_btn', 'show') ,
            'volume' => self::GPM($id, 'h5vp_hide_volume_control', 'show'),
            'captions' => 'show',
            'settings' => self::GPM($id, 'h5vp_hide_Setting_btn', 'show'),
            'pip' => self::GPM($id, 'h5vp_hide_pip_btn', 'mobile'),
            'airplay' => self::GPM($id, 'h5vp_hide_airplay_btn', 'mobile') ,
            'download' => self::GPM($id, 'h5vp_hide_downlaod_btn', 'mobile') ,
            'fullscreen' => self::GPM($id, 'h5vp_hide_fullscreen_btn', 'show'),
        ];

        $controls = array_filter($controls, function($control){ return $control != 'hide';});

        $controls = array_keys($controls);
    
        $options = [
            'controls' => $meta['h5vp_controls'] ?? $controls,
            'muted' => (boolean)self::GPM($id, 'h5vp_muted_playerio', '0', true),
            'seekTime' => (int)self::GPM($id, 'h5vp_seek_time_playerio', '10'),
            'hideControls' => (boolean)self::GPM($id, 'h5vp_auto_hide_control_playerio', '1', true),
            'resetOnEnd' => true,
            'autoplayNextVideo' => $options_meta['h5vp_play_nextvideo'] === 'yes' ?? false,
        ];

        return [
            'uniqueId' => 'h5vp_playlist_'.uniqid(),
            'options' => $options,
            'videos' => $videos,
            'styles' => [
                'h5vp_playlist_container' => [
                    // 'background' =>self::GPML($id, 'listbg', '#fff'),
                    'width' => $options_meta['h5vp_player_width_playerio'] ? $options_meta['h5vp_player_width_playerio'].'px' : '100%',
                    'max-width' => '100%'
                ],
                // 'video-block__title' => [
                //     'color' =>self::GPML($id, 'text_color', '#333')
                // ],
                // 'video-block__content' => [
                //     'color' =>self::GPML($id, 'text_color', '#333')
                // ]
            ]
        ];
    }

    public static function parseArgs($data){
        $default = DefaultArgs::get();
        $data['options'] = wp_parse_args( $data['options'], $default['options'] );
        $infos = wp_parse_args( $data['infos'], $default['infos'] );
        $template = wp_parse_args( $data['template'], $default['template'] );

        return wp_parse_args( $data, $default );
    }

    public static function getData($id){
        $result = [];
        $streaming = (boolean)self::get_post_meta($id, 'h5vp_video_streaming', '0');
        $streamingType = self::get_post_meta($id, 'h5vp_streaming_type', 'hls');
        $provider = self::get_post_meta($id, 'h5vp_video_source', 'library');
        $streaming = (boolean)self::get_post_meta($id, 'h5vp_video_streaming', '0');
        $protected = (boolean)self::get_post_meta($id, 'h5vp_password_protected', '0');
        $video = [];


        if($provider == 'amazons3'){
            $provider = 'library';
        }

        $tagUrl = get_post_meta($id, 'h5vp_ad_tagUrl', true) ? get_post_meta($id, 'h5vp_ad_tagUrl', true) : false;
        $ads = [];
        if($tagUrl){
            $tagUrl = str_replace('&amp;', '&', $tagUrl);
            $ads = ['enabled' => true,'tagUrl' => trim($tagUrl)];
        }else { $ads = ['enabled' => false];}

        $latest_controls = self::get_post_meta($id, 'h5vp_controls', null);

        $controls = [
            'play-large' => self::get_post_meta($id, 'h5vp_hide_large_play_btn', 'show') ,
            'restart' => self::get_post_meta($id, 'h5vp_hide_restart_btn', 'mobile'),
            'rewind' => self::get_post_meta($id, 'h5vp_hide_rewind_btn', 'mobile'),
            'play' => self::get_post_meta($id, 'h5vp_hide_play_btn', 'show') ,
            'fast-forward' => self::get_post_meta($id, 'h5vp_hide_fast_forward_btn', 'mobile') ,
            'progress' => self::get_post_meta($id, 'h5vp_hide_video_progressbar', 'show'),
            'current-time' => self::get_post_meta($id, 'h5vp_hide_current_time', 'show'),
            'duration' => self::get_post_meta($id, 'h5vp_hide_video_duration', 'mobile'),
            'mute' => self::get_post_meta($id, 'h5vp_hide_mute_btn', 'show'),
            'volume' => self::get_post_meta($id, 'h5vp_hide_volume_control', 'show'),
            'captions' => 'show',
            'settings' => self::get_post_meta($id, 'h5vp_hide_Setting_btn', 'show'),
            'pip' => self::get_post_meta($id, 'h5vp_hide_pip_btn', 'mobile'),
            'airplay' => self::get_post_meta($id, 'h5vp_hide_airplay_btn', 'mobile') ,
            'download' => self::get_post_meta($id, 'h5vp_hide_downlaod_btn', 'mobile') ,
            'fullscreen' => self::get_post_meta($id, 'h5vp_hide_fullscreen_btn', 'show'),
        ];

        $final_controls = [];
        foreach($controls as $key => $control){
            if($control === 'show'){
                array_push($final_controls, $key);
            }
        }
        
        $chapters = self::get_post_meta($id, 'h5vp_chapters', []);

        $markers = [];
        foreach($chapters as $key => $value){
            $value['label'] = $value['name'];
            array_push($markers, $value);
            
        }

        $options = [
            'controls' => $latest_controls ? $latest_controls : $final_controls,
            'tooltips' => [
                'controls' => true,
                'seek' => true,
            ],
            'seekTime' => (int)self::get_post_meta($id, 'h5vp_seek_time_playerio', '10'),
            'loop' => [
                'active' => (boolean)(self::get_post_meta($id, 'h5vp_repeat_playerio', false) == 'loop') 
            ],
            'autoplay' => (boolean) self::get_post_meta($id, 'h5vp_auto_play_playerio', '0'),
            'muted' => (boolean)self::get_post_meta($id, 'h5vp_muted_playerio', '0'),
            'hideControls' => (boolean)self::get_post_meta($id, 'h5vp_auto_hide_control_playerio', '1'),
            'resetOnEnd' => (boolean)self::get_post_meta($id, 'h5vp_reset_on_end_playerio', '1'),
            'ads' => $ads,
            'captions' => [
                'active' => false,
                'update' => false,
                'language' => 'auto'
            ],
            'markers' => [
                'enabled' => true,
                'points' => $markers
            ],
            'urls' => [
                'enabled' => self::get_post_meta($id, 'isCDURL', '0') == '1',
                'download' =>  self::get_post_meta($id, 'CDURL', null),
            ],
            'preload' => self::get_post_meta($id, 'h5vp_preload_playerio', 'metadata'),
            'ratio' => self::get_post_meta($id, 'h5vp_ratio', null),
            'ads' => [
                'enabled' => self::get_post_meta($id, 'h5vp_ad_tagUrl') != '',
                'tagUrl' => self::get_post_meta($id, 'h5vp_ad_tagUrl')
            ],

        ];

        $infos = [
            'id' => (int)$id,
            'startTime' => (int)self::get_post_meta($id, 'h5vp_start_time', '0'),
            'popup' => (boolean)self::get_post_meta($id, 'h5vp_popup', '0'),
            'sticky' => (boolean) self::get_post_meta($id, 'h5vp_sticky_mode', '0'),
            'stickyPosition' => 'top_right',
            'protected' => $protected,
            'video' => $video,
            'provider' => $provider,
            'streaming' => $streaming,
            'thumbInPause' => (boolean)self::get_post_meta($id, 'h5vp_poster_when_pause', false, true),
            'thumbStyle' => 'default',
            'endscreen' => (boolean)self::get_post_meta($id, 'h5vp_endscreen_enable', '0'),
            'endscreen_text' => get_post_meta($id, 'h5vp_endscreen_text', true),
            'endscreen_text_link' => get_post_meta($id, 'h5vp_endscreen_text_link', true),
            'streamingType' => self::get_post_meta($id, 'h5vp_streaming_type', 'hls'),
            'disableDownload' => (boolean)self::get_post_meta($id, 'h5vp_disable_downlaod', false),
            'disablePause' => (boolean)self::get_post_meta($id, 'h5vp_disable_pause', '0'),
            'propagans' => self::get_post_meta($id, 'h5vp_protected_password', ''),
            'count' => false, // false for one user
            'FYT' => self::get_post_meta($id, 'force_custom_thumbnail', '0') == '1' ? true : false,
            'chapters' => self::get_post_meta($id, 'h5vp_chapters', []),
            'isCDURL' => self::get_post_meta($id, 'isCDURL'),
            'CDURL' => self::get_post_meta($id, 'CDURL'),
            'captionEnabled' => (boolean)self::get_post_meta($id, 'h5vp_enable_caption', false),
        ];

        $branding_logo = get_post_meta($id,'h5vp_overlay_logo', true);
        
        $template = array(
            'branding' => (boolean)get_post_meta($id,'h5vp_enable_overlay', true),
            'branding_type' => get_post_meta($id,'h5vp_overlay_type', true) === '1' ? 'text' : 'logo',
            'branding_logo' => isset($branding_logo['url']) ? $branding_logo['url'] : '',
            'branding_background' => '#333',
            'branding_text' => get_post_meta($id,'h5vp_overlay_text', true),
            'branding_opacity' => '0.7',
            'branding_link' => get_post_meta($id,'h5vp_overlay_url', true),
            'branding_color' => get_post_meta($id,'h5vp_overlay_text_color', true),
            'branding_font_size' => '17px',
            'branding_hover_color' => get_post_meta($id,'h5vp_overlay_hover_color', true),
            'branding_position' => get_post_meta($id,'h5vp_overlay_position', true),
            'branding_position_first_type' => get_post_meta($id,'h5vp_overlay_position_type_first', true),
            'branding_position_first' => get_post_meta($id,'h5vp_overlay_position_first', true).'px',
            'branding_position_second_type' => get_post_meta($id,'h5vp_overlay_position_type_second', true),
            'branding_position_second' => get_post_meta($id,'h5vp_overlay_position_second', true).'px',
            //endscreen
            'endscreen' => (boolean)get_post_meta($id, 'h5vp_endscreen_enable', true),
            'endscreen_text' => get_post_meta($id, 'h5vp_endscreen_text', true),
            'endscreen_text_link' => get_post_meta($id, 'h5vp_endscreen_text_link', true),
            'endscreen_text_color' => get_post_meta($id, 'h5vp_endscreen_text_color', true),
            'endscreen_text_color_hover' => get_post_meta($id, 'h5vp_endscreen_text_color_hover', true),
            'popup' => (boolean) self::get_post_meta($id, 'h5vp_popup', '0'),
            'poster' => self::get_post_meta($id, 'h5vp_video_thumbnails'),
            'streaming' => (boolean)self::get_post_meta($id, 'h5vp_video_streaming', '0'),
            'streamingType' => self::get_post_meta($id, 'h5vp_streaming_type', 'hls'),
            'protected_text' => self::get_post_meta($id, 'h5vp_protected_password_text', 'Enter the password to watch the video'),
            'preload' => self::get_post_meta($id, 'h5vp_preload_playerio', 'metadata'),
            'subtitles' => self::get_post_meta($id, 'h5vp_subtitle_playerio', []),
            'hideYoutubeUI' => self::get_post_meta($id, 'hideYoutubeUI', false),
            'width' => self::get_post_meta($id, 'h5vp_player_width_playerio', 0) == 0 ? '100%' : self::get_post_meta($id, 'h5vp_player_width_playerio', 0).'px' ,
            'controlsShadow' => self::get_post_meta($id, 'h5vp_hide_control_shadow', 'show') == 'show' ? true : false,
            'FYT' => self::get_post_meta($id, 'force_custom_thumbnail', '0') == '1' ? true : false,
            // 'thumbnail' =>  self::get_post_meta($id, 'h5vp_video_thumbnails'),
            'title' => get_the_title( $id )
        );

        $source = self::get_post_meta($id, 'h5vp_video_link');
        $provider = self::get_post_meta($id, 'h5vp_video_source');
        if(in_array($provider, ['youtube', 'vimeo'])){
            $source = self::get_post_meta($id, 'h5vp_video_link_youtube_vimeo');
        }


        $finalData = wp_parse_args([], [
            'uniqueId' => 'h5vp'.uniqid(),
            'options' => $options,
            'features' => [
                'overlay' => [
                    'enabled' => $template['branding'],
                    'items' => [
                       [
                        'color' => $template['branding_color'],
                        'hoverColor' => $template['branding_hover_color'],
                        'fontSize' => $template['branding_font_size'],
                        'link' => $template['branding_link'],
                        'logo' => $template['branding_logo'],
                        'position' => self::get_post_meta($id, 'overlay_position'),
                        'text' => $template['branding_text'],
                        'type' => $template['branding_type'],
                        'backgroundColor' => self::get_post_meta($id, 'h5vp_overlay_background'),
                        'opacity' => self::get_post_meta($id, 'overlay_opacity'),
                       ]
                    ]

                ],
                'sticky' => [
                    'enabled' => $infos['sticky'],
                    'position' => str_replace('-', '_', $infos['stickyPosition']),
                ],
                'chapters' => $infos['chapters'],
                'watermark' => [
                    'enabled' => false,
                ],
                'thumbInPause' => [
                    'enabled' => $infos['thumbInPause'],
                    'type' => $infos['thumbStyle']
                ],
                'endScreen' => [
                    'enabled' => self::get_post_meta($id, 'h5vp_endscreen_enable') == '1',
                    'text' => self::get_post_meta($id, 'h5vp_endscreen_text'),
                    'btnText' => self::get_post_meta($id, 'endscreen_btn_text'),
                    'btnLink' => self::get_post_meta($id, 'endscreen_btn_link')
                ],
                'popup' => [
                    'enabled' => $infos['popup'],
                    'selector' => '',
                    'hasBtn'=> false,
                    'type' => 'poster',
                    'btnText' => 'Watch Video',
                    'btnStyle' => [],
                ],
                'playWhenVisible' => false,
                'hideLoadingPlaceholder' => self::get_post_meta($id, 'h5vp_hide_loading_placeholder', false),
                'passwordProtected' => [
                    'enabled' => self::get_post_meta($id, 'h5vp_password_protected', false) == '1'? true : false,
                    'key' => "propagans_$id",
                    'heading' => self::get_post_meta($id, 'h5vp_protected_password_text', ''),
                ]
            ],
            'data' => [
                
            ],
            'propagans' => $infos['propagans'],
            'customDownloadURL' => $infos['CDURL'],
            'captionEnabled' => $infos['captionEnabled'],
            'disableDownload' => $infos['disableDownload'],
            'disablePause' => $infos['disablePause'],
            'startTime' => $infos['startTime'],
            'saveState' => false,
            'protected' => $infos['protected'],
            'hideYoutubeUI' => $template['hideYoutubeUI'],
            'source' => $source,
            'poster' => self::get_post_meta($id, 'h5vp_video_thumbnails'),
            'qualities' => self::get_post_meta($id, 'h5vp_quality_playerio', []),
            'captions' => self::get_post_meta($id, 'h5vp_subtitle_playerio', []),
            'styles' => [
                'plyr_wrapper' => [
                    'width' => self::get_post_meta($id, 'h5vp_player_width_playerio') . 'px',
                ]
            ]
        ]); 

        $finalData['styles'] = wp_parse_args($finalData['styles'], [
            'plyr--video' => [
                    '--plyr-color-main' => DefaultArgs::brandColor()
                ],
            ]);

        return $finalData;
    }

    public static function getPlaylistData($id){
        $videos = self::get_videos($id, 'h5vp_playlist', []);
        //GPM = get playlist metadat
        $controls = [
            'play-large' => self::GPM($id, 'h5vp_hide_large_play_btn', 'show'),
            'restart' => self::GPM($id, 'h5vp_hide_restart_btn', 'mobile'),
            'rewind' => self::GPM($id, 'h5vp_hide_rewind_btn', 'mobile'),
            'play' => self::GPM($id, 'h5vp_hide_play_btn', 'show') ,
            'fast-forward' => self::GPM($id, 'h5vp_hide_fast_forward_btn', 'mobile') ,
            'progress' => self::GPM($id, 'h5vp_hide_video_progressbar', 'show'),
            'current-time' => self::GPM($id, 'h5vp_hide_current_time', 'show'),
            'duration' => self::GPM($id, 'h5vp_hide_video_duration', 'mobile'),
            'mute' => self::GPM($id, 'h5vp_hide_mute_btn', 'show') ,
            'volume' => self::GPM($id, 'h5vp_hide_volume_control', 'show'),
            'captions' => 'show',
            'settings' => self::GPM($id, 'h5vp_hide_Setting_btn', 'show'),
            'pip' => self::GPM($id, 'h5vp_hide_pip_btn', 'mobile'),
            'airplay' => self::GPM($id, 'h5vp_hide_airplay_btn', 'mobile') ,
            'download' => self::GPM($id, 'h5vp_hide_downlaod_btn', 'mobile') ,
            'fullscreen' => self::GPM($id, 'h5vp_hide_fullscreen_btn', 'show'),
        ];

        $controls = array_filter($controls, function($control){ return $control != 'hide';});

        $controls = array_keys($controls);
    
        $options = [
            'controls' => $videos['h5vp_controls'] ?? $controls,
            'muted' => (boolean)self::GPM($id, 'h5vp_muted_playerio', '0', true),
            'seekTime' => (int)self::GPM($id, 'h5vp_seek_time_playerio', '10'),
            'hideControls' => (boolean)self::GPM($id, 'h5vp_auto_hide_control_playerio', '1', true),
            'resetOnEnd' => true,
        ];
    
        $infos = [
            'id' => $id,
            'loop' => self::GPM($id, 'h5vp_repeat_playlist', 'yes'),
            'next' => self::GPM($id, 'h5vp_play_nextvideo', 'yes'),
            'viewType' => self::GPM($id, 'h5vp_playlist_view_type', 'listwithposter'),
            'carouselItems' => self::GPM($id, 'h5vp_listwithposter_colum', '3'),
            'provider' => isset($videos[0]['h5vp_video_provider']) ? $videos[0]['h5vp_video_provider'] : 'library',
            'slideVideos' => self::GPM($id, 'h5vp_playlist_view_type') == 'listwithposter' ? self::GPM($id, 'slide_videos', true, true) : false ,
        ];

        $borderWidth = self::get_videos($id, 'border_width', ['all' => '7', 'unit' => 'px']);

        $template = [
            'videos' => $videos,
            'width' => self::GPM($id, 'h5vp_player_width_playerio') ? self::GPM($id, 'h5vp_player_width_playerio') . 'px' : '100%',
            'skin' => self::GPM($id, 'h5vp_playlist_view_type', 'listwithposter'),
            'arrowSize' => self::GPM($id, 'h5vp_listwithposter_arrow_size', '25').'px',
            'arrowColor' => self::GPM($id, 'h5vp_listwithposter_arrow_color', '#222'),
            'preload' => self::GPM($id, 'h5vp_preload_playerio', 'metadata'),
            'slideVideos' => self::GPM($id, 'slide_videos', true),
            'column' => (int) self::GPM($id, 'h5vp_listwithposter_colum', '3'),
            'modern' => self::GPM($id, 'modern', 'imageText'),
            'listBG' => self::get_videos($id, 'listbg', '#ffffff'),
            'textColor' => self::get_videos($id, 'text_color', self::GPM($id, 'h5vp_listwithposter_text_color', '#333')),
            'listHoverBG' => self::get_videos($id, 'listhoverbg', '#333333'),
            'textHoverColor' => self::get_videos($id, 'text_hover_color', '#ffffff'),
            'borderColor' => self::get_videos($id, 'border_color', '#ffffff'),
            'borderWidth' => $borderWidth['all'].$borderWidth['unit'],
        ];
		
        return [
            'options' => $options,
            'infos' => $infos,
            'template' => $template,
        ];

        return [
            'options' => $options,
            'styles' => []
        ];
    }

    public static function get_post_meta($id, $key, $default = false){
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

    public static function GPM($id, $key, $default = false, $true = false){
        $meta = metadata_exists( 'post', $id, 'h5vp_playlist_options' ) ? get_post_meta($id, 'h5vp_playlist_options', true) : '';
        if(isset($meta[$key]) && $meta != ''){
            if($true == true){
                if($meta[$key] == '1'){
                    return true;
                }else if($meta[$key] == '0'){
                    return false;
                }
            }else {
                return $meta[$key];
            }
            
        }

        return $default;
    }

    // get playlist meta latest
    public static function GPML($id, $key, $default = false, $true = false){
        $meta = metadata_exists( 'post', $id, 'h5vp_playlist' ) ? get_post_meta($id, 'h5vp_playlist', true) : '';
        if(isset($meta[$key]) && $meta != ''){
            if($true == true){
                if($meta[$key] == '1'){
                    return true;
                }else if($meta[$key] == '0'){
                    return false;
                }
            }else {
                return $meta[$key];
            }
            
        }

        return $default;
    }

    private static function get_videos($id, $key, $default = null, $true = false){
        $meta = metadata_exists( 'post', $id, 'h5vp_playlist' ) ? get_post_meta( $id, 'h5vp_playlist', true ) : '';
        if(isset($meta[$key]) && $meta[$key] != '' && $true == true){
            return true;
        }elseif(isset($meta[$key]) && $meta[$key] != '') {
            return $meta[$key];
        }else {
            return $default;
        }
    } 

    public static function getQuickPlayerData(){
        
    }
}


// add_filter('template_include', 'h5ap_search_template');

// function h5ap_search_template($template){
//     global $wp_query;
//     if (!isset($_GET['bps']))
//         return $template;
    
//     return dirname( __FILE__ ) . '/inc/Template/search.php';

// } /*UPDATE: there was a missing ";" after $template*/
