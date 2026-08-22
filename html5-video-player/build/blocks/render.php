<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!isset($attributes['source']) || empty($attributes['source'])) {
    return;
}
$attributes = h5vp_process_block_attributes($attributes);

$attributes = apply_filters('h5vp_block_attributes', $attributes);

h5vp_maybe_enqueue_hls($attributes['source'] ?? '');
?>
<?php
$h5vp_wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'wp-block-html5-player-video html5_video_players',
]);
?>

<div data-video-id="<?php echo esc_attr($attributes['video_id'] ?? ''); ?>" <?php echo wp_kses_data($h5vp_wrapper_attributes); ?> data-attributes="<?php echo esc_attr(wp_json_encode($attributes)) ?>">
</div>