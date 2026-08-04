<?php

namespace H5VP\Base;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class GlobalChanges
{

    public function register()
    {
        if (is_admin()) {
            add_filter('post_row_actions', [$this, 'removeRowAction'], 10, 2);
            add_filter('gettext', [$this, 'changePublishText'], 10, 2);
            add_filter('post_updated_messages', [$this, 'changeUpdateMessage']);

            add_filter('block_categories_all', [$this, 'wpdocs_add_new_block_category'], 10, 2);

            add_action('media_buttons', [$this, 'h5vp_shortcode_button'], 1);
        }
    }

    public function removeRowAction($row)
    {
        global $post;
        if ($post && isset($post->post_type) && ($post->post_type == 'videoplayer' || $post->post_type == 'videoplayer_quick')) {
            unset($row['view']);
            unset($row['inline hide-if-no-js']);
        }
        return $row;
    }

    public function changePublishText($translation, $text)
    {
        if ('videoplayer' == get_post_type()) {
            if ($text == 'Publish') {
                return 'Save';
            }
        }
        return $translation;
    }

    public function changeUpdateMessage($messages)
    {
        $messages['videoplayer'][1] = __('Updated ', 'html5-video-player');
        return $messages;
    }


    /**
     * Adding a new (custom) block category.
     *
     * @param   array $block_categories                         Array of categories for block types.
     * @param   WP_Block_Editor_Context $block_editor_context   The current block editor context.
     */
    function wpdocs_add_new_block_category($block_categories)
    {
        $exists = false;
        foreach ($block_categories as $category) {
            if ($category['slug'] == 'media') {
                $exists = true;
            }
        }
        if (!$exists) {
            return array_merge(
                $block_categories,
                [
                    [
                        'slug' => 'media',
                        'title' => esc_html__('Media', 'html5-video-player'),
                        'icon' => '', // Slug of a WordPress Dashicon or custom SVG
                    ],
                ]
            );
        }

        return $block_categories;
    }



    function h5vp_shortcode_button()
    {
        $img = H5VP_PLUGIN_DIR . 'img/icn.png';
        $context = sprintf(
            '<a class="thickbox button" id="h5vp_shortcode_button" title="%s"><img src="%s" alt="" width="20" height="20"> %s</a>',
            esc_attr__('Insert Html5 Video Player', 'html5-video-player'),
            esc_url($img),
            esc_html__('Html5 video player', 'html5-video-player')
        );
        echo wp_kses_post($context);
    }
}
