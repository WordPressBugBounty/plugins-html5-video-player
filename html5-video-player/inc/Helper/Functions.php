<?php
namespace H5VP\Helper;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Functions
{

    protected static $settings = null;

    /**
     * get array value from option table
     */
    public static function getOptionDeep($option_name, $key, $default = false, $boolean = false)
    {
        $option = get_option($option_name);
        if (isset($option[$key]) && $option[$key] != '') {
            $result = $option[$key];
        } else {
            $result = $default;
        }

        if ($boolean) {
            return (bool) $result;
        }
        return $result;
    }


}