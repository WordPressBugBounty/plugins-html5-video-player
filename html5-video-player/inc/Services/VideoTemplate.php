<?php 

namespace H5VP\Services;
use H5VP\Helper\Functions as Utils;
use H5VP\Model\Video;
use H5VP\Model\View;

class VideoTemplate{
    protected static $uniqid;

    public static function html($data){
 
        $video_ext = pathinfo($data['source'], PATHINFO_EXTENSION);

        if($video_ext === 'm3u8') {
            wp_enqueue_script('h5vp-hls');
        }
        if($video_ext === 'mpd') {
            wp_enqueue_script('h5vp-dash');
        }
        wp_enqueue_script('html5-player-video-view-script');
        wp_enqueue_style('html5-player-video-style');
        $hideLoadingPlaceholder = $data['features']['hideLoadingPlaceholder'] ?? false;  

        $aws = self::parseS3Url($data['source']);

        if($aws && class_exists('\Aws\S3\S3Client')){
            $secrets = self::get_secrets();;
            if(!$secrets){
                echo "security";
                return false;
            }

            $s3Client = new \Aws\S3\S3Client([
                'version' => 'latest',
                'region' => $secrets['region'],
                'credentials' => [
                    'key'    => $secrets['key'],
                    'secret' => $secrets['secret'], 
                ]
            ]);

            
            try {
                $cmd = $s3Client->getCommand('GetObject', [
                    'Bucket' => $secrets['bucket'],
                    'Key' => $aws['file_location'],
                ]);
                $data['source'] = $s3Client->createPresignedRequest($cmd, '+60 minutes')->getUri()->__toString();
               
            } catch (\Throwable $th) {

            } 
        }

        
        unset($data['features']['passwordProtected']['password']);
        if($data['features']['passwordProtected']['enabled'] ?? null){
            unset($data['quality']);
            unset($data['qualities']);
            $data['source'] = 'https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-576p.mp4';
            
        }
        
        ob_start();

        // echo '<pre>';
        // print_r( $data['styles'] );
        // echo '</pre>';
        // echo "working fine";
        ?>

        <div class='html5_video_players' style="width:<?php echo esc_attr($data['styles']['plyr_wrapper']['width'] ?? '100%') ?>;" data-nonce="<?php echo esc_attr(wp_create_nonce('wp_ajax')) ?>" data-attributes="<?php echo esc_attr(wp_json_encode($data)) ?>">
            <div class="preload_poster" style="background: url(<?php echo esc_url($data['poster']); ?>);background-position: 50% 50%;overflow:hidden;aspect-ratio:<?php echo esc_attr($data['options']['ratio'] ? str_replace(':', '/', $data['options']['ratio']) : '');  ?>">
                <svg width="36px" height="36px" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.79062 2.09314C4.63821 1.98427 4.43774 1.96972 4.27121 2.05542C4.10467 2.14112 4 2.31271 4 2.5V12.5C4 12.6873 4.10467 12.8589 4.27121 12.9446C4.43774 13.0303 4.63821 13.0157 4.79062 12.9069L11.7906 7.90687C11.922 7.81301 12 7.66148 12 7.5C12 7.33853 11.922 7.18699 11.7906 7.09314L4.79062 2.09314Z" fill="#fff"/>
                </svg>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    public static function getFinalData($data){
        if($data['infos']['protected']){
            $quality = self::i($data['infos']['video'], 'quality');
            $newQuality = [];
            $data['infos']['propagans'] = Utils::scramble('encode', $data['infos']['propagans']);
            $data['infos']['video']['source'] = Utils::scramble('encode', $data['infos']['video']['source']);

            if($quality && is_array($quality) && count($quality) > 0){
                foreach($quality as $video){
                    $video['video_file'] = Utils::scramble('encode', $video['video_file']);
                    array_push($newQuality, $video);
                }
            }

            $data['infos']['video']['quality'] = $newQuality;
            return $data;
        }else {
            return $data;
        }
    }

    /**
     * create a unique id
     */
    public static function createId(){
        return "h5vp".uniqid();
    }

    /**
     * echo and ecape value if it isset
     */
    public static function e_i($array = [], $index = ''){
        if(isset($data[$index])){
            echo esc_html($data[$index]);
        }
        return false;
    }

    /**
     * return value if it isset
     */
    public static function i($array = [], $index = '', $key2 = ''){
        if(isset($array[$index][$key2])){
            return $array[$index][$key2];
        }
        if(isset($array[$index])){
            return $array[$index];
        }
        return false;
    }

    public static function getVideoId($source = '', $provider = 'library', $title = ''){
        global $wpdb;
        $table_name = $wpdb->prefix.'h5vp_videos';
        $video = new Video();
        $args = [
            'title' => $title,
            'user_id' => get_current_user_id(),
            'type' => $provider,
            'src' => $source
        ];

        $video_id = $video->create($args);
        return $video_id;
    }

    public static function getType($ext){
        $mimes = [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'm4v' => 'video/x-m4v',
            // 'mov' => 'video/quicktime',
            'qt' => 'video/quicktime',
            // 'avi' => 'video/x-msvideo',
            'flv' => 'video/x-flv',
            'mpg' => 'video/mpeg',
            'wmv' => 'video/x-ms-wmv',
            'asf' => 'video/x-ms-asf',
        ];
        return  'video/mp4';
        return $mimes[$ext] ?? 'video/mp4';
    }

    static function parseS3Url($s3Url) {
        // Parse the URL using parse_url
        $urlParts = wp_parse_url($s3Url);
    
        // Check if it's an S3 URL
        if ($urlParts && isset($urlParts['host']) && preg_match('/\.amazonaws\.com$/', $urlParts['host'])) {
            // Extract bucket, server, file location, and region
            $hostParts = explode('.', $urlParts['host']);
            $bucket = $hostParts[0];
            $server = $urlParts['host'];
            $fileLocation = ltrim($urlParts['path'], '/');
            $region = $hostParts[2]; // Assuming the region is the third part of the host
    
            return [
                'bucket' => trim($bucket),
                'server' => trim($server),
                'file_location' => trim($fileLocation),
                'region' => trim($region),
            ];
        } else {
            // Not a valid S3 URL
            return null;
        }
    }

    static function get_secrets(){
        $options = get_option('h5vp_option', []);
        if(isset($options['h5vp_aws_key_id']) && $options['h5vp_aws_access_key']){
            return [
                'bucket' => $options['h5vp_aws_bucket'],
                'region' => $options['h5vp_aws_region'],
                'key' => $options['h5vp_aws_key_id'],
                'secret' => $options['h5vp_aws_access_key'],
            ];
        }
        return null;
    }

    
}
