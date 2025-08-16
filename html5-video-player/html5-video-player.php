<?php

/*
 * Plugin Name: Html5 Video Player
 * Plugin URI:  https://bplugins.com/html5-video-player-pro/
 * Description: You can easily integrate html5 Video player in your WordPress website using this plugin.
 * Version:     2.7.1
 * Author:      bPlugins
 * Author URI:  http://bplugins.com
 * License:     GPLv3    
 * Text Domain: h5vp
 * 
 */
if ( function_exists( 'h5vp_fs' ) ) {
    h5vp_fs()->set_basename( false, __FILE__ );
} else {
    if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
        require_once dirname( __FILE__ ) . '/vendor/autoload.php';
    }
    if ( file_exists( dirname( __FILE__ ) . '/inc/admin.php' ) ) {
        require_once dirname( __FILE__ ) . '/inc/admin.php';
    }
    /*Some Set-up*/
    define( 'H5VP_PRO_PLUGIN_DIR', plugin_dir_url( __FILE__ ) );
    define( 'H5VP_PRO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
    define( 'H5VP_PRO_PLUGIN_FILE_BASENAME', plugin_basename( __FILE__ ) );
    define( 'H5VP_PRO_PLUGIN_DIR_BASENAME', plugin_basename( __DIR__ ) );
    define( 'H5VP_PRO_VER', ( isset( $_SERVER['HTTP_HOST'] ) && $_SERVER['HTTP_HOST'] === 'localhost' ? time() : '2.7.1' ) );
    // Create a helper function for easy SDK access.
    function h5vp_fs() {
        global $h5vp_fs;
        if ( !isset( $h5vp_fs ) ) {
            // Include Freemius SDK.
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
                    'slug'       => 'html5-video-player',
                    'support'    => false,
                    'first-path' => 'admin.php?page=choose-preferred-editor',
                ),
                'is_live'         => true,
            ) );
        }
        return $h5vp_fs;
    }

    h5vp_fs();
    do_action( 'h5vp_fs_loaded' );
    // if (file_exists(dirname(__FILE__) . '/admin/awsmanager/vendor/autoload.php') && version_compare(PHP_VERSION, '8.1', '>=')) {
    //     require_once(dirname(__FILE__) . '/admin/awsmanager/vendor/autoload.php');
    // }
    require_once __DIR__ . '/includes.php';
    add_action( 'plugins_loaded', function () {
        if ( class_exists( 'H5VP\\Init' ) ) {
            H5VP\Init::register_post_type();
        }
    } );
    if ( class_exists( 'H5VP\\Init' ) ) {
        H5VP\Init::register_services();
    }
    // add_action('init', function () {
    // });
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
}
add_action( 'wp_footer', function () {
    ?>

    <style>
        /* .plyr__progress input[type=range]::-ms-scrollbar-track {
            box-shadow: none !important;
        }

        .plyr__progress input[type=range]::-webkit-scrollbar-track {
            box-shadow: none !important;
        } */

        .plyr {
            input[type=range]::-webkit-slider-runnable-track {
                box-shadow: none;
            }

            input[type=range]::-moz-range-track {
                box-shadow: none;
            }

            input[type=range]::-ms-track {
                box-shadow: none;
            }
        }
    </style>
<?php 
} );