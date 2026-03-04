<?php

namespace H5VP\Services;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
    $meta = h5vp_getPostMeta($atts['id'], 'h5vp_playlist');
    wp_enqueue_script('html5-player-playlist');
    wp_enqueue_style('html5-player-playlist');
    wp_enqueue_script('bplugins-owl-carousel');
    wp_enqueue_style('bplugins-owl-carousel');


    if (is_array($data['videos'])) {
      foreach ($data['videos'] as $key => $video) {
        if (strpos($video['video_source'], '.m3u8') !== false) {
          wp_enqueue_script('h5vp-hls');
        }
        if (strpos($video['video_source'], '.mpd') !== false) {
          wp_enqueue_script('h5vp-dash');
        }
      }
    }

    $unique_id = wp_unique_id('h5vp_playlist_');
    ob_start();

?>

    <style>
      .h5vp_playlist .plyr {
        --plyr-color-main: <?php echo esc_attr(DefaultArgs::brandColor()); ?>;
      }
    </style>

    <div class="h5vp_playlist <?php echo esc_attr($unique_id) ?>" data-attributes="<?php echo esc_attr(wp_json_encode($data)) ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('wp_ajax')) ?>"></div>

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
      'preload' => 'metadata',
      'ios_native' => 'true',
      'controls' => null,
      'hideControls' => null,
      'playsinline' => true,
      'seek_time' => 10,
      'ratio' => null,
      'thumb_in_pause' => false,
      'watermark' => false,
      'watermark_type' => 'email',
      'watermark_text' => '',
      'watermark_color' => '#f00',
      // 'password_protected' => false,
      // 'password' => '',
      // 'password_error_message' => '',
      // 'password_btn_text' => '',
      // 'password_btn_color' => '#f00',
      // 'password_btn_bg_color' => '#fff',
      // 'password_heading' => '',
      'sticky' => false,
      'sticky_position' => 'top_right',
      'play_when_visible' => false,
      'disable_pause' => false,

      'hide_youtube_ui' => false,

      'start_time' => null,
      'hide_loading_placeholder' => false,
      'additional_id' => '',

      'who_can_see_this_video' => 'everyone',
      'allowed_roles' => '',
      'logged_out_user_text' => 'This video is only for registered users. Please login to watch the video.',
      
      'popup' => false,
      'popup_type' => 'button',
      'popup_btn_text' => 'Watch Video',
      'popup_btn_color' => '#fff',
      'popup_btn_bg_color' => '#006BA1',
    );
  }
}
