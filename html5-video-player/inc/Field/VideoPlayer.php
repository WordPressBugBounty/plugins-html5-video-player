<?php 
namespace H5VP\Field;

class VideoPlayer{

    public function register(){
        if (class_exists('\CSF')) {
            $prefix = '_h5vp_';
            \CSF::createMetabox($prefix, array(
                'title' => 'Configure Your Video Player',
                'post_type' => 'videoplayer',
                'data_type' => 'unserialize',
            ));

            $this->configure($prefix);
        }
    }

    public function configure($prefix){
        $id = isset($_GET['post']) ? $_GET['post'] : '';
        // Create a section
        \CSF::createSection($prefix, array(
            'title' => '',
            'id' => 'noting-to-hide',
            'fields' => array(
                array(
                    'type' => 'content',
                    'title' => ' ',
                    'content' => h5vp_get_meta_preset('h5vp_import_export_enable', false) ? '<button class="button button-primary h5vp_export_button" data-id=' . $id . '>Export</button> <button class="button button-primary h5vp_import_button" data-reload="true" data-id="'. $id .'">Import</button>' : '',
                ),
                
                array(
                    'id' => 'h5vp_video_source',
                    'title' => 'Video Source',
                    'type' => 'button_set',
                    'options' => array(
                        'library' => 'Library or CDN source',
                        'youtube' => 'Youtube',
                        'vimeo' => 'Vimeo',
                        'amazons3' => 'AWS S3 File Manager',
                        // 'google' => 'Google Drive',
                    ),
                    'default' => 'library',
                    'class' => 'bplugins-meta-readonly',
                    'dependency' => array('h5vp_video_streaming', '==', '0')
                ),
                array(
                    'id' => 'h5vp_aws_file_picker',
                    'title' => ' ',
                    'type' => 'button_set',
                    'options' => array(
                        'picker' => '<img src="'.H5VP_PRO_PLUGIN_DIR.'./img/aws.png"/> Choose From AWS S3 Storage',
                    ),
                    'default' => 'picker',
                    'class' => 'bplugins-meta-readonly',
                    'dependency' => array(array('h5vp_video_source', '==', 'amazons3'), array('h5vp_video_streaming', '!=', '1')),
                    'attributes' => array('class' => 'aws_video_picker', 'seton' => 'h5vp_video_link_aws'),
                    'class' => 'aws_picker_btn'
                ),
                array(
                    'id' => 'h5vp_video_link',
                    'type' => 'upload',
                    'title' => 'Source URL',
                    'placeholder' => 'https://',
                    'library' => 'video',
                    'button_title' => 'Add Video',
                    'attributes' => array('class' => 'h5vp_video_link', 'id' => 'h5vp_google_document_url'),
                    'desc' => 'select an mp4 or ogg video file. or paste a external video file link. if you use multiple quality. this source/video should be 720',
                    'dependency' =>array(array('h5vp_video_source', 'any', 'library,amazons3,google'), array('h5vp_video_streaming', '!=', '1')),
                ),
                array(
                    'id' => 'h5vp_video_thumbnails',
                    'type' => 'upload',
                    'title' => 'Video Thumbnail',
                    'subtitle' => 'for youtube and vimeo, the thumbnail only a backup. if failed to fetch the default thumbnail, this will show',
                    'library' => 'image',
                    'button_title' => 'Add Image',
                    'placeholder' => 'https://',
                    'attributes' => array('class' => 'h5vp_video_thumbnails'),
                    'desc' => 'specifies an image to be shown while the video is downloading or until the user hits the play button',
                ),
                array(
                    'id' => 'h5vp_controls',
                    'type' => 'button_set',
                    'title' => esc_html__('Controls', 'h5vp'),
                    'multiple' => true,
                    'options' => array(
                      'play-large' => esc_html__('Play Large', 'h5vp'),
                      'restart' => esc_html__('Restart', 'h5vp'),
                      'rewind' => esc_html__('Rewind', 'h5vp'),
                      'play' => esc_html__('Play', 'h5vp'),
                      'fast-forward' => esc_html__('Fast Forwards', 'h5vp'),
                      'progress' => esc_html__('Progressbar', 'h5vp'),
                      'duration' => esc_html__('Duration', 'h5vp'),
                      'current-time' => esc_html__('Current Time', 'h5vp'),
                      'mute' => esc_html__('Mute Button', 'h5vp'),
                      'volume' => esc_html__('Volume Control', 'h5vp'),
                      'settings' => esc_html__('Setting Button', 'h5vp'),
                      'pip' => esc_html__('PIP', 'h5vp'),
                      'airplay' => esc_html__('Airplay', 'h5vp'),
                      'download' => esc_html__('Download Button', 'h5vp'),
                      'fullscreen' => esc_html__('Full Screen', 'h5vp')
                    ),
                    'default' => array( 'play-large', 'play','progress','current-time','mute','volume','settings', 'pip', 'download', 'fullscreen' ),
                ),
                // array(
                //     'id' => 'h5vp_hide_loading_placeholder',
                //     'type' => 'switcher',
                //     'title' => 'Hide Loading Placeholder',
                //     'default' => false
                // ),
                array(
                    'id' => 'h5vp_repeat_playerio',
                    'type' => 'button_set',
                    'title' => 'Repeat',
                    'options' => array(
                        'once' => 'Once',
                        'loop' => 'Loop',
                    ),
                    'default' => h5vp_get_meta_preset('h5vp_op_repeat_playerio', 'once'),
                ),
                array(
                    'id' => 'h5vp_muted_playerio',
                    'type' => 'switcher',
                    'title' => 'Muted',
                    'desc' => 'On if you want the video output should be muted',
                    'default' => h5vp_get_meta_preset('h5vp_op_muted_playerio', '0'),
                ),
                array(
                    'id' => 'h5vp_auto_play_playerio',
                    'type' => 'switcher',
                    'title' => 'Auto Play',
                    'desc' => 'Turn On if you  want video will start playing as soon as it is ready. <a href="https://developers.google.com/web/updates/2017/09/autoplay-policy-changes">autoplay policy</a>',
                    'default' => h5vp_get_meta_preset('h5vp_op_auto_play_playerio', ''),
                ),
                array(
                    'id' => 'h5vp_player_width_playerio',
                    'type' => 'spinner',
                    'title' => 'Player Width',
                    'unit' => 'px',
                    'max' => '5000',
                    'min' => '200',
                    'step' => '50',
                    'desc' => 'set the player width. Height will be calculate base on the value. Left blank for Responsive player',
                    'default' => h5vp_get_meta_preset('h5vp_op_player_width_playerio', ''),
                ),
                array(
                    'id' => 'h5vp_auto_hide_control_playerio',
                    'type' => 'switcher',
                    'title' => 'Auto Hide Control',
                    'desc' => 'On if you want the controls (such as a play/pause button etc) hide automaticaly.',
                    'default' => h5vp_get_meta_preset('h5vp_op_auto_hide_control_playerio', '1'),
                ),
                array(
                    'id' => 'h5vp_ratio',
                    'type' => 'text',
                    'title' => 'Ratio',
                    'desc' => 'force custom video ratio',
                    'placeholder' => '16:9'
                ),
                array(
                    'id' => 'h5vp_video_streaming',
                    'title' => 'Streaming',
                    'subtitle' => 'Dash.js and Hls.js Support',
                    'type' => 'switcher',
                    'class' => 'bplugins-meta-readonly',
                    'text_on' => 'Yes',
                    'text_off' => 'No',
                    'default' => '0',
                ),
                array(
                    'id' => 'h5vp_streaming_type',
                    'title' => 'Streaming By',
                    'type' => 'button_set',
                    'options' => array(
                        'hls' => 'Hls.js',
                        'dash' => 'Dash.js'
                    ),
                    'dependency' => array('h5vp_video_streaming', '==', '1'),
                    'default' => 'hls'
                ),
                array(
                    'id' => 'h5vp_video_link_hlsdash',
                    'type' => 'text',
                    'title' => 'Streaming Source',
                    'placeholder' => 'https://',
                    'library' => 'video',
                    'button_title' => 'Add Video',
                    'desc' => 'paste here the streaming source',
                    'dependency' => array('h5vp_video_streaming', '==', '1'),
                    'attributes' => array('style' => 'width:100%;')
                ),
                array(
                    'id' => 'h5vp_video_link_youtube_vimeo',
                    'type' => 'text',
                    'title' => 'Source URL',
                    'placeholder' => 'https://',
                    'library' => 'video',
                    'button_title' => 'Add Video',
                    'class' => 'bplugins-meta-readonly',
                    'desc' => 'Youtube/vimeo video url or ID',
                    'dependency' => array(array('h5vp_video_source', 'not-any', 'library,amazons3,google'), array('h5vp_video_streaming', '!=', '1')),
                    'attributes' => array('style' => 'width: 100%;')
                ),
                array(
                    'id' => 'h5vp_aws_file_picker',
                    'title' => ' ',
                    'type' => 'button_set',
                    'options' => array(
                        'picker' => '<img src="'.H5VP_PRO_PLUGIN_DIR.'./img/aws.png"/> Choose From AWS S3 Storage',
                    ),
                    'default' => 'picker',
                    'dependency' => array(array('h5vp_video_source', '==', 'amazons3'), array('h5vp_video_streaming', '!=', '1')),
                    'attributes' => array('class' => 'aws_thumbnails_picker', 'seton' => 'h5vp_video_thumbnails'),
                    'class' => 'aws_picker_btn'
                ),
                array(
                    'id' => 'isCDURL',
                    'type' => 'switcher',
                    'title' => 'Custom Download URL?',
                    'default' => false,
                    'class' => 'bplugins-meta-readonly',
                ),
                array(
                    'id' => 'CDURL',
                    'type' => 'text', 
                    'placeholder' => 'URL',
                    'title' => 'URL',
                    'dependency' => array('isCDURL', '==', '1'),
                    'class' => 'bplugins-meta-readonly',
                    'attributes' => array('style' => 'width: 100%;')
                ),
                // array(
                //     'id' => 'eov_google_document',
                //     'title' => 'Google Drive Document URL',
                //     'type' => 'text',
                //     'validate' => 'csf_validate_url',
                //     'attributes' => array(
                //         'style' => 'min-height:29px !important;height:29px',
                //         'id' => 'eov_google_document_url',
                //     ),
                //     'dependency' => array('h5vp_video_source', '==', 'google'),
                // ),
              
                // only for one persion
                // array(
                //     'id' => 'force_custom_thumbnail',
                //     'type' => 'switcher',
                //     'title' => 'Alwyse use custom thumbnail',
                //     'desc' => 'if turned off the custom thumbnail, will only be shown when loading thumbnail from yoube fails',
                //     'default' => '1',
                //     'dependency' => array('h5vp_video_source', '==', 'youtube')
                // ),
                array(
                    'id' => 'h5vp_start_time',
                    'type' => 'number',
                    'title' => 'Video Start Time',
                    'desc' => 'Video start time in second',
                    'class' => 'bplugins-meta-readonly',
                    'default' => '0'
                ),
                array(
                    'id' => 'h5vp_poster_when_pause',
                    'type' => 'switcher',
                    'title' => 'Show Thumbnail when video pause',
                    'class' => 'bplugins-meta-readonly',
                    'default' => '0'
                ),
                array(
                    'id' => 'h5vp_popup',
                    'type' => 'switcher',
                    'title' => 'Enable Popup',
                    'class' => 'bplugins-meta-readonly',
                    'desc' => 'Enable Popup to open this video as modal',
                    'default' => '0'
                ),
                array(
                    'id' => 'h5vp_disable_pause',
                    'type' => 'switcher',
                    'title' => 'Disable Pause',
                    'class' => 'bplugins-meta-readonly',
                    'desc' => 'Disable Pause button so User can\'t pause video after play',
                    'default' => '0'
                ),
                array(
                    'id' => 'h5vp_sticky_mode',
                    'type' => 'switcher',
                    'title' => 'Enabled Sticky Mode',
                    'class' => 'bplugins-meta-readonly',
                    'desc' => 'when visitor will scroll bottom and video is playing than video will sticky on top-right corner.',
                    'default' => '0'
                ),

                // playerio metabox
                
                array(
                    'id' => 'h5vp_seek_time_playerio',
                    'type' => 'number',
                    'title' => 'Seek Time',
                    'class' => 'bplugins-meta-readonly',
                    'desc' => 'The time, in seconds, to seek when a user hits fast forward or rewind. Default value is 10 Sec.',
                    'default' => h5vp_get_meta_preset('h5vp_op_seek_time_playerio', '10'),
                ),
                
                array(
                    'id' => 'h5vp_reset_on_end_playerio',
                    'type' => 'switcher',
                    'title' => 'Reset On End',
                    'text_on' => 'Yes',
                    'text_off' => 'No',
                    'class' => 'bplugins-meta-readonly',
                    'desc' => 'video will reset to first and show thumbnail',
                    'default' => h5vp_get_meta_preset('h5vp_op_reset_on_end_playerio', '1'),
                ),
                array(
                    'id' => 'h5vp_preload_playerio',
                    'type' => 'radio',
                    'title' => 'Preload',
                    'class' => 'bplugins-meta-readonly',
                    'options' => array(
                        'auto' => 'Auto - Browser should load the entire file when the page loads.',
                        'metadata' => 'Metadata - Browser should load only meatadata when the page loads.',
                        'none' => 'None - Browser should NOT load the file when the page loads.',
                    ),
                    'desc' => 'Specify how the video file should be loaded when the page loads.',
                    'default' => h5vp_get_meta_preset('h5vp_op_preload_playerio', 'metadata'),
                ),
                array(
                    'id' => 'h5vp_password_protected',
                    'title' => 'Password Protected (Experimental)',
                    'type' => 'switcher',
                    'class' => 'bplugins-meta-readonly',
                    'text_on' => 'Yes',
                    'text_off' => 'Off',
                    'default' => 0
                ),
                array(
                    'id' => 'h5vp_protected_password',
                    'title' => 'Password',
                    'type' => 'password',
                    'class' => 'bplugins-meta-readonly',
                    'dependency' => array('h5vp_password_protected', '==', '1'),
                    'sanitize' => 'h5vp_encript_password'
                ),
                array(
                    'id' => 'h5vp_protected_password_text',
                    'title' => 'Text for Password Protected Video',
                    'type' => 'text',
                    'class' => 'bplugins-meta-readonly',
                    'dependency' => array('h5vp_password_protected', '==', '1'),
                    // 'sanitize' => 'h5vp_encript_password'
                    'default' => "It's a Password Protected Video. Do You Have any Password?"
                ),
                array(
                    'id' => 'h5vp_ad_tagUrl',
                    'type' => 'textarea',
                    'title' => 'Google VAST TagURL',
                    'class' => 'bplugins-meta-readonly',
                    'attributes' => array('style' => "height: 70px;min-height:70px;"),
                ),
                array(
                    'id' => 'h5vp_quality_playerio',
                    'type' => 'group',
                    'title' => 'Enable video quality switcher By Putting diffrent qualities of same video, leave blank if you don\'t want the quality switcher in the player.',
                    'class' => 'bplugins-meta-readonly',
                    'dependency' => array('h5vp_video_source', '!=', 'youtube'),
                    'fields' => array(
                        array(
                            'id' => 'size',
                            'type' => 'number',
                            'title' => 'Size',
                            'placeholder' => 'Eg: 1080',
                            'desc' => 'enter the video size, eg: 4320, 3840, 2880, 2160, 1920, 1440, 1280, 1080,800, 720, 640, 576, 480, 360, 240',
                        ),
                        array(
                            'id' => 'video_file',
                            'type' => 'upload',
                            'title' => 'Video',
                            'placeholder' => 'https://',
                            'desc' => 'select an mp4 or ogg video file or paste a external video file link',
                            'button_title' => 'Add Video',
                        ),
                    ),
                    'button_title' => 'Add Quality',
                ),
                array(
                    'id' => 'h5vp_subtitle_playerio',
                    'type' => 'group',
                    'class' => 'bplugins-meta-readonly',
                    'title' => 'You can set single or multiple subtitle, leave blank if you don\'t want to use subtitle.',
                    'fields' => array(
                        array(
                            'id' => 'label',
                            'type' => 'text',
                            'title' => 'Language',
                            'desc' => 'Eg: English, French etc. This will be used as a identifire in the subtitle switcher when multiple subtitle will be set',
                            'placeholder' => 'Eg: English',
                        ),
                        array(
                            'id' => 'caption_file',
                            'type' => 'upload',
                            'title' => 'Subtitle File',
                            'desc' => '.vtt file only. select a .vtt file or paste .vtt file link.',
                            'placeholder' => 'Subtitle File link',
                        ),
                    ),
                    'button_title' => 'Add Subtitle',
                    'dependency' => array('h5vp_video_source', '!=', 'youtube'),
                ),
                array(
                    'id' => 'h5vp_enable_caption',
                    'title' => 'Enable caption by default (Experimental)',
                    'type' => 'switcher',
                    'text_on' => 'Yes',
                    'text_off' => 'Off',
                    'default' => 0,
                    'class' => 'bplugins-meta-readonly',
                    'dependency' => array('h5vp_video_source', '==', 'library'),
                ),
                array(
                    'id' => 'h5vp_chapters',
                    'type' => 'group',
                    'title' => 'Chapters',
                    'class' => 'bplugins-meta-readonly',
                    'fields' => array(
                        array(
                            'id' => 'name',
                            'type' => 'text',
                            'title' => 'Name',
                            'placeholder' => 'Chapter Name',
                        ),
                        array(
                            'id' => 'time',
                            'type' => 'text',
                            'title' => 'time',
                            'desc' => 'minute:seconds or seconds',
                            'placeholder' => '00:00',
                        ),
                    ),
                    'button_title' => 'Add Chapter',
                ),
                array(
                    'id' => 'hideYoutubeUI',
                    'type' => 'switcher',
                    'class' => 'bplugins-meta-readonly',
                    'title' => 'Hide Youtube UI (Experimental, check it\'s working or not for you)',
                    'dependency' => array('h5vp_video_source', '==', 'youtube')
                )
            ),
        ));
    
    }

}

// require_once "option-page.php";
// require_once 'playlist-meta.php';

/**
 *
 * Field: password
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
// if( ! class_exists( 'CSF_Field_password' ) && class_exists('CSF_Fields') ) {
//     class CSF_Field_password extends \CSF_Fields {
  
//       public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
//         parent::__construct( $field, $value, $unique, $where, $parent );
//       }
  
//       public function render(){
//         echo $this->field_before();
//         echo '<input type="password" name="'. $this->field_name() .'" value="'. $this->value .'"'. $this->field_attributes() .' />';
//         echo '<button type="button" class="button button-secondary wp-hide-pw hide-if-no-js h5vp_show_password" data-toggle="0" aria-label="Show password"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></button>';
//         echo $this->field_after();
  
//       }
  
//     }
//   }