<?php
namespace H5VP\Base;

use H5VP\Helper\Plugin;

class AdminNotice{
    
    public function register(){
        if(\is_admin()){
            add_action('admin_notices', [$this, 'dataImported']);
            add_action('init', [$this, 'init']);
        }
    }

    public function dataImported(){
        $screen = get_current_screen();
        if($screen->base === 'plugins' && isset($_GET['h5ap-import'])){
            echo "<div class='notice notice-success is-dismissible'><p>Data Imported successfully. have fun!</p></div>";
        }
    }

    public function init(){
        if(isset($_GET['h5vp-notice-import']) && $_GET['h5vp-notice-import'] == 'true'){
            update_option('h5vp-notice-import', true);
        }
    }
}
