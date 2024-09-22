<?php
namespace H5VP\Services;
use H5VP\Helper\Functions;
use H5VP\Helper\DefaultArgs;

class QuickPlayerTemplate{
    
    public static function html($data = []){
        wp_enqueue_script('html5-player-video-view-script');
        wp_enqueue_style('html5-player-video-style');
        extract($data);

        
        $preload = $preload ? $preload : Functions::getOptionDeep('h5vp_quick', 'h5vp_preload_quick', 'metadata');
        $hideYoutubeUI = Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_youtube_ui', false);
        $hideControls = $hideControls ? $hideControls : Functions::getOptionDeep('h5vp_quick', 'h5vp_auto_hide_control_quick', true);
        $file = $file ? $file : ($src ? $src : $mp4);

        $code_controls = $controls ? explode(',', $controls) : null;
        $final_controls = [];
        
        if(is_array($code_controls)){
            foreach($code_controls as $control){
                array_push($final_controls, trim($control));
            }
        }

        $controls = $final_controls ? $final_controls : Functions::getOptionDeep('h5vp_quick', 'controls', ['play-large','play','progress','duration','current-time','mute','volume','settings','fullscreen']);

        $options = [
            'controls' => $controls,
            'autoplay' => $autoplay === 'true' ? true : false,
            'resetOnEnd' =>  $reset_on_end === 'true' ? true : false,
            'repeat' => $repeat === 'true' ? true : false,
            'muted' => (boolean) $autoplay ? true : ($muted === 'true' ? true : false),
            'fullscreen' => [
                'iosNative' => $ios_native === 'true',
            ],
            'preload' => $preload,
            'hideControls' => $hideControls
        ];

        $final_data = [
            'options' => $options,
            'source' => $file,
            'poster' => $poster,
            'styles' => [
                'plyr_wrapper' => [
                    'width' => $width ? $width : '100%',
                ]
            ],
            'hideYoutubeUI' => $hideYoutubeUI,
            'type' => 'quickPlayer'
        ];

        $final_data = DefaultArgs::parseArgs($final_data);


        ob_start();
     
        echo VideoTemplate::html($final_data);


        return ob_get_clean();
    }
}