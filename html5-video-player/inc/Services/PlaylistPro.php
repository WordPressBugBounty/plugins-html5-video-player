<?php
namespace H5VP\Services;

use H5VP\Helper\DefaultArgs;
use H5VP\Helper\Functions;
use H5VP\Services\AnalogSystem;
if(!class_exists('PlaylistPro')){
class PlaylistPro{
    public static $uniqid = null;
    public function __construct(){
        add_shortcode('video_playlist', [$this, 'h5vp_video_playlist_shortcode']);
    }

    /**
     * Video Playlist shortcode
     */
    public function h5vp_video_playlist_shortcode($atts){
        extract(shortcode_atts(array(
            'id' => null
        ), $atts));

        if($id == null){
            return;
        }
        self::createId();

        // self::enqueueEssentialAssets();
        $data = AnalogSystem::parsePlaylistData($id);

        // pr

        // $provider = $data['template']['videos'][0]['h5vp_video_provider'];
        // $videos = $data['template']['videos'];
        // $iid = self::$uniqid;
		
        ob_start();
        
        wp_enqueue_script('html5-player-playlist');
        // wp_enqueue_style('html5-player-playlist');
        
		?>

        <div class="h5vp_playlist" data-attributes="<?php echo esc_attr(wp_json_encode($data)) ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('wp_ajax')) ?>"></div>

<?php

        return ob_get_clean();

        ?>
		<style>
            <?php echo esc_html(Functions::trim(self::renderStyle($data['template']))); ?>
        </style>
		
        <?php if(count($videos) > 0): ?>
        <div id="<?php echo esc_attr($iid); ?>" class="h5vp_video_playlist h5vp_video_playlist_initializer" data-options='<?php echo esc_attr(wp_json_encode($data['options'])); ?>' data-infos='<?php echo esc_attr(wp_json_encode( $data['infos'])) ?>' data-videos='<?php echo esc_attr(wp_json_encode( $videos)) ?>' videoindex="0" style="--plyr-color-main: <?php echo esc_attr($data['template']['brandColor']); ?>;">
            <!-- initialize first video start -->
            <?php if($provider == 'library'): ?>
            <video  preload="<?php echo esc_attr($data['template']['preload']); ?>" id="video_playlist<?php echo $id; ?>" playsinline controls poster="<?php echo esc_attr($videos[0]['video_thumb']) ?>">
                <source src="<?php echo esc_url($videos[0]['video_source']) ?>" type="video/mp4" videoindex="0" id="playlist<?php echo $id; ?>" />
                <!-- initialize quality -->
                <?php
                    if(isset($videos[0]['h5vp_quality_playerio'])){
                        foreach($videos[0]['h5vp_quality_playerio'] as $video){
                            printf('<source src="%s" type="video/mp4" size="%s" />', $video['video_file'], $video['size']);
                        }
                    }
                ?>
                <!-- initialize captions -->
                <?php
                    if(isset($videos[0]['h5vp_subtitle_playerio'])){
                        foreach($videos[0]['h5vp_subtitle_playerio'] as $video){
                            printf('<track kind="captions" label="%s" src="%s" default="true">', $video['label'], $video['caption_file']);
                        }
                    }
                ?>
            </video>
            <?php elseif($provider == 'youtube'): ?>
                <div id="notLibrary" data-plyr-provider="youtube" data-plyr-embed-id="<?php echo esc_attr($videos[0]['h5vp_video_source']); ?>"></div>
            <?php elseif($provider == 'vimeo'): ?>
                <div id="notLibrary" data-plyr-provider="vimeo" data-plyr-embed-id="<?php echo esc_attr($videos[0]['h5vp_video_source']); ?>"></div>
            <?php endif; ?>
            <!-- end initialize first video -->

            <!-- all video list start -->
            <div class="list_items <?php echo esc_attr($data['template']['skin']); ?> h5vpNotSlide">
            <?php for($i=0; $i < count($videos); $i++):
                $source = $videos[$i]['h5vp_video_provider'] == 'library' ? $videos[$i]['video_source'] : $videos[$i]['h5vp_video_source'];
                $image = $videos[$i]['video_thumb'] ?? false;
                $title = $videos[$i]['video_title'] != '' ? $videos[$i]['video_title'] : 'No Video Found';
                 ?>
                <li class="item<?php echo esc_attr($i); ?>">
                    <a href="#" class="h5vp_playlist_item" provider="<?php echo esc_attr($videos[$i]['h5vp_video_provider']); ?>" poster="<?php echo esc_url($image) ?>" videoindex="<?php echo esc_attr($i); ?>" source='<?php echo esc_url($source); ?>'>
                        <div class="overlay"></div>
                        <?php if($image && $data['template']['skin'] != 'simplelist'): ?>
                            <img class="video_thumb" src="<?php echo esc_url($image); ?>" alt="">
                        <?php else: ?>
                            <div class="noImage"></div>
                        <?php endif; ?>
                        <div class="svg"><svg></svg></div>
                        <video src="<?php echo esc_url($source) ?>" style="display: none;"></video>
                    </a>
                    <div class="video_title"><h3 class="title"><?php echo esc_html($title); ?></h3></div>
                </li>
            <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php return ob_get_clean();
    }

    public static function enqueueEssentialAssets(){
        wp_enqueue_script('html5-player-video-view-script');
        wp_enqueue_script('bplugins-owl-carousel');
        wp_enqueue_style('html5-player-video-style');
        wp_enqueue_style('bplugins-owl-carousel');
    }

    public static function renderStyle($template){
        $id = self::$uniqid;
        ob_start();
        ?>
        <?php echo esc_html("#$id") ?>{
            width:<?php echo esc_html($template['width']); ?>;
            margin:0 auto;
            max-width: 100%;
        }
        <?php echo esc_html("#$id .video_title h3") ?>{
            color: <?php echo esc_html($template['textColor']); ?>;
        }
        <?php echo "#$id" ?> .listwithposter .owl-nav .owl-prev:before, <?php echo esc_html("#$id") ?> .listwithposter .owl-nav .owl-next:before {
            color: <?php echo esc_html($template['arrowColor']); ?>;
            font-size: <?php echo esc_html($template['arrowSize']); ?>;
        }
        <?php echo esc_html("#$id .listwithposter li a .svg") ?>{
            background: <?php echo esc_html($template['brandColor']); ?>
        }
        <?php echo esc_html("#$id .h5vpNotSlide"); ?> {
            grid-template-columns: repeat(<?php echo esc_html($template['column']); ?>, minmax(150px, 1fr));
        }
        @media screen and (max-width: 640px){
            <?php echo esc_html("#$id .h5vpNotSlide"); ?> {
            grid-template-columns: repeat(2, minmax(150px, 1fr));
            }
        }
        @media screen and (max-width: 411px){
            <?php echo esc_html("#$id .h5vpNotSlide"); ?> {
            grid-template-columns: repeat(1, minmax(150px, 1fr));
            }
        }
        <?php
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    public static function createId(){
        self::$uniqid = "h5vp".uniqid();
    }

}
}