<?php

namespace H5VP;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Init
{

    public static function get_services()
    {
        return [
            Database\Init::class,
            Base\ExtendMime::class,
            Base\Menu::class,
            Base\GlobalChanges::class,
            Base\Analytics::class,
            Services\EnqueueAssets::class,
            Services\Shortcodes::class,
            Field\VideoPlayer::class,
            Field\Settings::class,
            Field\QuickPlayer::class,
            Field\PlaylistFieldPro::class,
            Model\Ajax::class,
            // Model\Analytics::class,
            Rest\Analytics::class,
            Rest\Generic::class,
            Base\Notice::class
        ];
    }

    public static function register_services()
    {
        foreach (self::get_services() as $class) {
            $services = self::instantiate($class);
            if (method_exists($services, 'register')) {
                $services->register();
            }
        }
    }

    public static function register_post_type()
    {
        self::instantiate(PostType\VideoPlayer::class)->register();
        if (h5vp_fs()->can_use_premium_code() && class_exists(PostType\H5VPPlaylistPro::class)) {
            self::instantiate(PostType\H5VPPlaylistPro::class)->register();
        }
    }

    private static function instantiate($class)
    {
        if (strpos($class, 'Pro') !== false && !h5vp_fs()->can_use_premium_code()) {
            return new \stdClass();
        }

        if (class_exists($class . "Pro") && h5vp_fs()->can_use_premium_code()) {
            $class = $class . "Pro";
        }

        if (class_exists($class)) {
            return new $class();
        }
        return new \stdClass();
    }
}
