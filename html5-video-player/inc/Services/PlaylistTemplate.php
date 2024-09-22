<?php
namespace H5VP\Services;
// require_once(__DIR__.'/../Helper/DefaultArgs.php');
// require_once(__DIR__.'/../Helper/Functions.php');
// require_once(__DIR__.'/AnalogSystem.php');
use H5VP\Helper\DefaultArgs;
use H5VP\Helper\Functions;
use H5VP\Services\AnalogSystem;

class PlaylistTemplate{
    public static $uniqid = null;
    protected static $styles = [];
    protected static $mediaQuery = [];

    static function html($data){
        
    }

    public static function html_old($data){
        self::createId();
        self::enqueueEssentialAssets();

        $provider = $data['template']['videos'][0]['h5vp_video_provider'];
        
        $videos = $data['template']['videos'];
        $iid = self::$uniqid;

        ob_start();
		?>
		<style>
            <?php echo esc_html(self::renderStyle($data['template'])); ?>
        </style>
        <?php
		if(count($videos) > 0){
            if($data['template']['skin'] == 'rightside'){
                self::templateRightSide($data);
            }else {
                self::templateDefault($data);
            }
        }
        return ob_get_clean();
    }

    public static function templateDefault($data){
        $provider = $data['template']['videos'][0]['h5vp_video_provider'];
        $videos = $data['template']['videos'];
        $t = $data['template'];
        $iid = self::$uniqid;

        ?>sdfsdfsdf
        <div>
        <div  id="<?php echo esc_attr($iid); ?>" class="h5vp_video_playlist h5vp_video_playlist_initializer <?php //echo esc_attr($t['skin']); ?>" data-options='<?php echo esc_attr(wp_json_encode($data['options'])); ?>' data-infos='<?php echo esc_attr(wp_json_encode( $data['infos'])) ?>' data-videos='<?php echo esc_attr(wp_json_encode( $videos)) ?>' videoindex="0" style="--plyr-color-main: <?php echo esc_attr(DefaultArgs::brandColor()); ?>;">
            <!-- initialize first video start -->
            <div class="video_wrapper">
            <?php if($provider == 'library'): ?>
            <video  preload="<?php echo esc_attr($data['template']['preload']); ?>" id="video_playlist<?php echo esc_attr($iid); ?>" playsinline controls poster="<?php echo esc_attr($videos[0]['video_thumb']) ?>">
                <source src="<?php echo esc_url($videos[0]['video_source']) ?>" type="video/mp4" videoindex="0" id="playlist<?php echo esc_attr($iid); ?>" />
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
            </div>
            
            <!-- all video list start -->
            <div class="list_items <?php echo esc_attr($data['template']['skin']); ?> h5vpNotSlide">
            <?php for($i=0; $i < count($videos); $i++):
                $source = $videos[$i]['h5vp_video_provider'] == 'library' ? $videos[$i]['video_source'] : $videos[$i]['h5vp_video_source'];
                $image = $videos[$i]['video_thumb'] ? $videos[$i]['video_thumb'] : false;
                $title = $videos[$i]['video_title'] != '' ? $videos[$i]['video_title'] : 'No Video Found';
                 ?>
                <li class="item<?php echo $i; ?>">
                    <a href="#" class="h5vp_playlist_item" provider="<?php echo esc_attr($videos[$i]['h5vp_video_provider']); ?>" poster="<?php echo $image ?>" videoindex="<?php echo $i; ?>" source='<?php echo esc_url($source); ?>'>
                        <div class="overlay"></div>
                        <?php if($image && $data['template']['skin'] != 'simplelist'): ?>
                            <img class="video_thumb" src="<?php echo esc_url($image); ?>" alt="">
                        <?php else: ?>
                            <div class="noImage"></div>
                        <?php endif; ?>
                        <div class="svg"><svg></svg></div>
                        <video src="<?php echo $source ?>" style="display: none;"></video>
                    </a>
                    <div class="video_title"><h3 class="title"><?php echo $title; ?></h3></div>
                </li>
            <?php endfor; ?>
            </div>
        </div>
        </div>
        <?php
    }

