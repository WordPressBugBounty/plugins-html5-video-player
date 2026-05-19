<?php

namespace H5VP\Services;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly


class EnqueueAssets
{
    protected static $_instance = null;

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public static function instance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    public function enqueueAdminAssets($hook_suffix)
    {
        // Resolve the post type from the current screen. This avoids reading
        // the raw $_GET['post_type'] query var (and the nonce-verification
        // warning that comes with it) and works for edit.php / post-new.php /
        // post.php alike.
        $screen = get_current_screen();
        $post_type = ($screen && $screen->post_type) ? $screen->post_type : get_post_type();

        $isInPages = in_array($hook_suffix, array(
            'videoplayer_page_h5vp-support',
            'videoplayer_page_html5vp_settings',
            'videoplayer_page_html5vp_quick_player',
            'videoplayer_page_free-plugins-from-bplugins',
            'videoplayer_page_premium-plugins',
            'videoplayer_page_analytics',
        ), true);
        $isInPostType = in_array($hook_suffix, array('post.php', 'post-new.php', 'edit.php'), true)
            && 'videoplayer' === $post_type;

        if (!$isInPages && !$isInPostType) {
            return;
        }

        wp_enqueue_script('h5vp-admin', H5VP_PLUGIN_DIR . 'build/admin.js', array('jquery', 'react', 'react-dom', 'wp-util'), H5VP_VER, true);
        wp_enqueue_style('h5vp-admin', H5VP_PLUGIN_DIR . 'build/admin.css', array(), H5VP_VER);

        wp_localize_script('h5vp-admin', 'h5vpAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'website' => site_url(),
            'email' => get_option('admin_email'),
            'nonce' => wp_create_nonce('h5vp_admin'),
        ));
    }
}
