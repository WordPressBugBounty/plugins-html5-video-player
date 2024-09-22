<?php
namespace H5VP\Field;

class Settings{
    private $prefix = 'h5vp_option';
    public function register(){
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

    public function shortcode(){
        \CSF::createSection($this->prefix, [
            'title' => __("Shortcode/Player", "h5vp"),
            'fields' => [
                [
                    'id' => 'h5vp_gutenberg_enable',
                    'title' => 'Enable Gutenberg Shortcode Generator',
                    'type' => 'switcher',
                    'default' => get_option('nothdddding', true)
                ],
                [
                    'id' => 'h5vp_disable_video_shortcode',
                    'title' => __("Disable [video id='id'] shortcode for this plugin"),
                    'type' => 'switcher',
                    'default' => false,
                ],
                [
                    'id' => 'h5vp_pause_other_player',
                    'type' => 'switcher',
                    'title' => esc_html__('Play one player at a time', 'h5vp'),
                    'default' => false,
                ],
            ]
        ]);
    }



}