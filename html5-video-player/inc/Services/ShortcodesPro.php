<?php

namespace H5VP\Services;

use H5VP\Services\AnalogSystem;
use H5VP\Helper\DefaultArgs;


class ShortcodesPro extends Shortcodes
{

  public function __construct()
  {
    parent::__construct();
    add_shortcode('video_playlist', [$this, 'video_playlist']);
  }

  public function register() {}


  public function video_playlist($atts)
  {
    if (!isset($atts['id'])) {
      return false;
    }


    $data = AnalogSystem::parsePlaylistData($atts['id']);
    wp_enqueue_script('html5-player-playlist');
    wp_enqueue_style('html5-player-playlist');
    wp_enqueue_script('bplugins-owl-carousel');
    wp_enqueue_style('bplugins-owl-carousel');

    ob_start(); ?>

    <style>
      .h5vp_playlist .plyr {
        --plyr-color-main: <?php echo esc_attr(DefaultArgs::brandColor()); ?>;
      }
    </style>

    <div class="h5vp_playlist" data-attributes="<?php echo esc_attr(wp_json_encode($data)) ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('wp_ajax')) ?>"></div>

<?php

    return ob_get_clean();
  }

  public function video_player_attrs()
  {
    return array(
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
    );
  }
}
