<?php

if (!defined('ABSPATH'))
	exit;
/*-------------------------------------------------------------------------------*/
/*   AJAX Get Slider List
/*-------------------------------------------------------------------------------*/
function h5vp_pro_grab_slider_list_ajax()
{
	check_ajax_referer('h5vp_ajax', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_die();
	}


	$list = array();

	global $post;

	$args = array(
		'post_type' => 'videoplayer',
		'order' => 'ASC',
		'posts_per_page' => -1,
		'post_status' => 'publish'

	);

	$myposts = get_posts($args);
	foreach ($myposts as $post):
		setup_postdata($post);
		$list[$post->ID] = array('val' => $post->ID, 'title' => esc_html($post->post_title));
	endforeach;

	echo wp_json_encode($list); //Send to Option List ( Array )
	wp_die();


}

add_action('wp_ajax_h5vp_pro_grab_slider_list_ajax', 'h5vp_pro_grab_slider_list_ajax');

/*-------------------------------------------------------------------------------*/
/*   Frontend Register JS & CSS
/*-------------------------------------------------------------------------------*/
function h5vp_pro_reg_script()
{
	wp_register_style('h5vp-tinymcecss', plugins_url('tinymce/tinymce.css', dirname(__FILE__)), false, H5VP_VER, 'all');
	wp_register_script('h5vp-tinymcejs', plugins_url('tinymce/tinymce.js', dirname(__FILE__)), false, H5VP_VER, true);
	wp_localize_script('h5vp-tinymcejs', 'h5vp_tinymce_vars', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('h5vp_ajax'),
	));
}
add_action('admin_init', 'h5vp_pro_reg_script');

/*-------------------------------------------------------------------------------*/
/*   Editor Assets — classic post editor only, for non-videoplayer post types.
/*   Uses the current screen instead of matching against $_SERVER['REQUEST_URI'].
/*-------------------------------------------------------------------------------*/
function h5vp_pro_editor_assets()
{
	$screen = get_current_screen();
	if (!$screen || 'post' !== $screen->base) {
		return;
	}
	if ('videoplayer' === $screen->post_type) {
		return;
	}

	wp_enqueue_style('h5vp-tinymcecss');
	wp_enqueue_script('h5vp-tinymcejs');

	add_action('admin_footer', 'h5vp_pro_popup_content');
}
add_action('current_screen', 'h5vp_pro_editor_assets');

/*-------------------------------------------------------------------------------*/
/*   Popup Content — registered to admin_footer only by h5vp_pro_editor_assets()
/*-------------------------------------------------------------------------------*/
function h5vp_pro_popup_content()
{
	?>
	<div id="h5vpmodal" style="display:none;">
		<div id="tinyform" style="width: 550px;">
			<form method="post">

				<div class="h5vp_input" id="h5vptinymce_select_slider_div">
					<label class="label_option" for="h5vptinymce_select_slider">Html5 Video Player</label>
					<select class="h5vp_select" name="h5vptinymce_select_slider" id="h5vptinymce_select_slider">
						<option id="selectslider" type="text" value="select">- Select Player -</option>
					</select>
					<div class="clearfix"></div>
				</div>

				<div class="h5vp_button">
					<input type="button" value="Insert Shortcode" name="h5vp_insert_scrt" id="h5vp_insert_scrt"
						class="button-secondary" />
					<div class="clearfix"></div>
				</div>

			</form>
		</div>
	</div>
	<?php
}