    public static function templateRightSide($data){
        $provider = $data['template']['videos'][0]['h5vp_video_provider'];
        $videos = $data['template']['videos'];
        $t = $data['template'];
        $iid = self::$uniqid;

        ?>
        <div>
        <div id="<?php echo esc_attr($iid); ?>" class="h5vp_video_playlist h5vp_video_playlist_initializer <?php echo esc_attr($t['skin']); ?>" data-options='<?php echo esc_attr(wp_json_encode($data['options'])); ?>' data-infos='<?php echo esc_attr(wp_json_encode( $data['infos'])) ?>' data-videos='<?php echo esc_attr(wp_json_encode( $videos)) ?>' videoindex="0" style="--plyr-color-main: <?php echo esc_attr($data['template']['brandColor']); ?>;">
            <!-- initialize first video start -->
            <div class="video_wrapper">
                <?php if($provider == 'library'): ?>
                <video  preload="<?php echo esc_attr($data['template']['preload']); ?>" id="video_playlist<?php echo esc_attr($iid); ?>" playsinline controls poster="<?php echo esc_attr($videos[0]['video_thumb']) ?>">
                    <source src="<?php echo $videos[0]['video_source'] ?>" type="video/mp4" videoindex="0" id="playlist<?php echo esc_attr($iid); ?>" />
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
                    <div id="notLibrary" data-plyr-provider="youtube" data-plyr-embed-id="<?php echo $videos[0]['h5vp_video_source']; ?>"></div>
                <?php elseif($provider == 'vimeo'): ?>
                    <div id="notLibrary" data-plyr-provider="vimeo" data-plyr-embed-id="<?php echo $videos[0]['h5vp_video_source']; ?>"></div>
                <?php endif; ?>
            </div>
            <!-- end initialize first video -->

            <!-- all video list start -->
            <div class="list_items <?php echo esc_attr($data['template']['skin']); ?>">
            <?php for($i=0; $i < count($videos); $i++):
                $source = $videos[$i]['h5vp_video_provider'] == 'library' ? $videos[$i]['video_source'] : $videos[$i]['h5vp_video_source'];
                $image = $videos[$i]['video_thumb'] ?? false;
                $title = $videos[$i]['video_title'];
                $desc = $videos[$i]['video_desc'] ?? '';
            
                 ?>
                <li class="item<?php echo $i ." ".$t['modern']; ?>">
                    <a href="#" class="h5vp_playlist_item" provider="<?php echo $videos[$i]['h5vp_video_provider']; ?>" poster="<?php echo $image ?>" videoindex="<?php echo $i; ?>" source='<?php echo $source; ?>'>
                        <div class="overlay"></div>
                        <?php if($image && $t['modern'] == 'imageText'): ?>
                            <img class="video_thumb" src="<?php echo $image; ?>" alt="">
                        <?php else: ?>
                            <div class="noImage"></div>
                        <?php endif; ?>
                        <!-- <div class="svg"><svg></svg></div> -->
                        <video src="<?php echo $source ?>" style="display: none;"></video>
                    </a>
                    <?php if($t['modern'] != 'image'): ?>
                        <div class="video_title">
                            <h3 class="title"><?php echo esc_html(self::splice($title)); ?></h3>
                            <p><?php echo esc_html(self::splice($desc, 50)); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if($t['modern'] == 'image'): ?>
                        <?php if($image): ?>
                            <img class="video_thumb" src="<?php echo $image; ?>" alt="">
                        <?php else: ?>
                            <div class="noImage"></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </li>
            <?php endfor; ?>
            </div>
        </div>
        </div>
        <?php
    }

    public static function enqueueEssentialAssets(){
        wp_enqueue_script('html5-player-video-view-script');
        wp_enqueue_script('bplugins-owl-carousel');
        wp_enqueue_style('html5-player-video-style');
        wp_enqueue_style('bplugins-owl-carousel');
    }

