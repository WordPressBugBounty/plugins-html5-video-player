<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if (!class_exists('CSF') && file_exists(dirname(__FILE__) . "/vendor/codestar-framework/codestar-framework.php")) {
    require_once dirname(__FILE__) . "/vendor/codestar-framework/codestar-framework.php";
}
// if (file_exists(__DIR__ . "/inc/functions.php")) {
//     require_once __DIR__ . "/inc/functions.php";
// }
// if (h5vp_fs()->can_use_premium_code()) {
//     if (file_exists(__DIR__ . '/inc/Base/duplicate-player.php')) {
//         require_once __DIR__ . '/inc/Base/duplicate-player.php';
//     }

//     if (file_exists(__DIR__ . '/rest-api/index.php')) {
//         require_once __DIR__ . '/rest-api/index.php';
//     }
//     if (file_exists(__DIR__ . "/admin/player-control-script.php")) {
//         require_once __DIR__ . "/admin/player-control-script.php";
//     }
// }

// if (file_exists(__DIR__ . '/elementor-widget.php')) {
//     require_once(__DIR__ . '/elementor-widget.php');
// }

// if (file_exists(__DIR__ . '/blocks.php')) {
//     require_once(__DIR__ . '/blocks.php');
// }

// if (file_exists(__DIR__ . "/inc/Rest/VideoController.php")) {
//     require_once __DIR__ . "/inc/Rest/VideoController.php";
// }

// if (file_exists(__DIR__ . "/inc/Rest/Views.php")) {
//     require_once __DIR__ . "/inc/Rest/Views.php";
// }


// if (file_exists(__DIR__ . "/inc/Model/ViewsModel.php")) {
//     require_once __DIR__ . "/inc/Model/ViewsModel.php";
// }

$include_files = array(
    __DIR__ . "/blocks.php",
    __DIR__ . "/elementor-widget.php",
    __DIR__ . "/inc/functions.php",
    __DIR__ . "/inc/Rest/VideoController.php",
    __DIR__ . "/inc/Rest/Views.php",
    __DIR__ . "/inc/Model/ViewsModel.php",
    __DIR__ . "/inc/Model/VideoModel.php",
);

if (h5vp_fs()->can_use_premium_code()) {
    $include_files[] = __DIR__ . '/inc/Base/duplicate-player.php';
    $include_files[] = __DIR__ . "/admin/player-control-script.php";
}

foreach ($include_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}


