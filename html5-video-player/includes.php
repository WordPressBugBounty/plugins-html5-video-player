<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if (!class_exists('CSF') && file_exists(dirname(__FILE__) . "/vendor/codestar-framework/codestar-framework.php")) {
    require_once dirname(__FILE__) . "/vendor/codestar-framework/codestar-framework.php";
}

$include_files = array(
    __DIR__ . "/inc/Base/LicenseActivation.php",
    __DIR__ . "/blocks.php",
    __DIR__ . "/elementor-widget.php",
    __DIR__ . "/inc/functions.php",
    __DIR__ . "/inc/Rest/VideoController.php",
    __DIR__ . "/inc/Rest/Views.php",
    __DIR__ . "/inc/Model/ViewsModel.php",
    __DIR__ . "/inc/Model/VideoModel.php",
);

if (h5vp_fs()->can_use_premium_code()) {
    $include_files = wp_parse_args($include_files, array(
        __DIR__ . "/inc/Base/duplicate-player.php",
        __DIR__ . "/admin/player-control-script.php",
    ));
}

foreach ($include_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}


