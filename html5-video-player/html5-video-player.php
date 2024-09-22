<?php

/*
 * Plugin Name: Html5 Video Player
 * Plugin URI:  https://bplugins.com/html5-video-player-pro/
 * Description: You can easily integrate html5 Video player in your WordPress website using this plugin.
 * Version:     2.5.35
 * Author:      bPlugins LLC
 * Author URI:  http://bplugins.com
 * License:     GPLv3    
 * 
 */
use H5VP\Helper\Functions as Utils;
if ( function_exists( 'h5vp_fs' ) ) {
    h5vp_fs()->set_basename( false, __FILE__ );
} else {
    if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
        require_once dirname( __FILE__ ) . '/vendor/autoload.php';
    }
    if ( file_exists( dirname( __FILE__ ) . '/admin/awsmanager/vendor/autoload.php' ) ) {
        require_once dirname( __FILE__ ) . '/admin/awsmanager/vendor/autoload.php';
    }
    /*Some Set-up*/
    define( 'H5VP_PRO_PLUGIN_DIR', plugin_dir_url( __FILE__ ) );
    define( 'H5VP_PRO_PLUGIN_FILE_BASENAME', plugin_basename( __FILE__ ) );
    define( 'H5VP_PRO_PLUGIN_DIR_BASENAME', plugin_basename( __DIR__ ) );
    define( 'H5VP_PRO_VER', ( isset( $_SERVER['HTTP_HOST'] ) && $_SERVER['HTTP_HOST'] === 'localhost' ? time() : '2.5.33' ) );
    // Create a helper function for easy SDK access.
    function h5vp_fs() {
        global $h5vp_fs;
        if ( !isset( $h5vp_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $h5vp_fs = fs_dynamic_init( array(
                'id'              => '14259',
                'slug'            => 'html5-video-player',
                'premium_slug'    => 'html5-video-player-pro',
                'type'            => 'plugin',
                'public_key'      => 'pk_42a72d9cdea87e78854f59cdc1293',
                'is_premium'      => false,
                'premium_suffix'  => 'Pro',
                'has_addons'      => false,
                'has_paid_plans'  => true,
                'trial'           => array(
                    'days'               => 7,
                    'is_require_payment' => true,
                ),
                'has_affiliation' => 'selected',
                'menu'            => array(
                    'slug'    => 'edit.php?post_type=videoplayer',
                    'support' => false,
                ),
                'is_live'         => true,
            ) );
        }
        return $h5vp_fs;
    }

    h5vp_fs();
    do_action( 'h5vp_fs_loaded' );
    add_action( 'plugins_loaded', function () {
        if ( class_exists( 'H5VP\\Init' ) ) {
            H5VP\Init::register_services();
        }
    } );
    function h5vp_get_meta_preset(  $key, $default  ) {
        $options = get_option( 'h5vp_option', null );
        if ( isset( $options[$key] ) && $options[$key] != '' ) {
            return $options[$key];
        } else {
            return $default;
        }
    }

    /*-------------------------------------------------------------------------------*/
    /* TinyMce
       /*-------------------------------------------------------------------------------*/
    require_once 'tinymce/h5vp-tinymce.php';
    // Latest Code
    if ( !class_exists( 'H5VP_Main' ) ) {
        class H5VP_Main {
            public $baseName = null;

            function __construct() {
                $this->baseName = plugin_basename( __FILE__ );
                add_action( 'wp_ajax_nopriv_pipe_handler', [$this, 'pipe_handler'] );
                add_action( 'wp_ajax_pipe_handler', [$this, 'pipe_handler'] );
            }

            function pipe_handler() {
                if ( !wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'wp_ajax' ) ) {
                    wp_send_json_success( false );
                }
                wp_send_json_success( h5vp_fs()->can_use_premium_code() );
            }

        }

        new H5VP_Main();
    }
    require_once __DIR__ . '/upgrade.php';
}
add_filter(
    'save_post',
    'filter_post_data',
    '99',
    2
);
function filter_post_data(  $post_id, $postarr  ) {
    $post_type = get_post_type( $post_id );
    if ( $post_type === 'videoplayer' ) {
        $password = get_post_meta( $post_id, 'h5vp_protected_password', true );
        update_option( "propagans_{$post_id}", [
            'pass'    => md5( $password ),
            'quality' => Utils::sanitize_array( get_post_meta( $post_id, 'h5vp_quality_playerio', true ) ),
            'source'  => esc_url( get_post_meta( $post_id, 'h5vp_video_link', true ) ),
        ] );
    }
}
