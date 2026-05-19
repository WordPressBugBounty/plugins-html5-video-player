<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!isset($attributes['source']) || empty($attributes['source'])) {
    return;
}
$attributes = h5vp_process_block_attributes($attributes);

$attributes = apply_filters('h5vp_block_attributes', $attributes);

?>

<div data-video-id="<?php echo esc_attr($attributes['video_id'] ?? ''); ?>"
    class='wp-block-html5-player-video html5_video_players' <?php echo wp_kses_data(get_block_wrapper_attributes()); ?>
    data-attributes="<?php echo esc_attr(wp_json_encode($attributes)) ?>">
</div>