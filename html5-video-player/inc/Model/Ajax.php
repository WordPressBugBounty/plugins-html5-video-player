<?php

namespace H5VP\Model;

use PPV\Base\Help;

class Ajax
{

    protected static $_instance = null;
    private $params = [];
    private $requestType;
    private $requestMethod;
    private $requestModel;
    private $namespace = "H5VP\Model\\";
    private $model;

    public function __construct() {}

    public function register()
    {
        add_action('wp_ajax_h5vp_ajax_handler', [$this, 'prepareAjax']);
        add_action('wp_ajax_nopriv_h5vp_ajax_handler', [$this, 'prepareAjax']);

        // aws picker
        add_action('wp_ajax_h5vp_aws_picker', [$this, 'h5vp_aws_picker']);

        // from ajaxCall.php
        add_action('wp_ajax_h5vp_export_data', [$this, 'h5vp_export_data']);
        // add_action('wp_ajax_save_password', [$this, 'save_password']);

        add_action('wp_ajax_h5vp_save_preferred_editor', [$this, 'h5vp_save_preferred_editor']);
    }

    public static function instance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function isset($array, $key, $default = false)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }
        return $default;
    }

    public function prepareAjax()
    {
        if (!wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'wp_ajax')) {
            wp_send_json_error('403 Forbidden');
        }

        $this->params = $_POST;
        $this->requestType = 'POST';
        $this->proceedRequest();
    }

    public function proceedRequest()
    {
        $data = $this->params;

        $this->requestModel = $this->isset($data, 'model', 'Model');
        $this->requestMethod = $this->isset($data, 'method', 'invalid');
        $this->model = $this->namespace . $this->requestModel;

        if (!class_exists($this->model)) {
            wp_send_json_error('Model does not exists!');
        }

        $model = new $this->model();

        if (method_exists($model, $this->requestMethod)) {
            unset($this->params['method']);
            unset($this->params['action']);
            unset($this->params['nonce']);
            unset($this->params['model']);
            return $model->{$this->requestMethod}($this->params);
        } else {
            wp_send_json_error('Method does not exists!');
        }
    }

    public function invalid()
    {
        wp_send_json_error('invalid request!');
    }


    function user_has_role($user_id, $role_name)
    {
        $user_meta = get_userdata($user_id);
        $user_roles = $user_meta->roles;
        return in_array($role_name, $user_roles);
    }


    public function h5vp_export_data()
    {
        $nonce = sanitize_text_field($_POST['nonce']);

        if (!wp_verify_nonce($nonce, 'wp_ajax')) {
            wp_send_json_error('invalid request');
        }

        $id = sanitize_text_field($_POST['id']);
        $output['id'] = $id;
        if (!$id) die();

        $is_administrator = $this->user_has_role(get_current_user_id(), 'administrator');

        if (!$is_administrator) {
            echo wp_json_encode('you are not capable to export data');
            wp_die();
        }

        $post_type = get_post_type($id);

        if (in_array($post_type, ['videoplayer', 'h5vpplaylist'])) {
            $meta = get_post_meta($id);
            $post = get_post($id);
            unset($meta['_edit_last']);
            unset($meta['_edit_lock']);
            unset($meta['h5vp_total_views']);

            foreach ($meta as $key => $value) {
                $output[$key] = maybe_unserialize($value[0]);
            }
            $output['body'] = $post->post_content;
            echo wp_json_encode($output);
        }

        die();
    }

    function h5vp_aws_picker()
    {
        $aws = new \H5VP\Base\AWS();
        wp_send_json_success($aws->get_s3_list_objects());
    }

    public function h5vp_save_preferred_editor()
    {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_security_code'])), 'h5vp_security_key')) {
            wp_send_json_error('invalid request');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Invalid Authorization');
        }

        $editor = sanitize_text_field(wp_unslash($_POST['editor']));

        $options = get_option('h5vp_option', []);
        $options['h5vp_gutenberg_enable'] = $editor === 'gutenberg' ? '1' : '0';
        update_option('h5vp_option', $options);

        wp_send_json_success(['status' => 'success']);
    }
}
