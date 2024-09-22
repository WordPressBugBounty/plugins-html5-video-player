<?php
namespace H5VP\Block;
if(!defined('ABSPATH')) {
    return;
}
// require_once(__DIR__.'/inc/Helper/DefaultArgs.php');
// require_once(__DIR__.'/inc/Services/AdvanceSystem.php');
// require_once(__DIR__.'/inc/Services/VideoTemplate.php');
use H5VP\Helper\DefaultArgs;
use H5VP\Helper\Functions as Utils;
use H5VP\Services\AdvanceSystem;
use H5VP\Services\VideoTemplate;


if(!class_exists('H5VP_Block')){
    class H5VP_Block{
        function __construct(){
            add_action('init', [$this, 'enqueue_script']);
            add_action('wp_ajax_watermark_data', [$this, 'watermark_data_ajax']);
            add_action('wp_ajax_nopriv_watermark_data', [$this, 'watermark_data_ajax']);
        }

        function enqueue_script(){
            global $h5vp_bs;
            wp_register_script(	'html5-player-blocks', plugin_dir_url( __FILE__ ).'dist/editor.js', array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'jquery','bplugins-plyrio' ), H5VP_PRO_VER, true );

            wp_register_script( 'bplugins-plyrio', plugin_dir_url( __FILE__ ). 'public/js/plyr-v3.7.8.js' , array(), H5VP_PRO_VER, false );

            wp_register_script( 'html5-player-video-view-script', plugin_dir_url( __FILE__ ). 'dist/frontend.js' , array('jquery', 'bplugins-plyrio', 'react', 'react-dom', 'wp-util'), H5VP_PRO_VER, true );
            wp_register_script( 'h5vp-hls', H5VP_PRO_PLUGIN_DIR . 'public/js/hls.min.js' , array( 'bplugins-plyrio'), H5VP_PRO_VER, true );
            wp_register_script( 'h5vp-dash', H5VP_PRO_PLUGIN_DIR . 'public/js/dash.min.js' , array( 'bplugins-plyrio'), H5VP_PRO_VER, true );
            
            wp_register_style( 'bplugins-plyrio', plugin_dir_url( __FILE__ ) . 'public/css/h5vp.css', array(), H5VP_PRO_VER, 'all' );
            wp_register_style( 'h5vp-editor', plugin_dir_url( __FILE__ ) . 'dist/editor.css', array(), H5VP_PRO_VER, 'all' );

            wp_register_style( 'html5-player-video-style', plugin_dir_url( __FILE__ ). 'dist/frontend.css' , array('bplugins-plyrio'), H5VP_PRO_VER );


            $localize_data = [
                'siteUrl' => site_url(),
                'userId' => get_current_user_id(),
                'isPipe' => (boolean) h5vp_fs()->can_use_premium_code(),
                'hls' => H5VP_PRO_PLUGIN_DIR . 'public/js/hls.min.js',
                'dash' => H5VP_PRO_PLUGIN_DIR . 'public/js/dash.min.js',
                'nonce' => wp_create_nonce( 'wp_ajax')
            ];

            wp_localize_script('html5-player-blocks', 'h5vpBlock', $localize_data);

            wp_localize_script('html5-player-video-view-script', 'h5vpBlock', $localize_data);

            register_block_type('html5-player/parent', array(
                'editor_script' => 'html5-player-blocks',
                'editor_style' => 'h5vp-editor',
            ));

            $this->registerBlock('video');
            if(h5vp_fs()->can_use_premium_code()){
                $this->registerBlock('youtube');
                $this->registerBlock('vimeo');
            }
            
        }

        public function render_callback_video($attrs, $content){
            // $provider = 'self-hosted';
            // if(isset($attrs['source'])){
            //     $provider = Functions::getProvider($attrs['source']);
            // }else {
            //     return false; 
            // }
            // $attrs['provider'] = $provider;
            $data = DefaultArgs::parseArgs(AdvanceSystem::getData($attrs));

            // echo '<pre>';
            // print_r( $data );
            // echo '</pre>';

 
            $merge_able_data = [];

            if($attrs['imported']){
                $merge_able_data = $attrs;
                $merge_able_data['options']['markers'] = Utils::parseMarkers($merge_able_data['options']['markers']);
                $merge_able_data['styles'] = wp_parse_args($merge_able_data['styles'], $data['styles']);
            }

            $finalData = wp_parse_args($merge_able_data, [
                'options' => $data['options'],
                'additionalID' => '',
                'features' => [
                    'overlay' => [
                        'enabled' => $data['template']['branding'],
                        'items' => [
                           [
                             'color' => $data['template']['branding_color'],
                            'hoverColor' => $data['template']['branding_hover_color'],
                            'fontSize' => $data['template']['branding_font_size'],
                            'link' => $data['template']['branding_link'],
                            'logo' => $data['template']['branding_logo'],
                            'position' => 'top_right',
                            'text' => $data['template']['branding_text'],
                            'type' => $data['template']['branding_type'],
                            'backgroundColor' => $data['template']['branding_background'],
                            'opacity' => $data['template']['branding_opacity'],
                           ]
                        ]
    
                    ],
                    'sticky' => [
                        'enabled' => $data['infos']['sticky'],
                        'position' => str_replace('-', '_', $data['infos']['stickyPosition']),
                    ],
                    'chapters' => $data['infos']['chapters'],
                    'watermark' => $data['infos']['watermark'],
                    'thumbInPause' => [
                        'enabled' => $data['infos']['thumbInPause'],
                        'type' => $data['infos']['thumbStyle']
                    ],
                    'endScreen' => [
                        'enabled' => $data['infos']['endscreen'],
                        'text' => $data['infos']['endscreen_text'],
                        'btnText' => $data['infos']['endScreen']['btnText'],
                        'btnLink' => $data['infos']['endscreen_text_link']
                    ],
                    'popup' => [
                        'enabled' => $data['infos']['popup'],
                        'selector' => $data['infos']['popupBtnClass'],
                        'hasBtn'=> $data['infos']['popupBtnExists'],
                        'type' => $data['template']['popupType'],
                        'btnText' => $data['template']['popupBtnText'],
                        'btnStyle' => $data['template']['popupBtnStyle'],
                    ],
                    "hideLoadingPlaceholder" => $attrs['features']['hideLoadingPlaceholder'] ?? false ,
                ],
                'data' => [
                    
                ],
                'propagans' => $data['infos']['propagans'],
                'customDownloadURL' => $data['infos']['CDURL'],
                'captionEnabled' => $data['infos']['captionEnabled'],
                'disableDownload' => $data['infos']['disableDownload'],
                'disablePause' => $data['infos']['disablePause'],
                'startTime' => $data['infos']['startTime'],
                'thumbInPause' => $data['infos']['thumbInPause'],
                'thumbStyle' => $data['infos']['thumbStyle'],
                'autoplayWhenVisible' => $data['infos']['autoplayWhenVisible'],
                'saveState' => $data['infos']['saveState'],
                'protected' => $data['infos']['protected'],
                'captions' => $data['infos']['video']['subtitle'],
                'qualities' => $data['infos']['video']['quality'],
                'source' => $data['infos']['video']['source'],
                'poster' => $data['infos']['video']['poster'],
                'hideYoutubeUI' => $data['template']['hideYoutubeUI'],
                
                'branding' => [
                    'enabled' => $data['template']['branding'],
                    'color' => $data['template']['branding_color'] ?? ''
                ],
            ]);  
 

            ob_start();

            echo VideoTemplate::html($finalData);

            return ob_get_clean();
        }

        public function registerBlock($block){
            register_block_type(__DIR__."/blocks/$block", array(
                'editor_script' => 'html5-player-blocks',
                'editor_style' => 'h5vp-editor',
                'render_callback' => [$this, 'render_callback_video']
            ));
        }

        public function render_callback_vidstack($attrs){

            ?>
            <div class="vidstack">video Will go here</div>
            <?php
        }

        public function watermark_data_ajax(){

            if(!wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'wp_ajax')){
                wp_send_json_error('invalid request');
            }

            $user = wp_get_current_user();

            wp_send_json_success([
                'user' => [
                    'email' => $user->data->user_email ?? '',
                    'name' => $user->data->display_name ?? '',
                ]
            ]);
        }

        function getWatermarkPosition($position){

        }


    }



    new H5VP_Block();
}
