<?php
namespace H5VP\Elementor;
require_once(__DIR__.'/../Helper/DefaultArgs.php');
require_once(__DIR__.'/../Services/VideoTemplate.php');
use H5VP\Helper\DefaultArgs;
use H5VP\Services\VideoTemplate;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Elementor Hello World
 *
 * Elementor widget for hello world.
 *
 * @since 1.0.0
 */
class VideoPlayerPro extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'H5VPPlayer';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'HTML5 Video Player', 'h5vp' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-video-camera';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'basic' ];
	}

	/**
	 * Retrieve the list of scripts the widget depended on.
	 *
	 * Used to set scripts dependencies required to run the widget.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget scripts dependencies.
	 */
	public function get_script_depends() {
		return ['html5-player-video-view-script'];
	}

	/**
	 * Style
	 */
	public function get_style_depends() {
		return ['html5-player-video-style'];
	}


	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Settings', 'h5vp' ),
				'tab' 	=> \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'video_source',
			[
				'label' => __( 'Video Source', 'h5vp' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'library',
				'options' => [
					'library'  => __( 'Library or CDN Source', 'h5vp' ),
					'youtube'  => __( 'Youtube', 'h5vp' ),
					'vimeo'  => __( 'Vimeo', 'h5vp' ),
				],
			]
		);

		$this->add_control(
			'source',
			[
				'label' 		=> esc_html__( 'Select Video', 'h5vp' ),
				'type' 			=> 'b-select-file',
				'separator' 	=> 'before',
				'placeholder' => esc_html__("Paste Video URL", "h5vp"),
				'condition' => array(
					'video_source' => 'library'
				)
			]
		);

		$this->add_control(
			'source_youtube_vimeo',
			[
				'label' 		=> esc_html__( 'video id or url', 'h5vp' ),
				'type' 			=> Controls_Manager::TEXT,
				'separator' 	=> 'before',
				'default' => '',
				'label_block' => true,
				'condition' => [
					'video_source' => ['youtube', 'vimeo']
				]
			]
		);

		$this->add_control(
			'poster',
			[
				'label' 		=> esc_html__( 'Select Poster', 'h5vp' ),
				'type' 			=> 'b-select-file',
				'separator' 	=> 'before',
				'placeholder' => esc_html__("Paste Poster URL", "h5vp"),
			]
		);

		$this->add_control(
			'width',
			[
				'label' 		=> __( 'Width', 'h5vp' ),
				'type'			=> Controls_Manager::SLIDER,
				'size_units' 	=> [ 'px', '%'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1000,
						'step' => 5,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default' => [
					'unit' => '%',
					'size' => 100,
				],
				'separator' => 'before'
			]
		);

		$this->add_control(
			'repeat',
			[
				'label' => __( 'Repeat', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'h5vp' ),
				'label_off' => __( 'No', 'h5vp' ),
				'return_value' => '1',
				'default' => '',
				'separator' 	=> 'before',
			]
		);

		$this->add_control(
			'muted',
			[
				'label' => __( 'Muted', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'h5vp' ),
				'label_off' => __( 'No', 'h5vp' ),
				'return_value' => '1',
				'default' => '',
				'separator' 	=> 'before',
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label' => __( 'Autoplay', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'h5vp' ),
				'label_off' => __( 'No', 'h5vp' ),
				'return_value' => '1',
				'default' => '',
				'separator' 	=> 'before',
			]
		);

		$this->add_control(
			'seek_time',
			[
				'label' 		=> esc_html__( 'Seek Time', 'h5vp' ),
				'type' 			=> Controls_Manager::NUMBER,
				'placeholder'	=> esc_attr__('Input Seek Time','h5vp'),
				'default'		=> 10,
				'label_block'	=> false,
				'separator' => 'before'
			]
		);
		
		$this->add_control(
			'sticky_mode',
			[
				'label' => __( 'Sticky On Scroll', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'h5vp' ),
				'label_off' => __( 'No', 'h5vp' ),
				'return_value' => '1',
				'default' => '',
				'separator' 	=> 'before',
			]
		);

		$this->add_control(
			'start_time',
			[
				'label' 		=> esc_html__( 'Start Time', 'h5vp' ),
				'type' 			=> Controls_Manager::NUMBER,
				'placeholder'	=> esc_attr__('Input Start Time','h5vp'),
				'default'		=> 0,
				'label_block'	=> false,
				'condition'		=> array(
					'video_source' => 'library'
				),
				'separator' => 'before'
			]
		);

		$this->add_control(
			'popup',
			[
				'label' => __( 'Enable Popup', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'h5vp' ),
				'label_off' => __( 'No', 'h5vp' ),
				'return_value' => '1',
				'default' => '',
				'separator' 	=> 'before',
			]
		);

		$this->add_control(
			'disable_pause',
			[
				'label' => __( 'Disable Pause', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'h5vp' ),
				'label_off' => __( 'No', 'h5vp' ),
				'return_value' => '1',
				'default' => '',
				'separator' 	=> 'before',
			]
		);

		$this->add_control(
			'reset_on_end',
			[
				'label' => __( 'Reset On End', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'h5vp' ),
				'label_off' => __( 'No', 'h5vp' ),
				'return_value' => '1',
				'default' => '1',
				'separator' 	=> 'before',
				'condition' => array(
					'video_source' => 'library'
				)
			]
		);

		$this->add_control(
			'auto_hide_control',
			[
				'label' => __( 'Auto Hide Control', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'h5vp' ),
				'label_off' => __( 'No', 'h5vp' ),
				'return_value' => '1',
				'default' => '1',
				'separator' 	=> 'before',
			]
		);

		$this->add_control(
			'hideYoutubeUI',
			[
				'label' => __( 'Hide Youtube UI', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'h5vp' ),
				'label_off' => __( 'No', 'h5vp' ),
				'return_value' => '1',
				'default' => false,
				'separator' 	=> 'before',
				'condition' => [
					'video_source' => ['youtube']
				]
			]
		);
		
		$this->add_control(
			'preload',
			[
				'label' => __( 'Preload', 'h5vp' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'metadata',
				'options' => [
					'auto'  => __( 'Auto - Browser should load the entire file when the page loads.', 'h5vp' ),
					'metadata'  => __( 'Metadata - Browser should load only meatadata when the page loads.', 'h5vp' ),
					'none'  => __( 'None - Browser should NOT load the file when the page loads.', 'h5vp' ),
				],
				'separator' => 'before'
			]
		);

		$this->add_control(
			'protected',
			[
				'label' => __( 'Password Protected', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'h5vp' ),
				'label_off' => __( 'No', 'h5vp' ),
				'return_value' => '1',
				'default' => '0',
				'separator' 	=> 'before',
			]
		);

		$this->add_control(
			'password',
			[
				'label' 		=> esc_html__( 'Password', 'h5vp' ),
				'type' 			=> Controls_Manager::TEXT,
				'separator' 	=> 'before',
				'default' => '',
				'label_block' => true,
				'condition' => array(
					'protected' => '1'
				)
			]
		);

		$this->add_control(
			'protected_text',
			[
				'label' 		=> esc_html__( 'Password Protected Text', 'h5vp' ),
				'type' 			=> Controls_Manager::TEXTAREA,
				'separator' 	=> 'before',
				'default' => "It's a Password Protected Video. Do You Have any Password?",
				'label_block' => true,
				'condition' => array(
					'protected' => '1'
				)
			]
		);

		$this->add_control(
			'vast_url',
			[
				'label' 		=> esc_html__( 'Google VAST TagURL', 'h5vp' ),
				'type' 			=> Controls_Manager::TEXTAREA,
				'separator' 	=> 'before',
				'default' => '',
				'label_block' => true
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'controls',
			[
				'label' => esc_html__( 'Controls', 'h5vp' ),
				'tab' 	=> Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'play-large',
			[
				'label' => __( 'Large Play', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'play-large',
				'separator' 	=> 'before',
				'default' => 'play-large'
			]
		);

		$this->add_control(
			'restart',
			[
				'label' => __( 'Restart', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'restart',
				'separator' 	=> 'before',
			]
		);

		$this->add_control(
			'rewind',
			[
				'label' => __( 'Rewind', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'rewind',
				// 'default' => 'rewind',
				'separator' 	=> 'before',
			]
		);

		$this->add_control(
			'play',
			[
				'label' => __( 'Play', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'play',
				'default' => 'play',
				'separator' 	=> 'before',
			]
		);

		$this->add_control(
			'fast-forward',
			[
				'label' => __( 'Fast Forward', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'fast-forward',
				// 'default' => 'fast-forward',
				'separator' 	=> 'before',
			]
		);


		$this->add_control(
			'progress',
			[
				'label' => __( 'Progressbar', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'progress',
				'default' => 'progress',
				'separator' 	=> 'before',
			]
		);
		
		$this->add_control(
			'current-time',
			[
				'label' => __( 'Current Time', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'current-time',
				'default' => 'current-time', 
				'separator' 	=> 'before',
			]
		);
		$this->add_control(
			'duration',
			[
				'label' => __( 'Duration', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'duration',
				// 'default' => 'duration',
				'separator' 	=> 'before',
			]
		);
		$this->add_control(
			'mute',
			[
				'label' => __( 'Mute', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'mute',
				'default' => 'mute',
				'separator' 	=> 'before',
			]
		);
		$this->add_control(
			'volume',
			[
				'label' => __( 'Volume', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'volume',
				'default' => 'volume',
				'separator' 	=> 'before',
			]
		);
		$this->add_control(
			'settings',
			[
				'label' => __( 'Settings', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'settings',
				'default' => 'settings',
				'separator' 	=> 'before',
			]
		);


		$this->add_control(
			'pip',
			[
				'label' => __( 'PIP', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'pip',
				'separator' 	=> 'before',
			]
		);

		$this->add_control(
			'airplay',
			[
				'label' => __( 'Air Play', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'ariplay',
				'separator' 	=> 'before',
			]
		);

		$this->add_control(
			'fullscreen',
			[
				'label' => __( 'Full Screen', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'fullscreen',
				'separator' 	=> 'before',
			]
		);

		$this->add_control(
			'download',
			[
				'label' => __( 'Downlaod', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'download',
				'separator' 	=> 'before',
				'condition' => array(
					'video_source' => 'library'
				)
			]
		);

		$this->add_control(
			'controls_shadow',
			[
				'label' => __( 'Controls Shadow', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => '1',
				'default' => '1',
				'separator' 	=> 'before',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'branding_section',
			[
				'label' => esc_html__( 'Branding', 'h5vp' ),
				'tab' 	=> Controls_Manager::TAB_CONTENT,
			]
		);
		
		$this->add_control(
			'branding',
			[
				'label' => __( 'Overlay', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'h5vp' ),
				'label_off' => __( 'No', 'h5vp' ),
				'return_value' => '1',
				'default' => '',
			]
		);
		
		$this->add_control(
			'branding_type',
			[
				'label' => __( 'type', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Text', 'h5vp' ),
				'label_off' => __( 'Logo', 'h5vp' ),
				'return_value' => 'text',
				'default' => 'text',
				'condition' => [
					'branding' => '1'
				]
			]
		);

		$this->add_control(
			'branding_position',
			[
				'label' => __( 'Position', 'h5vp' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'top_right',
				'options' => [
					'top_right'  => __( 'top_right', 'h5vp' ),
					'top_left'  => __( 'Top Left', 'h5vp' ),
				],
			]
		);

		$this->add_control(
			'branding_text',
			[
				'label' 		=> esc_html__( 'Text', 'h5vp' ),
				'type' 			=> Controls_Manager::TEXT,
				'default' => 'Simple Text',
				'label_block' => true,
				'condition' => [
					'branding' => '1',
					'branding_type' => 'text'
				]
			]
		);

		$this->add_control(
			'branding_logo',
			[
				'label' 		=> esc_html__( 'Select Logo', 'h5vp' ),
				'type' 			=> 'b-select-file',
				'label_block' => true,
				'condition' => [
					'branding' => '1',
					'branding_type' => ''
				]
			]
		);

		$this->add_control(
			'branding_link',
			[
				'label' 		=> esc_html__( 'URL', 'h5vp'),
				'type' 			=> Controls_Manager::TEXT,
				'default' => '',
				'label_block' => true,
				'condition' => [
					'branding' => '1'
				]
			]
		);

		$this->add_control(
			'branding_text_color',
			[
				'label' 		=> esc_html__( 'Text Color', 'h5vp' ),
				'type' => Controls_Manager::COLOR,
				'scheme' => [
					'type' => \Elementor\Core\Schemes\Color::get_type(),
					'value' => \Elementor\Core\Schemes\Color::COLOR_1,
				],
				'label_block'	=> false,
				'default' => '#fff',
				'condition' => [
					'branding' => '1',
					'branding_type' => 'text'
				]
			]
		);

		$this->add_control(
			'branding_background_color',
			[
				'label' 		=> esc_html__( 'Background Color', 'h5vp' ),
				'type' => Controls_Manager::COLOR,
				'scheme' => [
					'type' => \Elementor\Core\Schemes\Color::get_type(),
					'value' => \Elementor\Core\Schemes\Color::COLOR_1,
				],
				'label_block'	=> false,
				'default' => '#1aafff',
				'condition' => [
					'branding' => '1',
					'branding_type' => 'text'
				]
			]
		);

		$this->add_control(
			'branding_font_size',
			[
				'label' 		=> __( 'Font Size', 'h5vp' ),
				'type'			=> Controls_Manager::SLIDER,
				'size_units' 	=> [ 'px', 'rem'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 60,
						'step' => 1,
					],
					'rem' => [
						'min' => 0,
						'max' => 20,
						'step' => 0.1,
					]
				],
				'default' => [
					'unit' => 'px',
					'size' => 20,
				],
				// 'show_label' => false,
				'condition' => [
					'branding' => '1',
					'branding_type' => 'text'
				]
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'endscreen',
			[
				'label' => esc_html__( 'End Screen', 'h5vp' ),
				'tab' 	=> Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'endscreen_enable',
			[
				'label' => __( 'Enable End Screen', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Text', 'h5vp' ),
				'label_off' => __( 'Logo', 'h5vp' ),
				'return_value' => '1',
				'default' => '',
			]
		);

		$this->add_control(
			'endscreen_text',
			[
				'label' 		=> esc_html__( 'Text', 'h5vp' ),
				'type' 			=> Controls_Manager::TEXT,
				'default' => '',
				'label_block' => true,
				'condition' => [
					'endscreen_enable' => '1'
				]
			]
		);

		$this->add_control(
			'endscreen_btnText',
			[
				'label' 		=> esc_html__( 'Button Text', 'h5vp' ),
				'type' 			=> Controls_Manager::TEXT,
				'default' => '',
				'label_block' => true,
				'condition' => [
					'endscreen_enable' => '1'
				]
			]
		);

		$this->add_control(
			'endscreen_link',
			[
				'label' 		=> esc_html__( 'Button URL', 'h5vp'),
				'type' 			=> Controls_Manager::TEXT,
				'default' => '',
				'label_block' => true,
				'condition' => [
					'endscreen_enable' => '1'
				]
			]
		);

		// $this->add_control(
		// 	'endscreen_text_color',
		// 	[
		// 		'label' 		=> esc_html__( 'End Screen Text Color', 'h5vp' ),
		// 		'type' => Controls_Manager::COLOR,
		// 		'scheme' => [
		// 			'type' => \Elementor\Core\Schemes\Color::get_type(),
		// 			'value' => \Elementor\Core\Schemes\Color::COLOR_1,
		// 		],
		// 		'label_block'	=> false,
		// 		'selectors' => [
		// 			'{{WRAPPER}} .endscreen_content a' => 'color:{{VALUE}};',
		// 		],
		// 		'default' => '#1aafff',
		// 		'condition' => [
		// 			'endscreen_enable' => '1'
		// 		]
		// 	]
		// );

		$this->end_controls_section();

		// Subtitle/Captions
		$this->start_controls_section(
			'captions_section',
			[
				'label' => esc_html__( 'Subtitle/Captions', 'h5vp' ),
				'tab' 	=> Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'label', [
				'label' => __( 'Language', 'h5vp' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => "Language",
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'caption_file', [
				'label' => __( 'File', 'h5vp' ),
				'type' => 'b-select-file',
				'label_block' => true,
			]
		);

		$this->add_control(
			'captions',
			[
				'label' => __( 'Captions', 'h5vp' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'title_field' => '{{{ label }}}',
			]
		);

		$this->add_control(
			'captionEnabled',
			[
				'label' => __( 'Caption enabled by default (Experimental)', 'h5vp' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => true,
				'default' => false,
			]
		);

		$this->end_controls_section();
		

		// Quality
		$this->start_controls_section(
			'quality_section',
			[
				'label' => esc_html__( 'Quality', 'h5vp' ),
				'tab' 	=> Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'size', [
				'label' => __( 'Size', 'h5vp' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => "720",
				// 'label_block' => true,
			]
		);

		$repeater->add_control(
			'video_file', [
				'label' => __( 'File', 'h5vp' ),
				'type' => 'b-select-file',
				'label_block' => true,
			]
		);

		$this->add_control(
			'quality',
			[
				'label' => __( 'Quality', 'h5vp' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'title_field' => '{{{ size }}}',
			]
		);

		$this->end_controls_section();


		$this->start_controls_section(
			'chapters_section',
			[
				'label' => esc_html__( 'Chapter', 'h5vp' ),
				'tab' 	=> Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'time', [
				'label' => __( 'Time', 'h5vp' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( '00:00', 'h5vp' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'name', [
				'label' => __( 'Name', 'h5vp' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( 'Chapter Name', 'h5vp' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'chapters',
			[
				'label' => __( 'Chapters', 'h5vp' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [],
				'title_field' => '{{{ name }}}',
			]
		);


		$this->end_controls_section();

		// Player Mode and Player Size Options


	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function render() {
		$s = $this->get_settings_for_display();

		$provider = $s['video_source'];
		$classes = 'h5vp_player';

		$options = [];
		$protected = (boolean)$s['protected'];
		$video = [];
		$video_source = $s['source'];
		if($provider === 'youtube' || $provider === 'vimeo'){
			$video_source =  $s['source_youtube_vimeo'];
		}
		$video_poster = $s['poster'];
		
		// google vast url
		$tagUrl = $s['vast_url'] ? $s['vast_url'] : false;
		$ads = [];
		if($tagUrl){
			$tagUrl = str_replace('&amp;', '&', $tagUrl);
			$ads = ['enabled' => true,'tagUrl' => trim($tagUrl)];
		}else { $ads = ['enabled' => false];}
	  
		$controls = [
			'play-large' => $s['play-large'] === 'play-large' ? 'show' : 'hide' ,
			'restart' => $s['restart'] === 'restart' ? 'show' : 'hide',
			'rewind' => $s['rewind'] === 'rewind' ? 'show' : 'hide',
			'play' => $s['play'] === 'play' ? 'show' : 'hide' ,
			'fast-forward' => $s['fast-forward'] === 'fast-forward' ? 'show' : 'hide' ,
			'progress' => $s['progress'] === 'progress' ? 'show' : 'hide' ,
			'current-time' => $s['current-time'] === 'current-time' ? 'show' : 'hide' ,
			'duration' => $s['duration'] === 'duration' ? 'show' : 'hide' ,
			'mute' => $s['mute'] === 'mute' ? 'show' : 'hide' ,
			'volume' => $s['volume'] === 'volume' ? 'show' : 'hide' ,
			'captions' => 'show',
			'settings' => $s['settings'] === 'settings' ? 'show' : 'hide' ,
			'pip' => $s['pip'] === 'pip' ? 'show' : 'hide' ,
			'airplay' => $s['airplay'] === 'airplay' ? 'show' : 'hide' ,
			'download' => $s['download'] === 'download' ? 'show' : 'hide' ,
			'fullscreen' => $s['fullscreen'] === 'fullscreen' ? 'show' : 'hide' ,
		]; 

		
		$final_controls = [];
		foreach($controls as $key => $value) {
			if($value === 'show') {
				array_push($final_controls, $key);
			}
		}

		$chapters = self::i($s, 'chapters');
		$markers = [];
        foreach($chapters as $key => $value){
            $value['label'] = $value['name'];
            array_push($markers, $value);
        }
	  
		$options = [
			'controls' => $final_controls,
			'tooltips' => [
				'controls' => true,
				'seek' => true,
			],
			'seekTime' => (int) $s['seek_time'],
			'loop' => [
				'active' => (boolean)$s['repeat'],
			],
			'autoplay' => (boolean)$s['autoplay'],
			'muted' => (boolean)$s['muted'],
			'hideControls' => (boolean)$s['auto_hide_control'],
			'resetOnEnd' => (boolean)$s['reset_on_end'],
			'ads' => $ads,
			'captions' => [
				'active' => true,
				'update' => true,
			],
			'markers' => [
				'enabled' => true,
				'points' => $markers,
			],
			'preload' => self::i($s, 'preload'),
		];

		$options = [
			'uniqueId' => 'h5vp'.uniqid(),
			'options' => $options,
			'source' => $video_source,
			'qualities' => $s['quality'],
			'captions' => $s['captions'],
			'features' => [
                'overlay' => [
                    'enabled' => self::i($s, 'branding') == 1,
                    'items' => [
                       [
						'type' => self::i($s, 'branding_type'),
                        'color' => self::i($s, 'branding_text_color'),
                        'hoverColor' => '#fff',
                        'fontSize' => self::i($s, 'branding_font_size', 'size') . self::i($s, 'branding_font_size', 'unit'),
                        'link' => self::i($s, 'branding_link'),
                        'logo' => self::i($s, 'branding_logo'),
                        'position' => self::i($s, 'branding_position'),
                        'text' => self::i($s, 'branding_text'),
                        'backgroundColor' => self::i($s, 'branding_background_color'),
                        'opacity' => '0.7',
                       ]
                    ]

                ],
                'sticky' => [
                    'enabled' => false,
                    'position' => 'top_left',
                ],
                // 'chapters' => [],
                'watermark' => [
                    'enabled' => false,
                ],
                'thumbInPause' => [
                    'enabled' => false,
                    'type' => 'default'
                ],
                'endScreen' => [
                    'enabled' => self::i($s, 'endscreen_enable'),
                    'text' =>  self::i($s, 'endscreen_text'),
                    'btnText' =>  self::i($s, 'endscreen_btnText'),
                    'btnLink' => self::i($s, 'endscreen_link')
                ],
                'popup' => [
                    'enabled' => self::i($s, 'popup'),
                    'selector' => '',
                    'hasBtn'=> false,
                    'type' => 'poster',
                    'btnText' => 'Watch Video',
                    'btnStyle' => [],
                ],
				'hideYoutubeUI' => (boolean)self::i($s, 'hideYoutubeUI')
            ],
            'propagans' => '',
            'captionEnabled' => false,
            'disableDownload' => false,
            'disablePause' => false,
            'startTime' => 0,
            'saveState' => false,
            'protected' => false,
            'source' => $video_source,
            'poster' => $video_poster,
            'styles' => [
				'plyr_wrapper' => [
					'width' => self::i($s, 'width', 'size').self::i($s, 'width', 'unit')
				]
			],
		];

		$popup = (boolean)$s['popup'];

		if($popup){
			$classes .= ' h5vp_popup_wrapper popup_close';
		}

		$data = DefaultArgs::parseArgs($options);
		
		echo VideoTemplate::html($data);
		return false;
	}

	function scramble($do = 'encode', $data){
		$originalKey = 'abcdefghijklmnopqrstuvwxyz1234567890';
		$key = "z1ntg4ihmwj5cr09byx8spl7ak6vo2q3eduf";
		$resultData = '';
		if($do == 'encode'){
			if($data != ''){
				$length = strlen($data);
				for($i = 0; $i < $length; $i++){
					$position = strpos($originalKey, $data[$i]);
					if($position !== false){
						$resultData .= $key[$position];
					}else {
						$resultData .= $data[$i];
					}
				}
			}
		}

		if($do == 'decode'){
			if($data != ''){
				$length = strlen($data);
				for($i = 0; $i < $length; $i++){
					$position = strpos($key, $data[$i]);
					if($position !== false){
						$resultData .= $originalKey[$position];
					}else {
						$resultData .= $data[$i];
					}
				}
			}
		}

		return $resultData;
	}

	public static function i($array, $key1, $key2 = '', $default = false){
        if(isset($array[$key1][$key2])){
            return $array[$key1][$key2];
        }else if (isset($array[$key1])){
            return $array[$key1];
        }
        return $default;
    }

}
