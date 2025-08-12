<?php

namespace H5VP\PostType;

use H5VP\Helper\Functions as Utils;

class VideoPlayer
{
    protected $post_type = 'videoplayer';
    protected $taxonomy = 'html5_video_tag';
    private static $_instance = null;

    public function __construct()
    {
        //we nothing do here
    }

    public function register()
    {
        add_action('init', [$this, 'init']);
        // add_filter('allowed_block_types', [$this, 'allowedTypes'], 10, 2);
        // add_filter('enter_title_here', [$this, 'videoTitle']);
        add_action('edit_form_after_title', [$this, 'shortcodeArea']);

        // // post type ui
        add_filter("manage_{$this->post_type}_posts_columns", [$this, 'postTypeColumns'], 1);
        add_action("manage_{$this->post_type}_posts_custom_column", [$this, 'postTypeContent'], 10, 2);

        // // filter by tags
        // add_action('restrict_manage_posts', [$this, 'tagFilter']);
        // add_action('parse_query', [$this, 'tagQuery']);

        // // force gutenberg here

        add_filter('pre_get_posts', [$this, 'limitAccess']);
        add_action('use_block_editor_for_post', [$this, 'forceGutenberg'], 10, 2);
        add_filter('filter_block_editor_meta_boxes', [$this, 'remove_metabox']);

        // add_filter( 'wp_insert_post_data' , [$this, 'filter_post_data'] , '99', 2 );

        add_filter('save_post', [$this, 'filter_post_data'], '99', 2);
    }

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * Register post type
     *
     * @return void
     */
    public function init()
    {
        register_post_type(
            $this->post_type,
            array(
                'labels' => array(
                    'name' => __('Html5 Video Player', 'h5vp'),
                    'singular_name' => __('Video Player', 'h5vp'),
                    'add_new' => __('Add New Player', 'h5vp'),
                    'add_new_item' => __('Add New Player', 'h5vp'),
                    'edit_item' => __('Edit Player', 'h5vp'),
                    'new_item' => __('New Player', 'h5vp'),
                    'view_item' => __('View Player', 'h5vp'),
                    'search_items' => __('Search Player', 'h5vp'),
                    'not_found' => __('Sorry, we couldn\'t find the Player you are looking for.', 'h5vp'),
                ),
                'public' => false,
                'show_ui' => true,
                // 'publicly_queryable' => true,
                // 'exclude_from_search' => true,
                'show_in_menu' => 'html5-video-player',
                'menu_position' => 14,
                'menu_icon' => H5VP_PRO_PLUGIN_DIR . 'admin/img/icn.png',
                'has_archive' => false,
                'hierarchical' => false,
                'capability_type' => 'page',
                'rewrite' => false,
                'show_in_rest' => true,
                'supports' => array('title', 'editor'),
                'template' =>  [['html5-player/parent']],
                'template_lock' => 'all',
            )
        );
    }

    function remove_metabox($metaboxs)
    {
        global $post;
        $screen = get_current_screen();

        if ($screen->post_type === $this->post_type) {
            return false;
        }
        return $metaboxs;
    }

    /**
     * Force gutenberg in case of classic editor
     */
    public function forceGutenberg($use, $post)
    {
        if ($this->post_type === $post->post_type) {
            $isGutenberg = get_post_meta($post->ID, 'isGutenberg', true);
            $gutenberg = get_option('h5vp_option', ['h5vp_gutenberg_enable' => true]);
            if (isset($gutenberg['h5vp_gutenberg_enable'])) {
                $gutenberg = (bool) $gutenberg['h5vp_gutenberg_enable'];
            } else {
                $gutenberg = true;
            }

            if ($gutenberg) {
                if ($post->post_status == 'auto-draft') {
                    update_post_meta($post->ID, 'isGutenberg', true);
                    return true;
                }
                if ($isGutenberg) {
                    return true;
                } else {
                    remove_post_type_support($this->post_type, 'editor');
                    return false;
                }
                return $use;
            } else {
                if ($isGutenberg) {
                    return true;
                } else {
                    remove_post_type_support($this->post_type, 'editor');
                    return false;
                }
            }
        }

        return $use;
    }

    /**
     * Limit media hub posts by author if cannot edit others posts
     *  
     * @param \WP_Query $query
     * @return \WP_Query
     */
    public function limitAccess($query)
    {
        global $pagenow, $typenow;

        if ('edit.php' != $pagenow || !$query->is_admin || $this->post_type !== $typenow) {
            return $query;
        }

        if (!current_user_can('edit_others_posts')) {
            $query->set('author', get_current_user_id());
        }

        return $query;
    }



