<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

function h5vp_add_duplicate_button($actions, $post)
{
    if ($post->post_type == 'videoplayer') {
        $post_type = get_post_type_object($post->post_type);
        $label = sprintf('Duplicate %s', $post_type->labels->singular_name);
        $nonce = wp_create_nonce('h5vp_duplicate_nonce');
        $actions['duplicate_player'] = '<a class="h5vp_duplicate_player" security="' . $nonce . '" href="#" data-postid="' . $post->ID . '">' . $label . '</a>';
    }
    if ($post->post_type == 'h5vpplaylist') {
        $post_type = get_post_type_object($post->post_type);
        $label = sprintf('Duplicate %s', $post_type->labels->singular_name);
        $nonce = wp_create_nonce('h5vp_duplicate_nonce');
        $actions['duplicate_player'] = '<a class="h5vp_duplicate_player" security="' . $nonce . '" href="#" data-postid="' . $post->ID . '">' . $label . '</a>';
    }
    return $actions;
}
add_action('post_row_actions', 'h5vp_add_duplicate_button', 10, 2);

/**
 * duplicate player
 */
function h5vp_dulicate_player()
{
    global $wpdb;
    $main_id = sanitize_text_field(wp_unslash($_POST['postid'] ?? ''));
    $security = sanitize_text_field(wp_unslash($_POST['security'] ?? ''));

    if (!wp_verify_nonce($security, 'h5vp_duplicate_nonce') || !current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    $newPost = get_post($main_id, 'ARRAY_A');

    $newPost['post_title'] = $newPost['post_title'] . '-Copy';
    $newPost['post_name'] = $newPost['post_name'] . '-copy';
    $newPost['post_status'] = 'draft';

    $newPost['post_date'] = current_time('mysql', false);
    $newPost['post_date_gmt'] = current_time('mysql', true);
    $newPost['post_modified'] = current_time('mysql', false);
    $newPost['post_modified_gmt'] = current_time('mysql', true);

    // Remove some of the keys
    unset($newPost['ID']);
    unset($newPost['guid']);
    unset($newPost['comment_count']);

    $newPostId = wp_insert_post($newPost);

    $custom_fields = get_post_custom($main_id);
    foreach ($custom_fields as $key => $value) {
        if (is_array($value) && count($value) > 0) {
            foreach ($value as $i => $v) {
                add_post_meta($newPostId, $key, maybe_unserialize($v));

            }
        }
    }

    update_post_meta($newPostId, 'h5vp_total_views', 0);

    wp_send_json_success(array(
        'message' => 'Player duplicated successfully',
        'post_id' => $newPostId
    ));
}
add_action('wp_ajax_h5vp_dulicate_player', 'h5vp_dulicate_player');


function h5vp_duplicate_notice()
{
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));

    if ($name) {
        echo '<div class="notice notice-success is-dismissible">
        <p>' . esc_html($name) . '</p>
    </div>';
    }
    if (sanitize_text_field(wp_unslash($_GET['duplicate'] ?? false)) == 'success') {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Duplicated Successfully', 'h5vp') ?></p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'h5vp_duplicate_notice');
