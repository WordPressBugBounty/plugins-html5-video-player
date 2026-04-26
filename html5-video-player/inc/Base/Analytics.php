<?php

namespace H5VP\Base;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

use H5VP\Model\View as View;
use H5VP\Model\Video as Video;

class Analytics
{
    public function register()
    {
        if (!is_admin()) {
            return;
        }
        $settings = get_option('h5vp_option');
        if (h5vp_fs()->can_use_premium_code() && (!isset($settings['enable_analytics']) || (isset($settings['enable_analytics']) && $settings['enable_analytics'] != '0'))) {
            add_action('admin_menu', [$this, 'admin_menu'], 15);
        }
    }

    public function admin_menu()
    {
        add_submenu_page('edit.php?post_type=videoplayer', 'Analytics', 'Analytics', 'manage_options', 'analytics', [$this, 'analyticsMain'], 100);
    }

    public function analyticsMain()
    {
        ?>
        <div id="h5vp-analytics-dashboard"></div>
        <?php
    }
}
