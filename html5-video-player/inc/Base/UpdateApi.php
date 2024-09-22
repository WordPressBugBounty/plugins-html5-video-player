<?php
namespace H5VP\Base;

class UpdateApi{

    public $version;
    private $api_url = 'https://api.bplugins.com/wp-json/update-handler/v1/plugin/';

    public function register(){
        $this->version = H5VP_PRO_VER;
        if(!strpos(site_url(), 'localhost')){
            add_filter('plugins_api', [$this, 'plugins_api'], 10, 3);
            add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_plugin' ) );
            add_filter( 'pre_set_transient_update_plugins', array( $this, 'update_plugin' ) );
        }
    }

    public function plugins_api($api, $action, $args){
        if ( isset( $args->slug ) && 'html5-video-player-pro' === $args->slug ) {
            $api_check = $this->api_check();
            if ( is_object( $api_check ) ) {
                $api = $api_check;
            }
        }
        return $api;
    }

    public function api_check(){
        $raw_response = wp_remote_get( $this->api_url.'html5-video-player-pro.json');
        
        if ( is_wp_error( $raw_response ) ) {
            return false;
        }

        if ( ! empty( $raw_response['body'] ) ) {
            $raw_body = json_decode( $raw_response['body'], true );
            if ( $raw_body ) {
                return (object) $raw_body;
            }
        }

        return false;
    }


    public function update_plugin( $transient ) {
        $api_check = $this->api_check();
        if ( is_object( $api_check ) && isset($api_check->version) && version_compare( $this->version, $api_check->version, '<' ) ) {
            $transient->response['html5-video-player-pro/html5-video-player-pro.php'] = (object) array(
                'slug'        => 'html5-video-player-pro',
                'plugin'      => 'html5-video-player-pro/html5-video-player-pro.php',
                'new_version' => $api_check->version,
                'url'         => 'https://bplugins.com',
                'package'     => $api_check->download_link,
            );
        }
        return $transient;
    }

}