<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
use H5VP\Helper\DefaultArgs;

$classes = 'wp-block-html5-player-video html5_video_players';
$attributes = h5vp_process_block_attributes($attributes);

$attributes = apply_filters('h5vp_block_attributes', $attributes);

$preset = null;
if ($attributes['presetId'] ?? false) {
    $presetModel = new \H5VP\Model\Preset();
    $preset = $presetModel->get_preset_by_id($attributes['presetId']);
}

if ($attributes['onlyLoggedIn']['whoCanSeeThisVideo'] === 'logged_in') {
    if (!is_user_logged_in()) {
        $login_url = wp_login_url(get_permalink());
        echo '<div class="h5vp-loggedin-message">' . esc_html($attributes['onlyLoggedIn']['message']) . '<a href="' . esc_url($login_url) . '"> Login to watch</a></div>';
        return;
    } else {
        $user = wp_get_current_user();
        $allowed_roles = $attributes['onlyLoggedIn']['allowedRoles'];
        if (!empty($allowed_roles) && !array_intersect($allowed_roles, $user->roles)) {
            echo "<p>" . esc_html__('Your role does not have permission to watch this video.', 'h5vp') . "</p>";
            return;
        }
    }
}

if ($attributes['onlyLoggedIn']['whoCanSeeThisVideo'] == 'logged_out' && is_user_logged_in()) {
    return;
}

$video_id = '';
if (class_exists('\H5VP\Model\Video')) {
    $videoInstance = new \H5VP\Model\Video();
    $video_id = $videoInstance->get_id(['src' => $attributes['source']]);
    $attributes['video_id'] = $video_id;
}


$attributes['features']['saveState'] = $attributes['saveState'] ?? false;

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
    $classes .= ' h5vp-hls-video';
}
if (strpos($attributes['source'], '.mpd') !== false) {
    wp_enqueue_script('h5vp-dash');
    $classes .= ' h5vp-dash-video';
}


if (strpos($attributes['source'], '.m3u8') !== false || strpos($attributes['source'], '.mpd') !== false) {
    $attributes['source'] = xorEncode($attributes['source'], $attributes['uniqueId']);
    $attributes['streaming'] = true;
}

?>

<div data-video-id="<?php echo esc_attr($video_id); ?>" class='<?php echo esc_attr($classes) ?>' <?php echo
          wp_kses_data(get_block_wrapper_attributes()); ?> data-nonce="
    <?php echo esc_attr(wp_create_nonce('wp_ajax')) ?>" data-attributes="
    <?php echo esc_attr(wp_json_encode($attributes)) ?>" data-preset="
    <?php echo esc_attr(wp_json_encode($preset)) ?>">
    <?php if (!$attributes['features']['hideLoadingPlaceholder'] ?? false) { ?>
        <style>
            .preload_poster svg {
                background: var(--plyr-video-control-background-hover, var(--plyr-color-main, var(--plyr-color-main,
                                <?php echo esc_attr(DefaultArgs::brandColor()); ?>
                            )));
                border: 0;
                border-radius: 100%;
                left: 50%;
                opacity: .9;
                padding: calc(var(--plyr-control-spacing, 8px) * 1.5);
                position: absolute;
                top: 50%;
                transform: translate(-50%, -50%);
                transition: .3s;
                z-index: 2;
                box-sizing: content-box;
                fill: #fff;
            }

            .preload_poster {
                display:
                    <?php echo esc_attr($attributes['features']['popup']['enabled'] ?? false ? 'none' : 'block') ?>
            }
        </style>
        <div class="preload_poster"
            style="overflow:hidden;aspect-ratio:<?php echo esc_attr($attributes['options']['ratio'] ?? '16/9'); ?>;background-image:url(<?php echo esc_url($attributes['poster'] ?? '') ?>)">
            <svg width="24px" height="24px" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M4.79062 2.09314C4.63821 1.98427 4.43774 1.96972 4.27121 2.05542C4.10467 2.14112 4 2.31271 4 2.5V12.5C4 12.6873 4.10467 12.8589 4.27121 12.9446C4.43774 13.0303 4.63821 13.0157 4.79062 12.9069L11.7906 7.90687C11.922 7.81301 12 7.66148 12 7.5C12 7.33853 11.922 7.18699 11.7906 7.09314L4.79062 2.09314Z" />
            </svg>
        </div>
        <?php
    } ?>
</div>