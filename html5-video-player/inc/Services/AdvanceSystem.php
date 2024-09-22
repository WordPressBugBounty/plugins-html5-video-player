<?php
namespace H5VP\Services;
use H5VP\Model\Block;
use H5VP\Helper\Functions as Utils;
use H5VP\Helper\DefaultArgs;

class AdvanceSystem{

    public static function html($id){
        $blocks =  Block::getBlock($id);
        $output = '';
        if(is_array($blocks)){
            foreach($blocks as $block){
                if(isset($block['attrs'])){
                    $output .= render_block($block);
                }
            }
        }
        return $output;
    }

    public static function getData($block){
        
        $options = [
            'controls' => self::parseControls(self::i($block, 'controls')),
            'tooltips' => [
                'controls' => true,
                'seek' => true,
            ],
            'seekTime' => (int)self::i($block, 'seekTime','', 10),
            'loop' => [
                'active' => (boolean) self::i($block, 'repeat') 
            ],
            'autoplay' => (boolean) self::i($block, 'autoplay'),
            'muted' => (boolean) self::i($block, 'muted'),
            'hideControls' => self::i($block, 'autoHideControl', '', true),
            'resetOnEnd' => (boolean) self::i($block, 'resetOnEnd', '', true),
            'captions' => [
                'active' => true,
                'language' => 'auto',
                'update' => false,
            ],
            'ratio' => self::i($block, 'ratio', '', null),
            'speed' => ['selected' => 1, 'options' => self::parseSpeed(self::i($block, 'speed'))],
            'urls' => [
                'download' => self::i($block, 'isCDURL') ? self::i($block, 'CDURL') : null,
            ],
            'disablePause' => (boolean) self::i($block, 'disablePause'),
        ];

        if(self::i($block, 'vastTag')){
            $options['ads'] = [
                'enabled' => true,
                'tagUrl' => self::i($block, 'vastTag')
            ];
        }
        // { enabled: false, publisherId: '', tagUrl: '' }

        $infos = [
            'id' => 0,
            'autoplayWhenVisible' => self::i($block, 'autoplayWhenVisible', '', false),
            'startTime' => self::i($block, 'startTime', '', 0),
            'popup' => (boolean) self::i($block, 'popup'),
            'popupBtnExists' => (boolean) self::i($block, 'popupBtnExists'),
            'popupBtnClass' => self::i($block, 'popupBtnClass'),
            'sticky' => (boolean) self::i($block, 'sticky'),
            'stickyPosition' => self::i($block, 'stickyPosition', '', 'top-right'),
            'protected' => self::i($block, 'protected', '', false),
            'video' => [
                'source' => self::i($block, 'source'),
                'poster' => self::i($block, 'poster'),
                'quality' => self::i($block, 'quality'),
                'subtitle' => self::i($block, 'subtitle'),
            ],
            'provider' => self::i($block, 'provider', '', 'self-hosted'),
            'branding' => (boolean) self::i($block, 'overlay'),

            'endscreen' => (boolean) self::i($block, 'endscreen'),
            'endScreen' => self::i($block, 'endScreen', '', ['btnText' => 'Watch Again']),
            'endscreen_text' => self::i($block, 'endscreenText'),
            'endscreen_text_link' => self::i($block, 'endscreenTextLink'),

            'streaming' => (boolean) self::i($block, 'streaming'),
            'streamingType' => self::i($block, 'streamingType', '', 'hls'),
            'nothing' => 'false',
            'disableDownload' => false,
            'disablePause' => (boolean) self::i($block, 'disablePause'),
            'thumbInPause' => (boolean) self::i($block, 'thumbInPause'),
            'thumbStyle' => self::i($block, 'thumbStyle', 'default'),
            'propagans' => self::i($block, 'password'),
            'chapters' => self::i($block, 'chapters'),
            'posterTime' => self::i($block, 'posterTime'),
            'isCDURL' => self::i($block, 'isCDURL'),
            'CDURL' => self::i($block, 'CDURL'),
            'watermark' => self::i($block, 'watermark', '', []),
            'captionEnabled' => (boolean)self::i($block, 'captionEnabled', '', false),
            'saveState' => (boolean)self::i($block, 'saveState', '', true),

        ];

        $template = array(
            'class' => 'h5vp_player_initializer',
            'branding' => (boolean) self::i($block, 'overlay'),
            'branding_type' => self::i($block, 'overlayType') === true ? 'text' : 'logo',
            'branding_logo' => self::i($block, 'overlayLogo'),
            'branding_text' => self::i($block, 'overlayText'),
            'branding_background' => self::i($block, 'overlayBackground'),
            'branding_opacity' => self::i($block, 'overlayOpacity'),
            'branding_font_size' => self::i($block, 'overlayFontSize', 'number', '20').self::i($block, 'overlayFontSize', 'unit', 'px'),
            'branding_link' => self::i($block, 'overlayLink', '', '#'),
            'branding_color' => self::i($block, 'overlayTextColor', '', '#ffffff'),
            'branding_hover_color' => self::i($block, 'overlayTextHoverColor', '', '#ffffff'),
            'branding_position' => false,
            'branding_position_first_type' => self::i($block, 'ODFFT', '', 'top'),
            'branding_position_first' => self::i($block, 'ODFF', 'number', 10).self::i($block, 'ODFF', 'unit', 'px'),
            'branding_position_second_type' => self::i($block, 'ODFST', '', 'left'),
            'branding_position_second' => self::i($block, 'ODFS', 'number', '10').self::i($block, 'ODFS', 'unit', 'px'),
            //endscreen
            'endscreen' => (boolean) self::i($block, 'endscreen', false),
            'endscreen_text' => self::i($block, 'endscreenText', 'Endscreen Text'),
            'endscreen_btn_text' => self::i($block, 'endScreen', 'Endscreen Text'),
            'endscreen_text_link' => self::i($block, 'endscreenTextLink', '#'),
            'endscreen_text_color' => '',
            'endscreen_text_color_hover' => '',

            'popup' => (boolean) self::i($block, 'popup'),
            'popupType' => self::i($block, 'popupType', '', 'poster'),
            'popupBtnStyle' => self::i($block, 'popupBtnStyle', '', []),
            'popupBtnText' => self::i($block, 'popupBtnText', '', 'Watch Video'),
            'popupBtnPadding' => self::i($block, 'popupBtnPadding', '', []),
            'popupBtnAlign' => self::i($block, 'popupBtnAlign', '', 'center'),
            'preload' => self::i($block, 'preload', '', 'metadata'),
            'streaming' => false,
            'streamingType' => self::i($block, 'streamingType', 'hls'),
            'protected' => self::i($block, 'protected', '', false),
            'hideYoutubeUI' => self::i($block, 'hideYoutubeUI', '', false),
            'additionalID' => self::i($block, 'additionalID', '', 'false'),
            'additionalCSS' => self::i($block, 'additionalCSS', '', false),
            'protected_text' => self::i($block, 'protectedText', '', 'Please enter password to wath the video'),
            'subtitles' => [],
            'width' => self::i($block, 'width', 'number', '', 100).self::i($block, 'width', 'unit', '', '%'),
            'round' => self::i($block, 'radius', 'number', '', 100).self::i($block, 'radius', 'unit', '', '%'),
            'controlsShadow' => true,
            'playsinline' => (boolean) self::i($block, 'playsinline'),
            
        );

        $result = [
            'options' => $options,
            'infos' => $infos,
            'template' => $template,
            'uniqueId' => self::i($block, 'uniqueId', '', 'LSDK'),
            'CSS' => self::i($block, 'CSS', '', ''),
            'styles' => [
                'plyr--video' => [
                    '--plyr-color-main' => DefaultArgs::brandColor()
                ],
            ]
        ];

        return $result;
    }

    public static function i($array, $key1, $key2 = '', $default = false){
        if(isset($array[$key1][$key2])){
            return $array[$key1][$key2];
        }else if (isset($array[$key1])){
            return $array[$key1];
        }
        return $default;
    }

    public static function parseControls($controls){
        $newControls = [];
        if(!is_array($controls)){
            return ['play-large','rewind', 'play', 'fast-forward', 'progress', 'current-time', 'mute', 'volume', 'captions', 'settings', 'fullscreen'];
        }
        foreach($controls as $key => $value){
            if($key === 'settings'){
                array_push($newControls, 'captions');
            }
            if($value == 1){
                array_push($newControls, $key);
            }
        }
        
        return $newControls;
    }

    public static function parseSpeed($speed){
        $newSpeed = [];
        if(!is_array($speed)){
            return [0.5,0.75, 1, 1.25, 1.5,1.75, 2, 2.5, 3];
        }
        foreach($speed as $key => $value){
            if($value){
                array_push($newSpeed, $key);
            }
        }
        
        sort($newSpeed);
        return $newSpeed;
    }

    
}