<?php
namespace H5VP\Base;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class ExtendMime {

    public function register(){
        global $wp_version;
        add_filter( 'upload_mimes', [$this, 'upload_mimes'] );

        if ( \version_compare( $wp_version, '5.1') >= 0){
            add_filter( 'wp_check_filetype_and_ext', [$this, 'check_filetype'],10,5);
        }else {
            add_filter( 'wp_check_filetype_and_ext', [$this, 'check_filetype'],10,4);
        }
    }

    public function upload_mimes( $mimes ) {
        $mimes['ts'] = 'video/mp2t';
        $mimes['m3u8'] = 'application/x-mpegURL';
        return $mimes;
    }

    public function check_filetype($data, $file, $filename,$mimes,$real_mime=null){
        $f_sp = explode(".", $filename);
        $f_exp_count  = count ($f_sp);

        if($f_exp_count <= 1){
            return $data;
        }else{
            $f_name = $f_sp[0];
            $ext  = $f_sp[$f_exp_count - 1];
        }

        if($ext == 'ts'){
            $type = 'video/mp2t';
            $proper_filename = '';
            return compact('ext', 'type', 'proper_filename');
        }else if($ext == 'm3u8'){
            $type = 'application/x-mpegURL';
            $proper_filename = '';
            return compact('ext', 'type', 'proper_filename');
        }else {
            return $data;
        }
    }
   
}