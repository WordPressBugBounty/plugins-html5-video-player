<?php


function h5vp_add_duplicate_button($actions, $post){
    if($post->post_type == 'videoplayer'){
        $post_type = get_post_type_object( $post->post_type );
        $label = sprintf( 'Duplicate %s', $post_type->labels->singular_name );
        $nonce = wp_create_nonce( 'h5vp_duplicate_nonce' );
        $actions['duplicate_player'] = '<a class="h5vp_duplicate_player" security="'.$nonce.'" href="#" data-postid="'.$post->ID.'">'.$label.'</a>';
    }
    if($post->post_type == 'h5vpplaylist'){
        $post_type = get_post_type_object( $post->post_type );
        $label = sprintf( 'Duplicate %s', $post_type->labels->singular_name );
        $nonce = wp_create_nonce( 'h5vp_duplicate_nonce' );
        $actions['duplicate_player'] = '<a class="h5vp_duplicate_player" security="'.$nonce.'" href="#" data-postid="'.$post->ID.'">'.$label.'</a>';
    }
    return $actions;
}
add_action('post_row_actions', 'h5vp_add_duplicate_button', 10, 2);

/**
 * duplicate player
 */
function h5vp_dulicate_player(){
    global $wpdb;
    $main_id = $_POST['postid'];
    $security = $_POST['security'];

    $newPost = get_post($main_id, 'ARRAY_A');

    $newPost['post_title'] = $newPost['post_title'].'-Copy';
    $newPost['post_name'] = $newPost['post_name'].'-copy';
    $newPost['post_status'] = 'draft';

    $newPost['post_date'] = date('Y-m-d H:i:s', current_time('timestamp',0));
	$newPost['post_date_gmt'] = date('Y-m-d H:i:s', current_time('timestamp',1));
	$newPost['post_modified'] = date('Y-m-d H:i:s', current_time('timestamp',0));
	$newPost['post_modified_gmt'] = date('Y-m-d H:i:s', current_time('timestamp',1));

	// Remove some of the keys
	unset( $newPost['ID'] );
	unset( $newPost['guid'] );
    unset( $newPost['comment_count'] );
    
    $newPostId = wp_insert_post($newPost);

    $custom_fields = get_post_custom( $main_id );
    foreach ( $custom_fields as $key => $value ) {
	  if( is_array($value) && count($value) > 0 ) {
			foreach( $value as $i=>$v ) {
				$result = $wpdb->insert( $wpdb->prefix.'postmeta', array(
					'post_id' => $newPostId,
					'meta_key' => $key,
					'meta_value' => $v
			));
		}
	}
  }

    update_post_meta($newPostId, 'h5vp_total_views', 0);

    echo esc_html($newPostId);
    die();
}
add_action('wp_ajax_h5vp_dulicate_player', 'h5vp_dulicate_player');


function h5vp_duplicate_notice(){

    if(isset($_POST['name'])){
        echo '<div class="notice notice-success is-dismissible">
        <p>'. esc_html($_POST['name']).'</p>
    </div>';
    }
    $result = $_GET['duplicate'] ?? false;
    $result = sanitize_text_field($result) ?? false;
    if($result == 'success'){
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('Duplicated Successfully', 'h5vp') ?></p>
    </div>
    <?php
    }
}
add_action('admin_notices', 'h5vp_duplicate_notice');