<?php

namespace PrestoPlayer\Services;

use PrestoPlayer\Services\Scripts;


class ReusableVideos
{

    /**
     * Get reusable video block function.
     * 
     * @param mixed $id The ID of the reusable block.
     * @return $content The content of the block.
     */
    public static function get($id)
    {
        $content_post = get_post($id);
        $content = $content_post->post_content;
        return $content;
    }

    public static function getBlock($id)
    {
        $blocks = parse_blocks($id);
        $out = '';
        foreach ($blocks as $block) {
            $out .= render_block($block);
        }
        return $out;
    }

    /**
     * Display block function.
     * 
     * @param mixed $id The ID of the reusable block.
     */
    public static function display($id)
    {
        echo wp_kses_post(self::getBlock($id));
    }
}
