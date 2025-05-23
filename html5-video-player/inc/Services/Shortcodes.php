<?php

namespace H5VP\Services;

use H5VP\Helper\Block;


class Shortcodes
{

  public function __construct()
  {
    $option = h5vp_get_option('h5vp_option', []);
    if (!$option('h5vp_disable_video_shortcode', false, true)) {
      add_shortcode('video', [$this, 'html5_video'], 10, 2);
    }
    add_shortcode('video_player', [$this, 'video_player'], 10, 2);
    add_shortcode('html5_video', [$this, 'html5_video'], 10, 2);
  }

  public function register() {}

  public function html5_video($atts)
  {
    extract(shortcode_atts(array(
      'id' => null,
    ), $atts));

    $post_type = get_post_type($id);
    $post = get_post($id);
    $isGutenberg = get_post_meta($id, 'isGutenberg', true);

    if ($post_type !== 'videoplayer') {
      return false;
    }
    if (post_password_required($post)) {
      return get_the_password_form($post);
    }
    switch ($post->post_status) {
      case 'publish':
        return $this->video_player_shortcode_content($post, $isGutenberg);
      case 'private':
        if (current_user_can('read_private_posts')) {
          return $this->video_player_shortcode_content($post, $isGutenberg);
        }
        return '';
      case 'draft':
      case 'pending':
      case 'future':
        if (current_user_can('edit_post', $post_id)) {
          return $this->video_player_shortcode_content($post, $isGutenberg);
        }
        return '';
      default:
        return '';
    }
  }

  public function video_player_shortcode_content($post, $isGutenberg)
  {
    if ($isGutenberg) {
      $blocks = parse_blocks($post->post_content);
      if (isset($blocks[0]['innerBlocks'][0])) {
        return render_block($blocks[0]['innerBlocks'][0]);
      }
      return null;
    }
    $block = Block::getInstance()->classic_to_gutenberg_block($post->ID);
    return render_block($block);
  }

  public function video_player($atts)
  {
    $attrs = shortcode_atts($this->video_player_attrs(), $atts);

    if ($attrs['file'] == null && $attrs['src'] == null && $attrs['mp4'] == null) {
      return "No Video Added";
    } else {
      return render_block(Block::getInstance()->video_player_to_gutenberg_block($attrs));
    }
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
      'reset_on_end' => false
    );
  }

  /**
   * Maybe switch provider if the url is overridden
   */
  protected function getProvider($src)
  {
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
