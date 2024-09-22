<?php
namespace H5VP\Model;

class Block{

    public static function get($id){
        $content_post = get_post($id);
        $content = $content_post->post_content;
        return $content;
    }

    public static function getBlock($id){
        $blocks = parse_blocks(self::get($id));
        return $blocks[0]['innerBlocks'];
        $out = [];
        if(!isset($blocks[0]['innerBlocks'])){
            return false;
        }
        foreach ($blocks[0]['innerBlocks'] as $block) {
            if($block['blockName'] === 'html5-player/video'){
                $block['attrs']['provider'] = 'library';
                $out[] = $block['attrs'];
            }elseif($block['blockName'] === 'html5-player/youtube'){
                $block['attrs']['provider'] = 'youtube';
                $out[] = $block['attrs'];
            }elseif($block['blockName'] === 'html5-player/vimeo'){
                $block['attrs']['provider'] = 'vimeo';
                $out[] = $block['attrs'];
            }else {
                $out[] = $block;
            } 

            
        }

        return $out;
    }

    
}


