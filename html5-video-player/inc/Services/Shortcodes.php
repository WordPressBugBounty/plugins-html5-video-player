<?php

namespace H5VP\Services;

use H5VP\Services\QuickPlayerTemplate;
use H5VP\Services\AnalogSystem;
use H5VP\Services\AdvanceSystem;
use H5VP\Helper\Functions as Utils;


class Shortcodes{

  public function register(){
    $option = get_option('h5vp_option');
    if(!Utils::isset($option, 'h5vp_disable_video_shortcode', false)){
      add_shortcode('video', [$this, 'shortcodeVideo'], 10, 2);
    }
    add_shortcode('video_player', [$this, 'shortcodeVideoPlayer'], 10, 2);
    add_shortcode('html5_video', [$this, 'shortcodeVideo'], 10, 2);
    // add_shortcode('video_playlist', [$this, 'videoPlaylist']);
  }

  public function shortcodeVideo($atts, $content){
    extract(shortcode_atts(array(
      'id' => null,
    ), $atts));

    $post_type = get_post_type($id);
    // $content = get_post($id);
    $isGutenberg = get_post_meta($id, 'isGutenberg', true);

    ob_start(); 
    
    if($post_type !== 'videoplayer'){
      return false;
    }
    if($isGutenberg){
      echo( AdvanceSystem::html($id));
    }else {
      echo AnalogSystem::html($id);
    }
    
    return ob_get_clean(); 
  }

  public function shortcodeVideoPlayer($atts){
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

  public function videoPlaylist($atts){
    if(!isset($atts['id'])){
      return false;
    }
    
    ob_start();

    echo  AnalogSystem::playlistHtml($atts['id']);

    $output = ob_get_contents();
    ob_end_clean();
    return $output;
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