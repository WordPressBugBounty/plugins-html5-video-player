<?php

namespace H5VP\Field;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Settings
{
    private $prefix = 'h5vp_option';
    public function register()
    {
        add_action('init', [$this, 'register_fields'], 0);
    }

    public function register_fields()
    {
        if (class_exists('\CSF')) {
            global $h5vp_bs;

            // Create options
            \CSF::createOptions($this->prefix, array(
                'menu_title' => 'Settings',
                'menu_slug' => 'html5vp_settings',
                'menu_parent' => 'edit.php?post_type=videoplayer',
                'menu_type' => 'submenu',
                'theme' => 'light',
                'data_type' => 'unserialize',
                'show_all_options' => false,
                'save_defaults' => true,
                'framework_class' => 'h5vp_options',
                'framework_title' => 'Settings',
                'show_bar_menu' => false,
                // 'menu_capability' => 'edit_posts'
            ));

            $this->shortcode();
        }
    }

    public function shortcode()
    {
        \CSF::createSection($this->prefix, [
            'title' => __("Shortcode/Player", "html5-video-player"),
            'fields' => [
                [
                    'id' => 'h5vp_gutenberg_enable',
                    'title' => 'Enable Gutenberg Shortcode Generator',
                    'type' => 'switcher',
                    'desc' => __("When enabled, the Gutenberg editor will enable to generate shortcode", "html5-video-player"),
                    'default' => true
                ],
                [
                    'id' => 'h5vp_disable_video_shortcode',
                    'title' => __("Disable [video id='id'] shortcode for this plugin", "html5-video-player"),
                    'type' => 'switcher',
                    'desc' => __("When enabled, the [video] shortcode will be disabled for this plugin to avoid conflicts.", "html5-video-player"),
                    'default' => false,
                ],
                [
                    'id' => 'h5vp_pause_other_player',
                    'type' => 'switcher',
                    'title' => __('Play one player at a time', 'html5-video-player'),
                    'desc' => __("When enabled, starting playback on one video player will automatically pause any other playing video players on the same page.", "html5-video-player"),
                    'default' => false,
                ],
                [
                    'id' => 'h5vp_player_primary_color',
                    'type' => 'color',
                    'title' => __('Brand Color', 'html5-video-player'),
                    'desc' => __("Set the primary color used for the video player interface.", "html5-video-player"),
                    'default' => '#00b2ff',
                ],
                // delete data during uninstall
                [
                    'id' => 'h5vp_delete_data_during_uninstall',
                    'type' => 'switcher',
                    'title' => __('Delete data during uninstall', 'html5-video-player'),
                    'desc' => __("When enabled, the plugin will delete all its data when uninstalled.", "html5-video-player"),
                    'default' => false,
                ],
            ]
        ]);
    }
}
