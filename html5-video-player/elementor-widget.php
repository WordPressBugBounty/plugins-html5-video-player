<?php
use H5VP\Elementor\VideoPlayer;
use H5VP\Elementor\SelectFile;
final class Elementor_Addons {

	/**
	 * Plugin Version
	 *
	 * @since 1.0.0
	 *
	 * @var string The plugin version.
	 */
	const VERSION = '1.0.0';

	/**
	 * Minimum Elementor Version
	 *
	 * @since 1.0.0
	 *
	 * @var string Minimum Elementor version required to run the plugin.
	 */
	const MINIMUM_ELEMENTOR_VERSION = '2.0.0';

	/**
	 * Minimum PHP Version
	 *
	 * @since 1.0.0
	 *
	 * @var string Minimum PHP version required to run the plugin.
	 */
	const MINIMUM_PHP_VERSION = '7.0';

	/**
	 * Instance
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @static
	 *
	 * @var Elementor_Test_Extension The single instance of the class.
	 */
	private static $_instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @static
	 *
	 * @return Elementor_Test_Extension An instance of the class.
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function __construct() {

		//Register Frontend Script
		add_action( "elementor/frontend/after_register_scripts", [ $this, 'frontend_assets_scripts' ] );

		// Add Plugin actions
		add_action( 'elementor/widgets/register', [ $this, 'init_widgets' ] );

		add_action( 'elementor/controls/controls_registered', [ $this, 'init_controls' ] );

	}

	/**
	 * Init Controls
	 *
	 * Include control files and register them
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function init_controls($controls_manager) {

		// Include Widget files
		require_once( __DIR__ . '/inc/elementor-custom-control/b-select-file.php' );

		// Register controls
		$controls_manager->register( new SelectFile() );
	}


	/**
	 * Frontend script
	 */
	public function frontend_assets_scripts(){
		wp_register_script( 'bplugins-plyrio', plugin_dir_url( __FILE__ ). 'public/js/plyr-v3.7.8.js' , array('jquery'), '3.7.8', false );
		wp_register_script( 'html5-player-video-view-script', plugin_dir_url( __FILE__ ). 'dist/frontend.js' , array('jquery', 'bplugins-plyrio', 'react', 'react-dom', 'wp-util'), time(), true );
		
		wp_register_style( 'bplugins-plyrio', plugin_dir_url( __FILE__ ) . 'public/css/h5vp.css', array(), H5VP_PRO_VER, 'all' );
		wp_register_style( 'html5-player-video-style', plugin_dir_url( __FILE__ ). 'dist/frontend.css' , array('bplugins-plyrio'), H5VP_PRO_VER );

		wp_localize_script( 'html5-player-video-view-script', 'ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php'),
		));

		// wp_enqueue_script('html5-player-video-view-script');
        // wp_enqueue_style('html5-player-video-style');
		
	}

	/**
	 * Init Widgets
	 *
	 * Include widgets files and register them
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function init_widgets() {
		// Include Widget files
		$widget = 'VideoPlayer';

		if(h5vp_fs()->can_use_premium_code()){ 
			$widget = $widget.'Pro';
		}
		if(file_exists( __DIR__ . "/inc/Elementor/$widget.php" )){
			require_once( __DIR__ . "/inc/Elementor/$widget.php" );
		}

		$class = "\H5VP\Elementor\\".$widget;
		
		// Register widget
		\Elementor\Plugin::instance()->widgets_manager->register( new $class() );
	}
}

Elementor_Addons::instance();