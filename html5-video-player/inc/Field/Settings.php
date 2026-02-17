<?php

namespace H5VP\Field;

if (! defined('ABSPATH')) exit; // Exit if accessed directly

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
                'framework_title' => 'HTML5 Video Player Preset',
                'show_bar_menu' => false,
                // 'menu_capability' => 'edit_posts'
            ));

            $this->shortcode();
        }
    }

    public function shortcode()
    {
        \CSF::createSection($this->prefix, [
            'title' => __("Shortcode/Player", "h5vp"),
            'fields' => [
                [
                    'id' => 'h5vp_gutenberg_enable',
                    'title' => 'Enable Gutenberg Shortcode Generator',
                    'type' => 'switcher',
                    'desc' => __("When enabled, the Gutenberg editor will enable to generate shortcode", "h5vp"),
                    'default' => get_option('nothdddding', true)
                ],
                [
                    'id' => 'h5vp_disable_video_shortcode',
                    'title' => __("Disable [video id='id'] shortcode for this plugin", "h5vp"),
                    'type' => 'switcher',
                    'desc' => __("When enabled, the [video] shortcode will be disabled for this plugin to avoid conflicts.", "h5vp"),
                    'default' => false,
                ],
                [
                    'id' => 'h5vp_pause_other_player',
                    'type' => 'switcher',
                    'title' => __('Play one player at a time', 'h5vp'),
                    'desc' => __("When enabled, starting playback on one video player will automatically pause any other playing video players on the same page.", "h5vp"),
                    'default' => false,
                ],
                [
                    'id' => 'h5vp_player_primary_color',
                    'type' => 'color',
                    'title' => __('Brand Color', 'h5vp'),
                    'desc' => __("Set the primary color used for the video player interface.", "h5vp"),
                    'default' => '#00b2ff',
                ],
            ]
        ]);
    }
}
