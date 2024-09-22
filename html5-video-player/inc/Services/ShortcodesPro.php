<?php

namespace H5VP\Services;

use H5VP\Services\QuickPlayerTemplate;
use H5VP\Services\AnalogSystem;
use H5VP\Services\AdvanceSystem;
use H5VP\Helper\Functions as Utils;
use H5VP\Helper\DefaultArgs;


class Shortcodes{

  public function register(){
    $option = get_option('h5vp_option');
    if(!Utils::isset($option, 'h5vp_disable_video_shortcode', false)){
      add_shortcode('video', [$this, 'html5_video'], 10, 2);
    }
    add_shortcode('video_player', [$this, 'video_player'], 10, 2);
    add_shortcode('html5_video', [$this, 'html5_video'], 10, 2);
    add_shortcode('video_playlist', [$this, 'video_playlist']);
  }

  public function html5_video($atts, $content){
    extract(shortcode_atts(array(
      'id' => null,
    ), $atts));

    $post_type = get_post_type($id);
    // $content = get_post($id);
    $isGutenberg = get_post_meta($id, 'isGutenberg', true);

    ob_start(); 

    
   try {
    if($post_type !== 'videoplayer'){
      return false;
    }
    if($isGutenberg){
      echo( AdvanceSystem::html($id));
    }else {
      echo AnalogSystem::html($id);
    }
   } catch (\Throwable $th) {
    throw $th->getMessage();
   }


  
    
    return ob_get_clean(); 
  }

  public function video_player($atts){
	  $attrs = shortcode_atts(array(
        'file' => null,
        'source' => 'library',
        'poster' => '',
        'mp4' => null,
        'src' => null,
        'autoplay' => false,
        'reset_on_end' => false,
        'repeat' => false,
        'muted' => false,
        'width' => '',
        'preload' => null,
        'ios_native' => 'true',
        'controls' => null,
        'hideControls' => null
    ), $atts);

    
    ob_start();
    
    if ($attrs['file'] == null && $attrs['src'] == null && $attrs['mp4'] == null) {
        echo "No Video Added";
    } else {
      echo QuickPlayerTemplate::html($attrs);
    }

    return ob_get_clean();
  }

  public function video_playlist($atts){
    if(!isset($atts['id'])){
      return false;
    }
    
    $data = AnalogSystem::parsePlaylistData($atts['id']);
    wp_enqueue_script('html5-player-playlist');
    wp_enqueue_style('html5-player-playlist');
		
    ob_start(); ?>

    <style>
      .h5vp_playlist .plyr {
        --plyr-color-main: <?php echo esc_attr(DefaultArgs::brandColor()); ?>;
      }
      </style>

      <div class="h5vp_playlist"  data-attributes="<?php echo esc_attr(wp_json_encode($data)) ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('wp_ajax')) ?>"></div>

      <?php

    return ob_get_clean();
  }

  public function html5Player($atts){
    if(!isset($atts['id'])){
      return false;
    }
    $post_type = get_post_type($atts['id']);
    ob_start();
    if($post_type === 'html5_video'){
      echo AdvanceSystem::html($atts['id']);
    }
    $output = ob_get_contents();
    ob_get_clean();
    return $output;
  }


  /**
   * Maybe switch provider if the url is overridden
   */
  protected function getProvider($src) {
    $provider = 'self-hosted';

    if (!empty($src)) {
      $yt_rx = '/^((?:https?:)?\/\/)?((?:www|m)\.)?((?:youtube\.com|youtu.be))(\/(?:[\w\-]+\?v=|embed\/|v\/)?)([\w\-]+)(\S+)?$/';
      $has_match_youtube = preg_match($yt_rx, $src, $yt_matches);

      if ($has_match_youtube) {
        return 'youtube';
      }

      $vm_rx = '/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([‌​0-9]{6,11})[?]?.*/';
      $has_match_vimeo = preg_match($vm_rx, $src, $vm_matches);

      if ($has_match_vimeo) {
        return 'vimeo';
      }

      if (strpos($src, 'https://vz-') !== false && strpos($src, 'b-cdn.net') !== false) {
        return 'bunny';
      }
    }

    return $provider;
  }
}
