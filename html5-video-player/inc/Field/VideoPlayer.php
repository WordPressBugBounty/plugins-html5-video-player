<?php

namespace H5VP\Field;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class VideoPlayer
{
    public $prefix = '_h5vp_';

    public function register()
    {
        add_action('init', [$this, 'register_fields'], 0);
    }
    public function register_fields()
    {
        if (class_exists('\CSF')) {
            $prefix = '_h5vp_';
            \CSF::createMetabox($prefix, array(
                'title' => 'Configure Your Video Player',
                'post_type' => 'videoplayer',
                'data_type' => 'unserialize',
                'theme' => 'light'
            ));

            $this->media();
            $this->controls($prefix);
            $this->quality();
            $this->subtitle();
            $this->popup();
            $this->password_protected();
            $this->chapters();
            $this->watermark();
            $this->seo();
            $this->additional();
            $this->style();
        }
    }

    public function media(){
        $id =  sanitize_text_field(wp_unslash($_GET['post'] ?? ''));

         $preset = h5vp_get_option('h5vp_option');
         
        \CSF::createSection($this->prefix, array(
            'title' => __('Media', 'h5vp'),
            'id' => 'h5vp_media',
            'fields' => [
                array(
                    'id' => 'h5vp_video_source',
                    'title' => 'Video Source',
                    'type' => 'button_set',
                    'options' => array(
                        'library' => 'Library or CDN source',
                        'youtube' => 'Youtube',
                        'vimeo' => 'Vimeo'
                    ),
                    'default' => 'library',
                    'dependency' => array('h5vp_video_streaming', '==', '0', 'all')
                ),
                array(
                    'id' => 'h5vp_video_link_youtube_vimeo',
                    'type' => 'text',
                    'title' => 'Source URL',
                    'placeholder' => 'https://',
                    'library' => 'video',
                    'button_title' => 'Add Video',
                    'desc' => 'Youtube video url or ID',
                    'dependency' => array(array('h5vp_video_source', 'not-any', 'library,amazons3,google', 'all')),
                    'attributes' => array('style' => 'width: 100%;')
                ),
                array(
                    'id' => 'h5vp_aws_file_picker',
                    'title' => ' ',
                    'type' => 'button_set',
                    'options' => array(
                        'picker' => '<img src="' . H5VP_PRO_PLUGIN_DIR . './img/aws.png"/> Choose From AWS S3 Storage',
                    ),
                    'default' => 'picker',
                    'class' => 'bplugins-meta-readonly',
                    'dependency' => array(array('h5vp_video_source', '==', 'amazons3', 'all'), array('h5vp_video_streaming', '!=', '1', 'all')),
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
                    'dependency' => array(array('h5vp_video_source', 'any', 'library,amazons3,google', 'all'), array('h5vp_video_streaming', '!=', '1', 'all')),
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
            ]
        ));
    }

    public function controls($prefix)
    {
        $id =  sanitize_text_field(wp_unslash($_GET['post'] ?? ''));
        $preset = h5vp_get_option('h5vp_option');
        // Create a section
        \CSF::createSection($prefix, array(
            'title' => 'General',
            'id' => 'noting-to-hide',
            'fields' => array(
                
                array(
                    'id' => 'h5vp_controls',
                    'type' => 'button_set',
                    'title' => __('Controls', 'h5vp'),
                    'multiple' => true,
                    'options' => array(
                        'play-large' => __('Play Large', 'h5vp'),
                        'restart' => __('Restart', 'h5vp'),
                        'rewind' => __('Rewind', 'h5vp'),
                        'play' => __('Play', 'h5vp'),
                        'fast-forward' => __('Fast Forwards', 'h5vp'),
                        'progress' => __('Progressbar', 'h5vp'),
                        'duration' => __('Duration', 'h5vp'),
                        'current-time' => __('Current Time', 'h5vp'),
                        'mute' => __('Mute Button', 'h5vp'),
                        'volume' => __('Volume Control', 'h5vp'),
                        'settings' => __('Setting Button', 'h5vp'),
                        'pip' => __('PIP', 'h5vp'),
                        'airplay' => __('Airplay', 'h5vp'),
                        'download' => __('Download Button', 'h5vp'),
                        'fullscreen' => __('Full Screen', 'h5vp')
                    ),
                    'default' => array('play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'settings', 'pip', 'download', 'fullscreen'),
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
                    'desc' =>  __("Automatically replay the video from the beginning after it ends.", "h5vp"),
                    'options' => array(
                        'once' => 'Once',
                        'loop' => 'Loop',
                    ),
                    'default' => $preset('h5vp_op_repeat_playerio', 'once'),
                ),
                array(
                    'id' => 'h5vp_muted_playerio',
                    'type' => 'switcher',
                    'title' => 'Muted',
                    'desc' => __("Start the video with sound turned off by default.", "h5vp"),
                    'default' => $preset('h5vp_op_muted_playerio', '0'),
                ),
                array(
                    'id' => 'h5vp_auto_play_playerio',
                    'type' => 'switcher',
                    'title' => 'Auto Play',
                    'desc' => 'Turn On if you  want video will start playing as soon as it is ready. <a href="https://developers.google.com/web/updates/2017/09/autoplay-policy-changes">autoplay policy</a>',
                    'default' => $preset('h5vp_op_auto_play_playerio', ''),
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
                    'default' => $preset('h5vp_op_player_width_playerio', ''),
                ),
                array(
                    'id' => 'h5vp_auto_hide_control_playerio',
                    'type' => 'switcher',
                    'title' => 'Auto Hide Control',
                    'desc' => __("Hide video controls automatically after 2s of no mouse or focus movement", "h5vp"),
                    'default' => $preset('h5vp_op_auto_hide_control_playerio', '1'),
                ),
                array(
                    'id' => 'h5vp_ratio',
                    'type' => 'text',
                    'title' => 'Ratio',
                    'desc' => __("Select the width-to-height ratio used to display the video.", "h5vp"),
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
                    'id' => 'h5vp_aws_file_picker',
                    'title' => ' ',
                    'type' => 'button_set',
                    'options' => array(
                        'picker' => '<img src="' . H5VP_PRO_PLUGIN_DIR . './img/aws.png"/> Choose From AWS S3 Storage',
                    ),
                    'default' => 'picker',
                    'dependency' => array(array('h5vp_video_source', '==', 'amazons3', 'all'), array('h5vp_video_streaming', '!=', '1', 'all')),
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
                    'dependency' => array('isCDURL', '==', '1', 'all'),
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
                    'desc' => __("The video will begin playing from this specified time.", "h5vp"),
                    'class' => 'bplugins-meta-readonly',
                    'default' => '0'
                ),
                array(
                    'id' => 'h5vp_poster_when_pause',
                    'type' => 'switcher',
                    'title' => 'Show Thumbnail when video pause',
                    'desc' => __("Show the video thumbnail image when the video is paused.", "h5vp"),
                    'class' => 'bplugins-meta-readonly',
                    'default' => '0'
                ),
                
                array(
                    'id' => 'h5vp_disable_pause',
                    'type' => 'switcher',
                    'title' => 'Disable Pause',
                    'class' => 'bplugins-meta-readonly',
                    'desc' =>  __("Prevent users from pausing the video during playback.", "h5vp"),
                    'default' => '0'
                ),
                array(
                    'id' => 'h5vp_sticky_mode',
                    'type' => 'switcher',
                    'title' => 'Enabled Sticky Mode',
                    'class' => 'bplugins-meta-readonly',
                    'desc' => __("Keep the mini video player visible on screen while scrolling the page.", "h5vp"),
                    'default' => '0'
                ),

                // playerio metabox

                array(
                    'id' => 'h5vp_seek_time_playerio',
                    'type' => 'number',
                    'title' => 'Seek Time',
                    'class' => 'bplugins-meta-readonly',
                    'desc' => __("The time, in seconds, to seek when a user hits fast forward or rewind.", "h5vp"),
                    'default' => $preset('h5vp_op_seek_time_playerio', '10'),
                ),

                array(
                    'id' => 'h5vp_reset_on_end_playerio',
                    'type' => 'switcher',
                    'title' => 'Reset On End',
                    'text_on' => 'Yes',
                    'text_off' => 'No',
                    'class' => 'bplugins-meta-readonly',
                    'desc' => __("Reset the video to the beginning when playback finishes.", "h5vp"),
                    'default' => $preset('h5vp_op_reset_on_end_playerio', '1'),
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
                    'desc' => __("Control how much of the video is loaded before playback. Options affect loading speed and bandwidth usage.", "h5vp"),
                    'default' => $preset('h5vp_op_preload_playerio', 'metadata'),
                ),
                
                array(
                    'id' => 'h5vp_ad_tagUrl',
                    'type' => 'textarea',
                    'title' => 'Google VAST TagURL',
                    'desc' => __("Enter the Google VAST ad tag URL to display video ads before or during playback.", "h5vp"),
                    'class' => 'bplugins-meta-readonly',
                    'attributes' => array('style' => "height: 70px;min-height:70px;"),
                ),
                
                
                
                array(
                    'id' => 'hideYoutubeUI',
                    'type' => 'switcher',
                    'class' => 'bplugins-meta-readonly',
                    'title' => 'Hide Youtube UI (Experimental, check it\'s working or not for you)',
                    'desc' => __("Hide YouTube player interface elements for a cleaner video display.", "h5vp"),
                    'dependency' => array('h5vp_video_source', '==', 'youtube', 'all')
                )
            ),
        ));
    }

    public function quality(){
        \CSF::createSection($this->prefix, array(
            'title' => __('Quality', 'h5vp') . ' <span class="h5vp-metabox-pro-badge">Pro</span>',
            'id' => 'h5vp_quality',
            'fields' => [
                $this->upgrade_section(),
                array(
                    'id' => 'readonly',
                    'type' => 'content',
                    'title' => 'Quality Switcher',
                    'class' => 'bplugins-meta-readonly',
                    'dependency' => array('h5vp_video_source|h5vp_video_source', '===|===', 'youtube|vimeo', 'all'),
                    'content' => 'Quality switcher is not available for youtube and vimeo videos',
                ),
                [
                    'id' => 'readonly',
                    'type' => 'group',
                    'title' => 'Enable video quality switcher By Putting diffrent qualities of same video, leave blank if you don\'t want the quality switcher in the player.',
                    'class' => 'bplugins-meta-readonly',
                    'dependency' => array('h5vp_video_source|h5vp_video_source', '!=|!=', 'youtube|vimeo', 'all'),
                    'fields' => [
                        [
                            'id' => 'size',
                            'type' => 'number',
                            'title' => 'Size',
                            'placeholder' => 'Eg: 1080',
                            'desc' => 'enter the video size, eg: 4320, 3840, 2880, 2160, 1920, 1440, 1280, 1080,800, 720, 640, 576, 480, 360, 240',
                        ],
                        [
                            'id' => 'video_file',
                            'type' => 'upload',
                            'title' => 'Video',
                            'placeholder' => 'https://',
                            'desc' => 'select an mp4 or ogg video file or paste a external video file link',
                            'button_title' => 'Add Video',
                        ],
                    ],
                    'button_title' => 'Add Quality',
                ],
            ]
        ));
    }

    public function subtitle(){
        \CSF::createSection($this->prefix, array(
            'title' => __('Subtitle/Caption', 'h5vp') . ' <span class="h5vp-metabox-pro-badge">Pro</span>',
            'id' => 'h5vp_subtitle',
            'fields' => [
                $this->upgrade_section(),
                array(
                    'id' => 'readonly',
                    'type' => 'group',
                    'class' => 'bplugins-meta-readonly',
                    'title' => 'You can set single or multiple subtitle, leave blank if you don\'t want to use subtitle.',
                    'fields' => array(
                        array(
                            'id' => 'label',
                            'type' => 'text',
                            'title' => 'Language',
                            'desc' => __("Enter the subtitle label with language name and code (e.g., English / en).", "h5vp"),
                            'placeholder' => 'Eg: English',
                        ),
                        array(
                            'id' => 'caption_file',
                            'type' => 'upload',
                            'title' => 'Subtitle File',
                            'desc' =>  __("Only .vtt file accept", "h5vp"),
                            'placeholder' => 'Subtitle File link',
                            'library' => 'text'
                        ),
                    ),
                    'button_title' => 'Add Subtitle',
                    'dependency' => array('h5vp_video_source|h5vp_video_source', '!=|!=', 'youtube|vimeo', 'all'),
                ),
                array(
                    'id' => 'readonly',
                    'title' => 'Enable caption by default (Experimental)',
                    'type' => 'switcher',
                    'text_on' => 'Yes',
                    'text_off' => 'Off',
                    'default' => 0,
                    'class' => 'bplugins-meta-readonly',
                    'dependency' => array('h5vp_video_source', '==', 'library', 'all'),
                ),
            ]
        ));
    }
    public function popup(){
        \CSF::createSection($this->prefix, array(
            'title' => __('Popup', 'h5vp') . ' <span class="h5vp-metabox-pro-badge">Pro</span>',
            'id' => 'h5vp_popup',
            'fields' => [
                $this->upgrade_section(),
                array(
                    'id' => 'readonly',
                    'type' => 'switcher',
                    'title' => 'Enable Popup',
                    'class' => 'bplugins-meta-readonly',
                    'desc' => 'Enable Popup to open this video as modal',
                    'default' => '0'
                ),
            ]
        ));
    }
    public function password_protected(){
        \CSF::createSection($this->prefix, array(
            'title' => __('Password Protected', 'h5vp') . ' <span class="h5vp-metabox-pro-badge">Pro</span>',
            'id' => 'h5vp_password_protected',
            'fields' => [
                $this->upgrade_section(),
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
                    'id' => 'readonly',
                    'title' => 'Password',
                    'type' => 'text',
                    'class' => 'bplugins-meta-readonly',
                    'default' => '************'
                ),
                array(
                    'id' => 'readonly_text',
                    'title' => 'Text for Password Protected Video',
                    'type' => 'text',
                    'class' => 'bplugins-meta-readonly',
                    'default' => "It's a Password Protected Video. Do You Have any Password?"
                ),
                // button text
                [
                    'id' => 'readonly',
                    'type' => 'text',
                    'title' => 'Button Text',
                    'class' => 'bplugins-meta-readonly',
                ],
                // password incorrect message
                [
                    'id' => 'readonly',
                    'type' => 'text',
                    'title' => 'Password Incorrect Message',
                    'class' => 'bplugins-meta-readonly',
                ],
                
            ]
        ));
    }

    public function chapters(){
        \CSF::createSection($this->prefix, array(
            'title' => __('Chapter', 'h5vp') . ' <span class="h5vp-metabox-pro-badge">Pro</span>',
            'id' => 'h5vp_chapter',
            'fields' => [
                $this->upgrade_section(),
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
            ]
        ));
    }
    public function watermark(){
        \CSF::createSection($this->prefix, array(
            'title' => __('Watermark', 'h5vp') . ' <span class="h5vp-metabox-pro-badge">Pro</span>',
            'id' => 'h5vp_watermark',
            'fields' => [
                $this->upgrade_section(),
                [
                    'id' => 'readonly',
                    'type' => 'switcher',
                    'title' => __("Enable Watermark", "h5vp"),
                    'class' => 'bplugins-meta-readonly',
                ],
                // type
                [
                    'id' => 'readonly',
                    'type' => 'select',
                    'title' => __("Type", "h5vp"),
                    'options' => [
                        'text' => 'Custom Text',
                        'username' => 'Username',
                        'email' => 'Email',
                    ],
                    'class' => 'bplugins-meta-readonly',
                ],
                // custom text
                [
                    'id' => 'readonly',
                    'type' => 'text',
                    'title' => __("Custom Text", "h5vp"),
                    'class' => 'bplugins-meta-readonly',
                ],
                // text color
                [
                    'id' => 'readonly',
                    'type' => 'color',
                    'title' => 'Text Color',
                    'class' => 'bplugins-meta-readonly',
                ]
            ]
        ));
    }
    public function seo(){
        \CSF::createSection($this->prefix, array(
            'title' => __('SEO (Google)', 'h5vp') . ' <span class="h5vp-metabox-pro-badge">Pro</span>',
            'id' => 'h5vp_seo',
            'fields' => [
                $this->upgrade_section(),
                [
                    'id' => 'readonly',
                    'type' => 'text',
                    'title' => __("Name", "h5vp"),
                    'class' => 'bplugins-meta-readonly',
                ],
                [
                    'id' => 'readonly',
                    'type' => 'textarea',
                    'title' => __("Description", "h5vp"),
                    'class' => 'bplugins-meta-readonly',
                ],
                [
                    'id' => 'readonly',
                    'type' => 'text',
                    'title' => __("Duration", "h5vp"),
                    'class' => 'bplugins-meta-readonly',
                ]
            ]
        ));
    }
    public function additional(){
        \CSF::createSection($this->prefix, array(
            'title' => __('Additional', 'h5vp') . ' <span class="h5vp-metabox-pro-badge">Pro</span>',
            'id' => 'h5vp_additional',
            'fields' => [
                $this->upgrade_section()
            ]
        ));
    }
    public function style(){
        \CSF::createSection($this->prefix, array(
            'title' => __('Style', 'h5vp') . ' <span class="h5vp-metabox-pro-badge">Pro</span>',
            'id' => 'h5vp_style',
            'fields' => [
                $this->upgrade_section()
            ]
        ));
    }

    function upgrade_section(){
		return array(
					'type' => 'callback',
					'function' => [$this, 'upgrade_callback']
		);
	}

    function upgrade_callback(){
        ?>
            <div class="pdfp-metabox-upgrade-section">No-Code Video Player Plugin – Trusted by 30,000+ Websites Worldwide. <a class="button button-bplugins" href="<?php echo esc_url(admin_url('admin.php?page=html5-video-player#/pricing')); ?>">Upgrade to PRO </a></div>
        <?php
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

