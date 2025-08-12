<?php

namespace H5VP\Helper;

class Block
{

    private static $_instance = null;
    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function classic_to_gutenberg_block($id)
    {
        $provider = $this->get_post_meta($id, 'h5vp_video_source', 'self-hosted');
        $block_name = in_array($provider, ['amazons3', 'self-hosted', 'library']) ? 'video' : $provider;
        $width = $this->get_post_meta($id, 'h5vp_player_width_playerio') ? $this->get_post_meta($id, 'h5vp_player_width_playerio') . 'px' : '100%';
        $source = $this->get_post_meta($id, 'h5vp_video_link');
        $option = h5vp_get_option('h5vp_option', []);

        $chapters = $this->get_post_meta($id, 'h5vp_chapters', []);

        if (count($chapters) > 0) {
            foreach ($chapters as $key => $chapter) {
                $chapters[$key]['label'] = $chapter['name'];
                $chapters[$key]['time'] = (int) $chapter['time'];
            }
        }

        if (in_array($provider, ['vimeo', 'youtube'])) {
            $source = $this->get_post_meta($id, 'h5vp_video_link_youtube_vimeo', '');
        }

        return [
            'blockName' => "html5-player/$block_name",
            'attrs' => [
                "provider" => $provider === 'library' ? 'self-hosted' : $provider,
                "imported" => false,
                "clientId" => "",
                "uniqueId" => wp_unique_id('h5vp'),
                "source" => $source,
                "poster" => $this->get_post_meta($id, 'h5vp_video_thumbnails', ''),
                "options" => [
                    "controls" => $this->get_post_meta($id, 'h5vp_controls', ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'captions', 'settings', 'pip', 'airplay', 'fullscreen']),
                    "settings" => ["captions", "quality", "speed", "loop"],
                    "loadSprite" => true,
                    "autoplay" => $this->get_post_meta($id, 'h5vp_auto_play_playerio', false, true),
                    "playsinline" => true,
                    "seekTime" => (int)$this->get_post_meta($id, 'h5vp_seek_time_playerio', 10),
                    "volume" => 1,
                    "muted" => $this->get_post_meta($id, 'h5vp_muted_playerio', false, true),
                    "hideControls" => $this->get_post_meta($id, 'h5vp_auto_hide_control_playerio', false, true),
                    "resetOnEnd" => $this->get_post_meta($id, 'h5vp_reset_on_end_playerio', false, true),
                    "tooltips" => [
                        "controls" => true,
                        "seek" => true
                    ],
                    "captions" => [
                        "active" => $this->get_post_meta($id, 'h5vp_enable_caption', false, true),
                        "language" => "auto",
                        "update" => true
                    ],
                    "ratio" => $this->get_post_meta($id, 'h5vp_ratio', '16:9'),
                    "storage" => [
                        "enabled" => true,
                        "key" => "plyr"
                    ],
                    "speed" => [
                        "options" => explode(',', $option('h5vp_speed', '0.5, 0.75, 1, 1.25, 1.5, 1.75, 2, 4'))
                    ],
                    "loop" => [
                        "active" => $this->get_post_meta($id, 'h5vp_repeat_playerio', false) === 'loop',
                    ],
                    "ads" => [
                        "enabled" => true,
                        "tagUrl" => $this->get_post_meta($id, 'h5vp_ad_tagUrl'),
                    ],
                    "urls" => [
                        "enabled" => $this->get_post_meta($id, 'isCDURL', false, true),
                        "download" => $this->get_post_meta($id, 'CDURL', null),
                    ],
                    "markers" => [
                        "enabled" => count($chapters) > 0,
                        "points" => $chapters,
                    ],
                    "preload" => 'none'
                ],
                "features" => [
                    "popup" => [
                        "enabled" => $this->get_post_meta($id, 'h5vp_popup', false, true),
                        "selector" => null,
                        "hasBtn" => false,
                        "type" => "poster",
                        "btnText" => "Watch Video",
                        "align" => "center",
                        "btnStyle" => [
                            "color" => "#fff",
                            "backgroundColor" => "#006BA1",
                            "fontSize" => "16px",
                            "padding" => [
                                "top" => "10px",
                                "right" => "20px",
                                "bottom" => "10px",
                                "left" => "20px"
                            ]
                        ]
                    ],
                    "overlay" => [
                        "enabled" => $this->get_post_meta($id, 'h5vp_enable_overlay', false, true),
                        "items" => [[
                            "color" => $this->get_post_meta($id, 'h5vp_overlay_text_color', '#ffffff'), // -,
                            "backgroundColor" => $this->get_post_meta($id, 'h5vp_overlay_background', '#333'),
                            "fontSize" => "16px",
                            "link" => $this->get_post_meta($id, 'h5vp_overlay_url', ''),
                            "logo" => $this->get_post_meta($id, 'h5vp_overlay_logo', [])['url'] ?? null, // image url or base64 image pat
                            "text" => $this->get_post_meta($id, 'h5vp_overlay_text', ''),
                            "position" => $this->get_post_meta($id, 'overlay_position', 'top_right'),
                            "type" => $this->get_post_meta($id, 'h5vp_overlay_type', '0', true) ? 'text' : 'logo',
                            "opacity" => $this->get_post_meta($id, 'overlay_opacity', 0.7),
                        ]]
                    ],
                    "endScreen" => [
                        "enabled" => $this->get_post_meta($id, 'h5vp_endscreen_enable', false, true),
                        "text" => $this->get_post_meta($id, 'h5vp_endscreen_text', 'End Screen Text'),
                        "btnText" => $this->get_post_meta($id, 'endscreen_btn_text', 'Visit'),
                        "btnLink" => $this->get_post_meta($id, 'endscreen_btn_link', ''),
                        "btnStyle" => []
                    ],
                    "thumbInPause" => [
                        "enabled" => $this->get_post_meta($id, 'h5vp_reset_on_end_playerio', false, true),
                        "type" => "default"
                    ],
                    "watermark" => [
                        "enabled" => false,
                        "type" => "email",
                        "text" => "",
                        "color" => "#f00"
                    ],
                    "passwordProtected" => [
                        "enabled" => $this->get_post_meta($id, 'h5vp_password_protected', false, true),
                        "errorMessage" => "Password didn't matched",
                        "heading" => $this->get_post_meta($id, 'h5vp_protected_password_text', "Please Entire the password to access the video"),
                        'key' => "propagans_$id",
                        "button" => [
                            "text" => "Access",
                            "color" => "#222",
                            "backgroundColor" => "#ffffffe3"
                        ]
                    ],
                    "sticky" => [
                        "enabled" => $this->get_post_meta($id, 'h5vp_sticky_mode', false, true),
                        "position" => "top_right"
                    ],
                    "playWhenVisible" => false,
                    "disablePause" => $this->get_post_meta($id, 'h5vp_disable_pause', false, true),
                    "hideYoutubeUI" => $this->get_post_meta($id, 'hideYoutubeUI', false, true),
                    "startTime" => $this->get_post_meta($id, 'h5vp_start_time', 0),
                    "hideLoadingPlaceholder" => false
                ],
                'seo' => [
                    'name' => $this->get_post_meta($id, 'h5vp_seo_name', ''),
                    'description' => $this->get_post_meta($id, 'h5vp_seo_description', ''),
                    'duration' => $this->get_post_meta($id, 'h5vp_seo_duration', 0),
                ],
                "qualities" => $this->get_post_meta($id, 'h5vp_quality_playerio'),
                "subtitle" => $this->get_post_meta($id, 'h5vp_subtitle_playerio'),
                "thumbInPause" => false,
                "hideYoutubeUI" =>  $this->get_post_meta($id, 'hideYoutubeUI', false, true),
                "additionalCSS" => "",
                "additionalID" => "",
                "autoplayWhenVisible" => false,
                "styles" => [
                    "plyr_wrapper" => [
                        "width" => $width,
                        "borderRadius" => "0px",
                        "overflow" => "hidden"
                    ]
                ],
                "CSS" => "",
                "ratio" => null,
                "isCDURL" => false,
                "CDURL" => "",
                "posterTime" => 20,
                "brandColor" => "#00B3FF",
                "radius" => [
                    "number" => 0,
                    "unit" => "px"
                ],
                "protected" => false,
                "password" => "",
                "protectedText" => "Please enter password to wath the video",
                "seekTime" => 10,
                "startTime" => 0,
                "preload" => $this->get_post_meta($id, 'h5vp_preload_playerio', 'metadata'),
                "streaming" => false,
                "streamingType" => "hls",
                "captionEnabled" => false,
                "vastTag" => "",
                "saveState" => true
            ],
            "innerBlocks" => [],
            "innerHTML" => "",
            "innerContent" => [],
        ];
    }

    public function video_player_to_gutenberg_block($attrs)
    {

        $quick = h5vp_get_option('h5vp_quick');
        $option = h5vp_get_option('h5vp_option');
        extract($attrs);
        $preload = isset($attrs['preload']) && !empty($attrs['preload']) ? $preload : $quick('h5vp_preload_quick', 'metadata');
        $hideYoutubeUI = $quick('h5vp_hide_youtube_ui', '0') === '1';
        $hideControls = isset($attrs['hideControls']) && $attrs['hideControls'] ? $hideControls === 'true' : $quick('h5vp_auto_hide_control_quick', '1') === '1';
        $reset_on_end = isset($attrs['reset_on_end']) && $attrs['reset_on_end'] ? $reset_on_end === 'true' : $quick('h5vp_reset_on_end_quick', '1') === '1';
        $file = $file ? $file : ($src ? $src : $mp4);
        $block_name = $source == 'library' ? 'video' : $source;
        $muted = isset($attrs['muted']) ? $muted : $quick('h5vp_muted_quick', true);
        $repeat = isset($attrs['repeat']) && !empty($attrs['repeat']) ? $repeat : $quick('h5vp_repeat_quick', 'none');
        $width = isset($attrs['width']) && !empty($attrs['width']) ? $width : $quick('h5vp_player_width_quick') . 'px';

        $code_controls = isset($attrs['controls']) ? explode(',', $controls) : null;
        $final_controls = [];

        if (is_array($code_controls)) {
            foreach ($code_controls as $control) {
                array_push($final_controls, trim($control));
            }
        }

        $controls = $final_controls ? $final_controls : Functions::getOptionDeep('h5vp_quick', 'controls', ['play-large', 'play', 'progress', 'duration', 'current-time', 'mute', 'volume', 'settings', 'fullscreen']);

        return [
            'blockName' => "html5-player/$block_name",
            'attrs' => [
                "provider" => $source,
                "uniqueId" => wp_unique_id('h5vp'),
                "source" => $file,
                "poster" => $poster,
                "options" => [
                    "controls" => $controls,
                    "settings" => ["captions", "quality", "speed", "loop"],
                    "loadSprite" => true,
                    "autoplay" => $autoplay === 'true' ? true : false,
                    "playsinline" => true,
                    "seekTime" => (int) $quick('h5vp_seek_time_quick', 10),
                    "volume" => 1,
                    "muted" => (bool) $autoplay ? true : ($muted === 'true' ? true : false),
                    "hideControls" => $hideControls,
                    "resetOnEnd" =>  $reset_on_end,
                    "tooltips" => [
                        "controls" => true,
                        "seek" => true
                    ],
                    "captions" => [
                        "active" => false,
                        "language" => "auto",
                        "update" => true
                    ],
                    "ratio" => "16:9", // here is the isssue
                    "storage" => [
                        "enabled" => true,
                        "key" => "plyr"
                    ],
                    "speed" => [
                        "options" => explode(',', $option('h5vp_speed', '0.5, 0.75, 1, 1.25, 1.5, 1.75, 2, 4'))
                    ],
                    "loop" => [
                        "active" => $repeat === 'true' ? true : false,
                    ],
                    "ads" => [
                        "enabled" => false,
                        "tagUrl" => '',
                    ],
                    "preload" => $preload,
                    'markers' => [
                        'enabled' => false,
                        'points' => []
                    ],
                    'urls' => [
                        'enabled' => false,
                    ],
                ],
                "features" => [
                    "popup" => [
                        "enabled" => false,
                    ],
                    "overlay" => [
                        "enabled" => false,
                        "items" => []
                    ],
                    "endScreen" => [
                        "enabled" => false
                    ],
                    "thumbInPause" => [
                        "enabled" => false,
                        "type" => "default"
                    ],
                    "watermark" => [
                        "enabled" => false,
                        "type" => "email",
                        "text" => "",
                        "color" => "#f00"
                    ],
                    "passwordProtected" => [
                        "enabled" => false,
                    ],
                    "sticky" => [
                        "enabled" => false,
                        "position" => "top_right"
                    ],
                    "playWhenVisible" => false,
                    "disablePause" => false,
                    "hideYoutubeUI" => $hideYoutubeUI,
                    "startTime" => false,
                    "hideLoadingPlaceholder" => false
                ],

                "hideYoutubeUI" =>  $hideYoutubeUI,
                "additionalCSS" => "",
                "additionalID" => "",
                "autoplayWhenVisible" => false,
                "styles" => [
                    "plyr_wrapper" => [
                        'width' => $width ? $width : '100%',
                        "borderRadius" => "0px",
                        "overflow" => "hidden"
                    ]
                ],

            ],

            "innerBlocks" => [],
            "innerHTML" => "",
            "innerContent" => [],
        ];
    }

    function get_post_meta($id, $key, $default = null, $is_boolean = false)
    {
        $meta = get_post_meta($id, $key, true);

        if ($is_boolean) {
            $meta = $meta == '1' ? true : false;
        }
        if ($meta == '') {
            $meta = $default;
        }
        return $meta;
    }
}
