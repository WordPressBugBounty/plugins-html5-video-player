<?php
namespace H5VP\Helper;

class Functions{

    protected static $settings = null;

    /**
     * get settings form video playlist
     */
    public static function getPlaylistOption($id, $key, $default = null, $true = false){
        $meta = metadata_exists( 'post', $id, 'h5vp_playlist_options' ) ? get_post_meta($id, 'h5vp_playlist_options', true) : '';
        if(isset($meta[$key]) && $meta != ''){
            if($true == true){
                if($meta[$key] == '1'){
                    return true;
                }else if($meta[$key] == '0'){
                    return false;
                }
            }else {
                return $meta[$key];
            }
            
        }else {
            return $default;
        }
    }

    /**
     * get single value from option table
     */
    public static function getOption($option_name, $default = false, $boolean = false){
        $option = get_option($option_name);
        $result = '';
            if($option != ''){
                $result = $option;
            } else {
                $result = $default;
            }
        if($boolean){
            return (boolean) $result;
        }
        return $result ;
    }

    /**
     * get array value from option table
     */
    public static function getOptionDeep($option_name, $key, $default = false, $boolean = false){
        $option = get_option($option_name);
        if (isset($option[$key]) && $option[$key] != '') {
            $result =  $option[$key] ;
        }else {
            $result = $default;
        }

        if($boolean){
            return (boolean) $result;
        }
        return $result ;
    }

    /**
     * trim extra line and Tab
     */
    public static function trim($string){
        $string = preg_replace('/\s+/i', 'whiteSpace', $string);
        $string = preg_replace('/whiteSpace/i', ' ', $string);
        return $string;
    }


    /**
     * get provider form source
     */
    public static function getProvider($src){
        $provider = 'library';
    
        if (!empty($src)) {
          $yt_rx = '/^((?:https?:)?\/\/)?((?:www|m)\.)?((?:youtube\.com|youtu.be))(\/(?:[\w\-]+\?v=|embed\/|v\/)?)([\w\-]+)(\S+)?$/';
          $has_match_youtube = preg_match($yt_rx, $src, $yt_matches);
    
          if ($has_match_youtube) {
            return 'youtube';
          }
    
          $vm_rx = '/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([‌​0-9]{6,11})[?]?.*/';
          $has_match_vimeo = preg_match($vm_rx, $src, $vm_matches);
    
          if ($has_match_vimeo) {
            return 'vimeo';
          }
        }
    
        return $provider;
      }

      /**
       * scrambel data ( password and video file if it is protected)
       */
      public static function scramble($do = 'encode', $data = ''){
        $originalKey = 'abcdefghijklmnopqrstuvwxyz1234567890';
		$key = 'z1ntg4ihmwj5cr09byx8spl7ak6vo2q3eduf';
		$resultData = '';
		if($do == 'encode'){
			if($data != ''){
				$length = strlen($data);
				for($i = 0; $i < $length; $i++){
					$position = strpos($originalKey, $data[$i]);
					if($position !== false){
						$resultData .= $key[$position];
					}else {
						$resultData .= $data[$i];
					}
				}
			}
		}

		if($do == 'decode'){
			if($data != ''){
				$length = strlen($data);
				for($i = 0; $i < $length; $i++){
					$position = strpos($key, $data[$i]);
					if($position !== false){
						$resultData .= $originalKey[$position];
					}else {
						$resultData .= $data[$i];
					}
				}
			}
		}

		return $resultData;
    }

    public static function settings($key, $default = false){
        $settings = get_option('h5vp_option');
        if(isset($settings[$key])){
            return $settings[$key];
        }
        return $default;
    }

    public static function isset($array, $key, $default=false){
        if(isset($array[$key])){
            return $array[$key];
        }
        return $default;
    }

    public static function isJSON($string){
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    public static function getThumb($video_id = 0){
        global $wp_filesystem;
    
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			include_once ABSPATH.'wp-admin/includes/file.php';
		}
    
		WP_Filesystem();

        $upload_directory = wp_get_upload_dir();
		$upload_baseurl   = $upload_directory['basedir'];
		$file = trailingslashit( $upload_baseurl ) . "h5vp/video-$video_id.json";
        $contents = $wp_filesystem->get_contents($file);

        if(self::isJSON($contents)){
            $data = json_decode($contents, true);
            if(isset($data['thumb']) && $data['thumb'] == 'data:,'){
                $data['thumb'] = '';
            }
            return $data;
        }
        return [];
    }

    static function sanitize_array($array){
        if(!is_array($array)){
            return [];
        }

        foreach($array as $key => $value){
            if(strpos($key, 'secret_key') !== false && strlen($value) == 32){
                $value = sanitize_text_field(str_replace('<', '&lt;', $value) ); 
                $value = sanitize_text_field($value);
                $array[$key] = str_replace(['&lt;', '&gt;', '&amp;'], [ '<', '>', '&'], $value);
            }else {
                if(is_array($value)){
                    $array[$key] = self::sanitize_array($value);
                }else {
                    $array[$key] =$value == 'true' ? true : ($value == 'false' ? false :  sanitize_text_field($value));
                }
            }
        }
        return $array;
    }


    static function chapterTimeToSeconds($value = "") {
        $time = [];
        preg_match('/(\d+):(\d+)/', $value, $time);
        if (empty($time)) {
            return (int)$value;
        }
        return (int)$time[1] * 60 + (int)$time[2];
    }

    public static function parseMarkers($markers){
        foreach($markers['points'] as $key => $value){
            $markers['points'][$key]['time'] =  self::chapterTimeToSeconds($value['time']);
        }
        return $markers;
    }

}