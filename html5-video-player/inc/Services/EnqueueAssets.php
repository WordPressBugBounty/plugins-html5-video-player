<?php
namespace H5VP\Services;

use H5VP\Helper\Functions;
use H5VP\Helper\LocalizeScript;

class EnqueueAssets {
    protected static $_instance = null;

    public function __construct(){
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('wp_head', [$this, 'quickPlayerStyle']);
        add_action('admin_head', [$this, 'adminHead']);
    }

    public static function instance(){
        if(self::$_instance === null){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function enqueueAssets(){
         //plyrio
        wp_register_script('bplugins-plyrio', plugin_dir_url( __FILE__ ). 'public/js/plyr-v3.7.8.js' , array(), H5VP_PRO_VER, false );
        wp_register_script('html5-player-video-view-script', H5VP_PRO_PLUGIN_DIR . 'dist/frontend.js', array('jquery', 'bplugins-plyrio', 'react', 'react-dom', 'wp-util'), H5VP_PRO_VER, false);

        wp_register_script('html5-player-playlist', H5VP_PRO_PLUGIN_DIR . 'dist/frontend-playlist.js', array('react', 'react-dom', 'wp-util', 'bplugins-plyrio'), H5VP_PRO_VER, false);

        wp_register_style('bplugins-plyrio', H5VP_PRO_PLUGIN_DIR . 'public/css/h5vp.css', array(), H5VP_PRO_VER, 'all');
        wp_register_style('html5-player-video-style', H5VP_PRO_PLUGIN_DIR . 'dist/frontend.css', array('bplugins-plyrio'), H5VP_PRO_VER, 'all');
        wp_register_style('html5-player-playlist', H5VP_PRO_PLUGIN_DIR . 'dist/frontend-playlist.css', array('bplugins-plyrio'), H5VP_PRO_VER, 'all');

        //owl-carousel
        wp_register_script('bplugins-owl-carousel', H5VP_PRO_PLUGIN_DIR . 'public/js/owl.carousel.min.js', null, H5VP_PRO_VER, false);
        wp_register_style('bplugins-owl-carousel', H5VP_PRO_PLUGIN_DIR . 'public/css/owl.carousel.min.css', null, H5VP_PRO_VER, 'all');

        wp_localize_script('html5-player-video-view-script', 'hpublic', array(
            'siteUrl' => site_url(),
            'userId' => get_current_user_id(),
            'pauseOther' => (boolean) Functions::getOptionDeep("h5vp_option", "h5vp_pause_other_player", false),
            'speed' => Functions::getOptionDeep("h5vp_option", "h5vp_speed", false),
            'dir' => H5VP_PRO_PLUGIN_DIR ,
        ));

        
        //localize quick player settings
        wp_localize_script( 'html5-player-video-view-script', 'h5vpData', LocalizeScript::quickPlayer());

        //Localize H5VP_Video Translated Word
        wp_localize_script('html5-player-video-view-script', 'h5vpI18n', LocalizeScript::translatedText());
    }

    public function enqueueAdminAssets($screen){
        global $post;

        if ((!empty($post) && 'videoplayer' == $post->post_type || $screen == 'edit.php') || (!empty($post) && 'h5vpplaylist' == $post->post_type) || $screen == 'videoplayer_page_h5vp-support' || $screen == 'videoplayer_page_html5vp_settings' || $screen == 'videoplayer_page_html5vp_quick_player' || $screen == 'videoplayer_page_free-plugins-from-bplugins' || $screen == 'videoplayer_page_premium-plugins' || $screen == 'plugins.php' || $screen = 'videoplayer_page_analytics') {

            wp_enqueue_script('h5vp-chart', 'https://cdn.jsdelivr.net/npm/chart.js', array('jquery'), H5VP_PRO_VER, false);
            wp_enqueue_script('h5vp-admin', H5VP_PRO_PLUGIN_DIR . 'dist/admin.js', array('jquery', 'react', 'react-dom'), H5VP_PRO_VER, true);
            wp_enqueue_style('h5vp-admin', H5VP_PRO_PLUGIN_DIR . 'dist/admin.css', array(), H5VP_PRO_VER);

            wp_localize_script('h5vp-admin', 'h5vpAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'website' => site_url(),
                'email' => get_option('admin_email'),
                'isPipe' => h5vp_fs()->can_use_premium_code(),
                'nonce' => wp_create_nonce('wp_ajax')
            ));
        }

        if($screen == 'videoplayer_page_free-plugins-from-bplugins'){
            wp_enqueue_script('plugin-install');
            wp_enqueue_script('updates');
        }
    }

    public function quickPlayerStyle(){
        $width = Functions::getOptionDeep('h5vp_quick', 'h5vp_player_width_quick', 0);
        $shadow = Functions::getOptionDeep('h5vp_quick', 'h5vp_hide_control_shadow_quick', 'show');
        $width = $width == 0 ? '100%' : $width."px";
        ob_start();
        ?>
        <style>
            #h5vpQuickPlayer {
                width: <?php echo esc_html($width); ?>;
                max-width: 100%;
                margin: 0 auto;
            }
            <?php if($shadow === 'hide'){
                echo '#h5vpQuickPlayer .plyr__controls {background: none;}';
            } ?>
            <?php if($shadow === 'mobile'){ ?>
                @media screen and (max-width: 640px){
                    #h5vpQuickPlayer .plyr__controls {background: none;}
                }
                <?php 
            } ?>
            <?php echo Functions::getOptionDeep('h5vp_option', 'h5vp_custom_style', '') ?>
        </style>
        <?php

        echo Functions::trim(ob_get_clean());
    }

    public function adminHead(){

    }
}
