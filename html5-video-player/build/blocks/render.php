<?php

$classes = 'wp-block-html5-player-video html5_video_players';
$attributes = h5vp_process_block_attributes($attributes);

$attributes = apply_filters('h5vp_block_attributes', $attributes);

if (h5vp_fs()->is__premium_only() && isset($attributes['seo']['duration']) && !empty($attributes['seo']['duration'])) {
    add_action('wp_head', function () use ($attributes) {
?>
        <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "VideoObject",
                "name": "<?php echo esc_html($attributes['seo']['name'] ?? get_the_title()); ?>",
                "description": "<?php echo esc_html($attributes['seo']['description'] ?? get_the_excerpt()) ?>",
                "thumbnailUrl": "<?php echo esc_url($attributes['poster'] ?? '') ?>",
                "uploadDate": "<?php echo esc_html(get_the_date('c')) ?>",
                "contentUrl": "<?php echo esc_url($attributes['source']) ?>",
                "embedUrl": "<?php echo esc_url(get_permalink()); ?>",
                "duration": "<?php echo esc_html(h5vp_convert_duration_to_iso8601($attributes['seo']['duration'])); ?>"
            }
        </script>
<?php
    });
}
if (strpos($attributes['source'], '.m3u8') !== false) {
    wp_enqueue_script('h5vp-hls');
}
if (strpos($attributes['source'], '.mpd') !== false) {
    wp_enqueue_script('h5vp-dash');
}
if (strpos($attributes['source'], '.m3u8') !== false || strpos($attributes['source'], '.mpd') !== false) {
    $attributes['source'] = xorEncode($attributes['source'], $attributes['uniqueId']);
}

?>

<div class='<?php echo esc_attr($classes) ?>' <?php echo get_block_wrapper_attributes(); ?> data-nonce="<?php echo esc_attr(wp_create_nonce('wp_ajax')) ?>" data-attributes="<?php echo esc_attr(wp_json_encode($attributes)) ?>">
    <div class="preload_poster" style="overflow:hidden;aspect-ratio:<?php echo esc_attr($attributes['options']['ratio']);  ?>;">
        <svg width="36px" height="36px" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4.79062 2.09314C4.63821 1.98427 4.43774 1.96972 4.27121 2.05542C4.10467 2.14112 4 2.31271 4 2.5V12.5C4 12.6873 4.10467 12.8589 4.27121 12.9446C4.43774 13.0303 4.63821 13.0157 4.79062 12.9069L11.7906 7.90687C11.922 7.81301 12 7.66148 12 7.5C12 7.33853 11.922 7.18699 11.7906 7.09314L4.79062 2.09314Z" fill="#fff" />
        </svg>
    </div>
</div>

<?php
