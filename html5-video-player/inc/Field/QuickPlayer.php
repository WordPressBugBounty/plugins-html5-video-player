<?php
namespace H5VP\Field;

class QuickPlayer{
    private $prefix = 'h5vp_quick';
    public function register(){

        if (class_exists('\CSF')) {
            // wp_enqueue_style('h5vp-admin', H5VP_PRO_PLUGIN_DIR . 'dist/admin.css', array(), H5VP_PRO_VER);

             // Create options
             \CSF::createOptions($this->prefix, array(
                'menu_title' => 'Quick Player',
                'menu_slug' => 'html5vp_quick_player',
                'menu_parent' => 'edit.php?post_type=videoplayer',
                'menu_type' => 'submenu',
                'theme' => 'light',
                'data_type' => 'unserialize',
                'show_all_options' => false,
                'save_defaults' => true,
                'framework_class' => 'h5vp_quick_player',
                'framework_title' => 'HTML5 Quick Video Player Preset',
                'show_bar_menu' => false,
                // 'menu_capability' => 'edit_posts'
            ));

            $this->quickPlayer();
        }
    }

    public function quickPlayer(){
        \CSF::createSection($this->prefix, array(
            'title' => 'Quick Player',
            'fields' => array(
                array(
                    'title' => 'Shortcode',
                    'type' => 'content',
                    'content' => '<code>[video_player file=yourvideo poster=poster.jpeg source=library]</code> <p>Use this shortcode to show video in your website quickly. change the source to youtube/vimeo to show youtube/vimeo video</p>',
                ),
                array(
                    'id' => 'h5vp_all_video_quick',
                    'title' => 'Apply Html5 Video Player for all previous videos',
                    'desc' => 'Normal Shortcode/Elementor/Gutenberg HTML5 Video Player will not work if you enable this option',
                    'type' => 'switcher',
                    'text_off' => 'No',
                    'class' => 'bplugins-meta-readonly',
                    'text_on' => 'Yes'
                ),
                
                array(
                    'id' => 'h5vp_reset_on_end_quick',
                    'title' => 'Reset On End',
                    'type' => 'switcher',
                    'default' => '1',
                ),
                array(
                    'id' => 'h5vp_player_width_quick',
                    'title' => 'Player Width',
                    'type' => 'spinner',
                    'unit' => 'px',
                    'step' => '50',
                    'max' => '5000',
                    'desc' => 'set the player width. Height will be calculate base on the value. Left blank for Responsive player',
                ),
                array(
                    'id' => 'h5vp_seek_time_quick',
                    'title' => 'Seek Time',
                    'type' => 'number',
                    'desc' => 'The time, in seconds, to seek when a user hits fast forward or rewind. Default value is 10 Sec.',
                    'default' => '10',
                ),
                array(
                    'id' => 'h5vp_auto_hide_control_quick',
                    'title' => 'Auto Hide Control',
                    'type' => 'switcher',
                    'desc' => 'On if you want the controls (such as a play/pause button etc) hide automaticaly.',
                    'default' => true
                ),
                array(
                    'id' => 'h5vp_preload_quick',
                    'title' => 'Preload',
                    'type' => 'radio',
                    'class' => 'bplugins-meta-readonly',
                    'options' => array(
                        'auto' => 'Auto - Browser should load the entire file when the page loads.',
                        'metadata' => 'Metadata - Browser should load only meatadata when the page loads.',
                        'none' => 'None - Browser should NOT load the file when the page loads.',
                    ),
                    'desc' => 'Specify how the video file should be loaded when the page loads.',
                    'default' => 'metadata',
                ),
                array(
                    'id' => 'controls',
                    'type' => 'button_set',
                    'class' => 'bplugins-meta-readonly',
                    'title' => 'Control buttons and Components',
                    'multiple' => true,
                    'options' => array(
                      'play-large' => 'Play Large',
                      'restart' => 'Restart',
                      'rewind' => 'Rewind',
                      'play' => 'Play',
                      'fast-forward' => 'Fast Forwards',
                      'progress' => 'Progressbar',
                      'duration' => 'Duration',
                      'current-time' => 'Current Time',
                      'mute' => 'Mute Button',
                      'volume' => 'Volume Control',
                      'settings' => 'Setting Button',
                      'pip' => 'PIP',
                      'airplay' => 'Airplay',
                      'download' => 'Download Button',
                      'fullscreen' => 'Fullscreen',
                    ) ,
                    'default' => array(
                      'play-large',
                      'play',
                      'progress',
                      'duration',
                      'current-time',
                      'mute',
                      'volume',
                      'settings',
                      'fullscreen'
                    ) ,
                    'help' => 'Click on the item to turn ON/OFF',
                ),
                array(
                    'id' => 'h5vp_hide_youtube_ui',
                    'title' => 'Hide Youtube UI (Experimental, check it\'s working or not for you)',
                    'type' => 'switcher',
                    'class' => 'bplugins-meta-readonly',
                    'default' => 0
                ),
            ),
        ));
    }
}