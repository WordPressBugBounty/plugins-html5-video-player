<?php
namespace H5VP\Helper;

class Plugin{

    public static $version = H5VP_PRO_VER;
    public static $latestVersion = null;

    public static function dir(){
        return plugin_dir_url(__FILE__);
    }

    public static function path(){
        return plugin_dir_path(__FILE__);
    }

    public static function version(){
        return self::$version;
    }

    public static function getLatestVersion(){
        if(self::$latestVersion !== null) return self::$latestVersion;
        $version = wp_remote_get('https://bplugins.com/wp-json/version/v1/product/52654');

        if(!is_array($version) || !array_key_exists('body', $version)) return false;
        $version = json_decode($version['body']);
        if(!isset($version->version)) return false;

        self::$latestVersion = $version->version;
        return $version->version;
    }

    
}