    public static function renderStyle($template){
        $id = self::$uniqid;
        $t = $template;
        self::addStyle("#$id .video_title h3", ['color' => $template['textColor']]);
        self::addStyle("#$id .listwithposter .owl-nav .owl-prev:before, #$id .listwithposter .owl-nav .owl-next:before", ['color' => $template['arrowColor'], 'font-size' => $template['arrowSize']]);
        self::addStyle("#$id .h5vpNotSlide", ['grid-template-columns' => "repeat( ".$template['column'].", minmax(150px, 1fr))"]);
        self::addStyle("#$id .listwithposter li a .svg", ['background' => $template['brandColor']]);

        //global
        self::addStyle("#$id", ['border' => $t['borderWidth']." solid ".$t['borderColor']]);
        self::addStyle("#$id .list_items  li", ['background' => $template['listBG']]);
        self::addStyle("#$id .list_items  li.active, #$id .list_items  li:hover", ['background' => $template['listHoverBG']]);
        self::addStyle("#$id .list_items li:hover h3, #$id .list_items li:hover p", ['color' => $template['textHoverColor']]);
        self::addStyle("#$id .list_items li.active h3, #$id .list_items li.active p", ['color' => $template['textHoverColor']]);
        self::addStyle("#$id .list_items li h3, #$id .list_items li p", ['color' => $template['textColor']]);
        if($t['modern'] != 'image'){
            self::addStyle("#$id .rightside li", ['background' => $template['listBG']]);
        }

        // self::addStyle("#$id", ['width' => '700px']);
        // self::addStyle("#$id .video_wrapper", ['width' => 'calc(100% - 300px)']);

        if($template['skin'] == 'rightside'){
            self::addStyle("#$id .rightside", ['width' => '300px', 'float' => 'left']);
            self::addStyle("#$id", ['width' => '100%']);
            self::addStyle("#$id .video_wrapper", ['width' => 'calc(100% - 300px)', 'float' => 'left', 'overflow' => 'hidden']);
            self::addStyle("#$id .video_wrapper", ['border-right' => $t['borderWidth']." solid ".$t['borderColor']]);
            // self::addStyle("#$id .rightside li.active h3, #$id .rightside li:hover h3", ['color' => '#fff']);
            // self::addStyle("#$id .rightside li.active, #$id .rightside li:hover", ['background' => $template['listHoverBG']]);
            // self::addStyle("#$id .rightside li h3, #$id .rightside li p", ['color' => $template['textColor']]);
            // self::addStyle("#$id .rightside li:hover h3, #$id .rightside li:hover p", ['color' => $template['textHoverColor']]);
            // self::addStyle("#$id .rightside li.active h3, #$id .rightside li.active p", ['color' => $template['textHoverColor']]);

            // if($t['modern'] != 'image'){
            //     self::addStyle("#$id .rightside li", ['background' => $template['listBG']]);
            // }
        }else {
            self::addStyle("#$id", ['width' => $template['width'], 'margin' => '0 auto', 'max-width' => '100%']);
            self::addStyle("#$id .h5vpNotSlide", ['grid-template-columns' => 'repeat(2, minmax(150px, 1fr))'], '@media screen and (max-width: 640px)');
            self::addStyle("#$id .h5vpNotSlide", ['grid-template-columns' => 'repeat(1, minmax(150px, 1fr))'], '@media screen and (max-width: 411px)');
            // .h5vp_video_playlist .simplelist li:hover
        }


        $output = '';
        foreach(self::$styles as $selector => $style){
            $new = '';
            foreach($style as $property => $value){
                if($value == ''){
                    $new .= $property;
                }else {
                    $new .= " $property: $value;";
                }
            }
            $output .= "$selector { $new }";
        }
        
        foreach(self::$mediaQuery as $query => $styles){
            $output .= $query."{";
            foreach($styles as $selector => $style){
                $new = '';
                foreach($style as $property => $value){
                    if($value == ''){
                        $new .= $property;
                    }else {
                        $new .= " $property: $value;";
                    }
                }
                $output .= "$selector { $new }";
            }
            $output .= "}";
        }



        return $output;
    }

    public static function addStyle($selector, $styles, $mediaQuery = false){
        if($mediaQuery){
            if(array_key_exists($mediaQuery, self::$mediaQuery)){
                if(array_key_exists($selector, self::$mediaQuery[$mediaQuery])){
                    self::$mediaQuery[$mediaQuery][$selector] = wp_parse_args(self::$mediaQuery[$mediaQuery][$selector], $styles);
                }else {
                    self::$mediaQuery[$mediaQuery] = wp_parse_args(self::$mediaQuery[$mediaQuery], [$selector => $styles]);
                }
             }else {
                 self::$mediaQuery[$mediaQuery] = [$selector => $styles];
             }
        }else {
            if(array_key_exists($selector, self::$styles)){
                self::$styles[$selector] = wp_parse_args(self::$styles[$selector], $styles);
             }else {
                 self::$styles[$selector] = $styles;
             }
        }
        
    }


    public static function createId(){
        self::$uniqid = "h5vp".uniqid();
    }

    public static function splice($string, $length = 40){
        if(strlen($string) < 45){
            return $string;
        }
        return substr($string, 0, $length)."...";
    }



}