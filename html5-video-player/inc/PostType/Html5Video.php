<?php

namespace H5VP\PostType;

class Html5Video
{
    protected $post_type = 'html5_video';
    protected $taxonomy = 'html5_video_tag';
    private static $_instance = null;
    
    public function __construct()
    {
        add_action('init', [$this, 'init']);
        // add_filter('allowed_block_types', [$this, 'allowedTypes'], 10, 2);
        add_filter('enter_title_here', [$this, 'videoTitle']);

        // post type ui
        add_filter("manage_{$this->post_type}_posts_columns", [$this, 'postTypeColumns'], 1);
        add_action("manage_{$this->post_type}_posts_custom_column", [$this, 'postTypeContent'], 10, 2);

        // force gutenberg here
        add_action('use_block_editor_for_post', [$this, 'forceGutenberg'], 999, 2);

        // limit media hub posts
        add_filter('pre_get_posts', [$this, 'limitMediaHubPosts']);

    }


    public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

    /**
     * Limit media hub posts by author if cannot edit others posts
     *  
     * @param \WP_Query $query
     * @return \WP_Query
     */
    public function limitMediaHubPosts($query)
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
     * Force gutenberg in case of classic editor
     */
    public function forceGutenberg($use, $post)
    {
        if ($this->post_type === $post->post_type) {
            return true;
        }

        return $use;
    }

    /**
     * Columns on all posts page
     *
     * @param array $defaults
     * @return array
     */
    public function postTypeColumns($defaults)
    {
        $columns = array_merge($defaults, array(
            'title' => $defaults['title'],
            'shortcode' => __('Shortcode', 'h5vp'),
            'php_function' => __('PHP Function', 'h5vp'),
        ));

        $v = $columns['taxonomy-'.$this->taxomony];
        unset($columns['taxonomy-'.$this->taxomony]);
        $columns['taxonomy-'.$this->taxomony] = $v;

        $v = $columns['date'];
        unset($columns['date']);
        $columns['date'] = $v;
        return $columns;
    }

    public function postTypeContent($column_name, $post_ID)
    {
        if ('shortcode' === $column_name) {
            echo '<code>[html5_video id=' . (int) esc_attr($post_ID) . ']</code>';
        }
        if ('php_function' === $column_name) {
            echo '<code>html5_video(' . (int) esc_attr($post_ID) . ')</code>';
        }
        if ('video_tags' === $column_name) {
            $tags = get_the_terms($post_ID, $this->taxonomy);
            if (is_array($tags)) {
                foreach ($tags as $key => $tag) {
                    $tags[$key] = '<a href="?post_type='.$this->post_type.'&'.$this->taxonomy.'=' . $tag->term_id . '">' . $tag->name . '</a>';
                }
                echo wp_kses_post(implode(', ', $tags));
            }
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
            'html5-payer/video'
        ];
    }

    /**
     * Register post type
     *
     * @return void
     */
    public function init()
    {
        register_taxonomy('html5_video_tag', $this->taxonomy, [
            'labels'                => array(
                'name'                     => _x('Media Categories', 'post type general name'),
                'singular_name'            => _x('Media Category', 'post type singular name'),
                'search_items'             => _x('Search Media Categories', 'admin menu'),
                'popular_items'            => _x('Popular Media Categories', 'add new on admin bar'),
            ),
            'label' => __('Category', 'h5vp'),
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
        ]);

        register_post_type(
            $this->post_type,
            array(
                'labels'                => array(
                    'name'                     => _x('Media', 'post type general name', 'h5vp'),
                    'singular_name'            => _x('Media', 'post type singular name', 'h5vp'),
                    'menu_name'                => _x('Media', 'admin menu', 'h5vp'),
                    'name_admin_bar'           => _x('Video', 'add new on admin bar', 'h5vp'),
                    'add_new'                  => _x('Add New', 'Video', 'h5vp'),
                    'add_new_item'             => __('Add New Video', 'h5vp'),
                    'new_item'                 => __('New Video', 'h5vp'),
                    'edit_item'                => __('Edit Video', 'h5vp'),
                    'view_item'                => __('View Video', 'h5vp'),
                    'all_items'                => __('Media', 'h5vp'),
                    'search_items'             => __('Search Videos', 'h5vp'),
                    'not_found'                => __('No Videos found.', 'h5vp'),
                    'not_found_in_trash'       => __('No Videos found in Trash.', 'h5vp'),
                    'filter_items_list'        => __('Filter Videos list', 'h5vp'),
                    'items_list_navigation'    => __('Videos list navigation', 'h5vp'),
                    'items_list'               => __('Videos list', 'h5vp'),
                    'item_published'           => __('Video published.', 'h5vp'),
                    'item_published_privately' => __('Video published privately.', 'h5vp'),
                    'item_reverted_to_draft'   => __('Video reverted to draft.', 'h5vp'),
                    'item_scheduled'           => __('Video scheduled.', 'h5vp'),
                    'item_updated'             => __('Video updated.', 'h5vp'),
                ),
                'public'                => false,
                'show_ui'               => true,
                'show_in_menu'          => 'edit.php?post_type=videoplayer',
                'rewrite'               => false,
                'show_in_rest'          => true,
                'rest_base'             => 'html5-videos',
                'rest_controller_class' => 'WP_REST_Blocks_Controller',
                'map_meta_cap'          => true,
                'supports'              => [
                    'title',
                    'editor',
                ],
                'taxonomies' => [$this->taxonomy],
                'template' => [
                    ['html5-player/parent']
                ],
                'template_lock' => 'all',
            )
        );
    }

}