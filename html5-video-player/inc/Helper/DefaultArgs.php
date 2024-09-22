<?php
namespace H5VP\Helper;

class DefaultArgs{

    public static function parseArgs($data){
        $default = self::get();
        $data = wp_parse_args( $data, $default );
        $data['options'] = wp_parse_args( $data['options'], $default['options'] );
        $data['features'] = wp_parse_args( $data['features'], $default['features'] );

		return $data;
    }

    public static function parsePlaylistArgs($data = []){
        $default = self::gePlaylistDefaultArgs();
        $data = wp_parse_args( $data, $default );
        $data['options'] = wp_parse_args( $data['options'], $default['options'] );
        $data['infos'] = wp_parse_args( $data['infos'], $default['infos'] );
        $data['template'] = wp_parse_args( $data['template'], $default['template'] );
        $data['template']['videos'] = wp_parse_args( $data['template']['videos'], $default['template']['videos'] );

        return $data;
    }

    public static function get(){
        $controls = [
            'play-large' => 'show',
            'restart' => 'hide',
            'rewind' => 'mobile',
            'play' => 'show',
            'fast-forward' => 'mobile',
            'progress' => 'show',
            'current-time' => 'show',
            'duration' => 'hide',
            'mute' => 'show' ,
            'volume' => 'show',
            'captions' => 'show',
            'settings' => 'show',
            'pip' => 'hide',
            'airplay' => 'hide' ,
            'download' => 'hide' ,
            'fullscreen' => 'show',
        ];

        $options = [
            'controls' => $controls,
            'tooltips' => [
                'controls' => true,
                'seek' => true,
            ],
            'seekTime' => (int)10,
            'loop' => [
                'active' => false 
            ],
            'autoplay' => false,
            'muted' => false,
            'hideControls' => true,
            'resetOnEnd' => false,
            'captions' => [
                'active' => true,
                'update' => false,
                'language' =>  'auto',
            ],
            'ratio' => null,
            'speed' => [
                'selected' => 1, 
                'options' => [0.5,0.75, 1, 1.25, 1.75]
            ],
            'storage' => [
                'enabled' => true,
            ],
            'urls' => [
                'download' => null
            ],
            'markers' => [
                'enabled' => false,
                'points' => []
            ],
            'ads' => [
                'enabled' => false,
            ]
        ];


        return [
            // 'type' => 'block',
            'additionalID' => '',
            'options' => $options,
            'features' => [
                'overlay' => [
                    'enabled' => false,
                    'items' => [
                       [
                        'color' => '#fff',
                        'hoverColor' => '#fff',
                        'fontSize' => '17px',
                        'link' => '#',
                        'logo' => false,
                        'position' => 'top_right',
                        'text' => 'Overlay Sample Text',
                        'type' => 'text',
                        'backgroundColor' => '#333',
                        'opacity' => '0.7',
                       ]
                    ]

                ],
                'sticky' => [
                    'enabled' => false,
                    'position' => 'top_left',
                ],
                'chapters' => [],
                'watermark' => [
                    'enabled' => false,
                ],
                'thumbInPause' => [
                    'enabled' => false,
                    'type' => 'default'
                ],
                'endScreen' => [
                    'enabled' => false,
                    'text' => '',
                    'btnText' => 'Watch Again',
                    'btnLink' => '#'
                ],
                'popup' => [
                    'enabled' => false,
                    'selector' => '',
                    'hasBtn'=> false,
                    'type' => 'button',
                    'btnText' => 'Watch Video',
                    'btnStyle' => [],
                ],
                "hideYoutubeUI" => false,
                "hideLoadingPlaceholder" => false,
                "passwordProtected" => [
                    'enabled' => false,
                    'password' => '',
                    'key' => '',
                ]
            ],
            'propagans' => '',
            'customDownloadURL' => '',
            'captionEnabled' => false,
            'disableDownload' => false,
            'disablePause' => false,
            'startTime' => 0,
            'autoplayWhenVisible' => false,
            'saveState' => false,
            'protected' => false,
            'captions' => [],
            'qualities' => [],
            'source' => '',
            'poster' => '',
            'hideYoutubeUI' => true,
            'branding' => [
                'enabled' => false,
                'color' => '#fff'
            ],
            'styles' => [],
        ];
    }

    public static function gePlaylistDefaultArgs(){
		
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

    public static function brandColor(){
        $brandColor = get_option('h5vp_option', ['h5vp_player_primary_color' => '#1ABAFF' ]);
        if(isset($brandColor['h5vp_player_primary_color']) && !empty($brandColor['h5vp_player_primary_color'])){
            return $brandColor['h5vp_player_primary_color'];
        }else {
            return '#1ABAFF';
        }
    }
}