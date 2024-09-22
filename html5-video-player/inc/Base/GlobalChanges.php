<?php
namespace H5VP\Base;

class GlobalChanges{

    public function register(){
        if(is_admin()){
            add_filter('post_row_actions', [$this, 'removeRowAction'], 10, 2);
            add_action('admin_head-post.php', [$this, 'hideAction']);
            add_action('admin_head-post-new.php', [$this, 'hideAction']);
            add_filter('gettext', [$this, 'changePublishText'], 10, 2);
            add_filter('post_updated_messages', [$this, 'changeUpdateMessage']);

            add_action('admin_init', [$this, 'wpLoaded'], 1);

            if ( version_compare( $GLOBALS['wp_version'], '5.8-alpha-1', '<' ) ) {
                add_filter( 'block_categories', [$this, 'wpdocs_add_new_block_category'], 10, 2 );
            } else {
                add_filter( 'block_categories_all', [$this, 'wpdocs_add_new_block_category'], 10, 2 );
            }

            add_action( 'media_buttons', [$this, 'h5vp_shortcode_button'], 1 );
        }
        
    }

    public function wpLoaded(){
        add_filter('admin_footer_text', [$this, 'footerText']);
    }

    public function removeRowAction($row){
        global $post;
        if ($post->post_type == 'videoplayer' or $post->post_type == 'videoplayer_quick') {
            unset($row['view']);
            unset($row['inline hide-if-no-js']);
        }
        return $row;
    }

    public function hideAction(){
        global $post;
        if ($post->post_type == 'videoplayer' || $post->post_type == 'h5vpplaylist') {
            echo '
                <style type="text/css">
                    #misc-publishing-actions,
                    #minor-publishing-actions{
                        display:none;
                    }
                </style>
                ';
        }
    }

    public function changePublishText($translation, $text){
        if ('videoplayer' == get_post_type()) {
            if ($text == 'Publish') {
                return 'Save';
            }
        }
        return $translation;
    }

    public function changeUpdateMessage($messages){
        $messages['videoplayer'][1] = __('Updated ');
        return $messages;
    }

    public function footerText($text){
            $screen = get_current_screen();
            $page = '';
            if(isset($screen->base)){
                $page = $screen->base;
            }
        if ('videoplayer' == get_post_type() || 'h5vpplaylist' == get_post_type() || $page == 'videoplayer_page_html5vp_quick_player') {
            $url = 'https://wordpress.org/support/plugin/html5-video-player/reviews/?filter=5#new-post';
            $text = sprintf(__( ' If you like <strong>Html5 Video Player</strong> please leave us a <a href="%s" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> rating. Your Review is very important to us as it helps us to grow more. ', 'h5vp-domain'), $url);
        }
        return $text;
    }


    /**
     * Adding a new (custom) block category.
     *
     * @param   array $block_categories                         Array of categories for block types.
     * @param   WP_Block_Editor_Context $block_editor_context   The current block editor context.
     */
    function wpdocs_add_new_block_category( $block_categories ) {
        $exists = false;
        foreach($block_categories as $category){
            if($category['slug'] == 'media'){
                $exists = true;
            }
        }
        if(!$exists){
            return array_merge(
                $block_categories,
                [
                    [
                        'slug'  => 'media',
                        'title' => esc_html__( 'Media', 'h5vp' ),
                        'icon'  => '', // Slug of a WordPress Dashicon or custom SVG
                    ],
                ]
            );
        }
        
        return $block_categories;
    }

    public function arrayToString($array = []){
        $implode = implode(',', $array);
        return $implode;
    }

    public function getVideoTitle($videos, $videoId){
        foreach($videos as $video){
            if($video->id == $videoId){
                return $video->title !== '' ? $video->title : $video->src;
            }
        }
    }

    public function ageAveView($seconds = 0, $views = 0){

    }

    /**
     * format second
     */
    public function secToHR($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;
        $hLabel = $hours > 1 ? 'hours' : 'hour';
        $mLabel = $minutes > 1 ? 'minutes' : 'minute';
        $sLabel = $seconds > 1 ? 'seconds' : 'second';
        return $hours > 0 ? "$hours $hLabel $minutes $mLabel " : ($minutes > 0 ? "$minutes $mLabel $seconds $sLabel" : "$seconds $sLabel");
    }


	function h5vp_shortcode_button() {
		$img = H5VP_PRO_PLUGIN_DIR .'img/icn.png';
		$container_id = 'h5vpmodal';
		$title = 'Insert Html5 Video Player';
		$context = '
		<a class="thickbox button" id="h5vp_shortcode_button" title="'.$title.'" style="outline: medium none !important; cursor: pointer;" >
		<img src="'.$img.'" alt="" width="20" height="20" style="position:relative; top:-1px"/>Html5 video player</a>
		<a class="thickbox button" id="h5vp_add_video_button" title="'.$title.'" style="outline: medium none !important; cursor: pointer;" >
		<img src="'.$img.'" alt="" width="20" height="20" style="position:relative; top:-1px"/>Add Video</a>';
		echo wp_kses_post($context);
	}
}


