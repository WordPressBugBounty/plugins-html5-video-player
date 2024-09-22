<?php
namespace H5VP;


class Init{
   
    public static function get_services(){
        return [
            Database\Init::class,
            Base\ExtendMime::class,
            Base\AdminNotice::class,
            Base\GlobalChanges::class,
            Base\Loader::class,
            PostType\VideoPlayer::class,
            PostType\H5VPPlaylistPro::class,
            Services\EnqueueAssets::class,
            Services\Shortcodes::class,
            // Services\Playlist::class,
            Field\VideoPlayer::class,
            Field\Settings::class,
            Field\QuickPlayer::class,
            Field\PlaylistFieldPro::class,
            Model\Ajax::class
        ];
    }

    public static function register_services(){
        foreach(self::get_services() as $class){
            $services = self::instantiate($class);
            if(method_exists($services, 'register')){
                $services->register();
            }
        }

    }

    private static function instantiate($class){
        if(strpos($class, 'Pro') !== false && !h5vp_fs()->can_use_premium_code()){
            return new \stdClass();
        }

        if(class_exists($class."Pro") && h5vp_fs()->can_use_premium_code()){
            $class = $class."Pro";
        }


        if(class_exists($class)){
            return new $class();
        }
        return new \stdClass();
    }
}