    /**
     * Columns on all posts page
     *
     * @param array $defaults
     * @return array
     */
    public function postTypeColumns($columns)
    {

        unset($columns['date']);
        $columns['shortcode'] = 'Shortcode';
        $option = h5vp_get_option('h5vp_option');
        if ($option('h5vp_import_export_enable', false)) {
            $columns['export'] = 'Export/Import';
        }
        $columns['shortcode_deprecated'] = 'Shortcode Deprecated';
        // $columns['total_viewes'] = 'Total Viewes ( Deprecated )';
        $columns['date'] = 'Date';
        return $columns;
    }

    public function postTypeContent($column_name, $post_id)
    {
        switch ($column_name) {
            case 'shortcode':
                echo '<div class="h5vp_front_shortcode"><input style="text-align: center; border: none; outline: none; background-color: #1e8cbe; color: #fff; padding: 4px 10px; border-radius: 3px;" value="[html5_video id=' . esc_attr($post_id) . ']" ><span class="htooltip">Copy To Clipboard</span></div>';
                break;
            case 'shortcode_deprecated':
                echo '<div class="h5vp_front_shortcode"><input style="text-align: center; border: none; outline: none; background-color: #1e8cbe; color: #fff; padding: 4px 10px; border-radius: 3px;" value="[video id=' . esc_attr($post_id) . ']" ><span class="htooltip">Copy To Clipboard</span></div>';
                break;
            case 'export':
                echo '<button class="button button-primary h5vp_export_button" data-id=' . esc_attr($post_id) . '>Export</button> <button class="button button-primary h5vp_import_button" data-id=' . esc_attr($post_id) . '>Import</button>';
                break;
            default:
                false;
        }
    }

    public function videoTitle($title)
    {
        $screen = get_current_screen();
        if ($this->post_type == $screen->post_type) {
            $title = __('Enter a title...', 'h5vp');
        }
        return $title;
    }

    public function allowedTypes($allowed_block_types, $post)
    {
        if ($post->post_type !== $this->post_type) {
            return $allowed_block_types;
        }

        return [
            'html5-player/parent',
            'html5-player/video',
            'html5-player/vimeo',
            'html5-player/youtube'
        ];
    }

    public function shortcodeArea()
    {
        global $post;
        if ($post->post_type == 'videoplayer') {
?>
            <div class="h5vp_playlist_shortcode">
                <div class="shortcode-heading">
                    <div class="icon"><span class="dashicons dashicons-video-alt3"></span> <?php echo esc_html__("HTML5 Video Player", "h5vp"); ?></div>
                    <div class="text"> <a href="https://bplugins.com/support/" target="_blank"><?php echo esc_html__("Supports", "h5vp"); ?></a></div>
                </div>
                <div class="shortcode-left">
                    <h3><?php echo esc_html__("Shortcode", "h5vp") ?></h3>
                    <p><?php echo esc_html__("Copy and paste this shortcode into your posts, pages and widget content:", "h5vp") ?></p>
                    <div class="shortcode" selectable>[html5_video id='<?php echo esc_attr($post->ID); ?>']</div>
                </div>
                <div class="shortcode-right">
                    <h3><?php echo esc_html__("Template Include", "h5vp") ?></h3>
                    <p><?php echo esc_html__("Copy and paste the PHP code into your template file:", "h5vp"); ?></p>
                    <div class="shortcode">&lt;?php echo do_shortcode('[html5_video id="<?php echo esc_attr($post->ID); ?>"]');
                        ?&gt;</div>
                </div>
            </div>
<?php
        }
    }

    /**
     * create a video id on database
     */
    function filter_post_data($post_id, $postarr)
    {

        $post_type = get_post_type($post_id);
        if ($post_type === 'videoplayer') {
            $password = get_post_meta($post_id, 'h5vp_protected_password', true);
            update_option("propagans_$post_id", [
                'pass' => md5($password),
                'quality' => Utils::sanitize_array(get_post_meta($post_id, 'h5vp_quality_playerio', true)),
                'source' => esc_url(get_post_meta($post_id, 'h5vp_video_link', true)),
            ]);

            $provider = get_post_meta($post_id, 'h5vp_video_source', true);
            $source = get_post_meta($post_id, 'h5vp_video_link', true);
            if (in_array($provider, ['youtube', 'vimeo'])) {
                $source = get_post_meta($post_id, 'h5vp_video_link_youtube_vimeo', true);
            }

            if (class_exists('\H5VP\Model\Video')) {
                $video = new \H5VP\Model\Video();
                $video->create([
                    'src' => esc_url($source),
                    'title' => get_the_title($post_id),
                    'type' => $provider,
                ]);
            }
        }
    }

    /**
     * get _h5vp_ meta
     */
    public static function get_h5vp_meta($array, $key, $default = false)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }
        return $default;
    }
}
