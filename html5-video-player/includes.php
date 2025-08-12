<?php

if (!class_exists('CSF') && file_exists(dirname(__FILE__) . "/admin/codestar-framework/codestar-framework.php")) {
    require_once dirname(__FILE__) . "/admin/codestar-framework/codestar-framework.php";
}
if (file_exists(__DIR__ . "/inc/functions.php")) {
    require_once __DIR__ . "/inc/functions.php";
}
if (h5vp_fs()->can_use_premium_code()) {
    if (file_exists(__DIR__ . '/inc/Base/duplicate-player.php')) {
        require_once __DIR__ . '/inc/Base/duplicate-player.php';
    }

    if (file_exists(__DIR__ . '/rest-api/index.php')) {
        require_once __DIR__ . '/rest-api/index.php';
    }
    if (file_exists(__DIR__ . "/admin/player-control-script.php")) {
        require_once __DIR__ . "/admin/player-control-script.php";
    }
}

if (file_exists(__DIR__ . '/elementor-widget.php')) {
    require_once(__DIR__ . '/elementor-widget.php');
}

if (file_exists(__DIR__ . '/blocks.php')) {
    require_once(__DIR__ . '/blocks.php');
}

if (file_exists(__DIR__ . "/inc/Rest/VideoController.php")) {
    require_once __DIR__ . "/inc/Rest/VideoController.php";
}

if (file_exists(__DIR__ . "/inc/Rest/Views.php")) {
    require_once __DIR__ . "/inc/Rest/Views.php";
}
