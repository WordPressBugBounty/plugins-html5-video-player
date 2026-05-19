<?php

namespace H5VP\Model;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Ajax
{

    protected static $_instance = null;
    private $params = [];
    private $requestType;
    private $requestMethod;
    private $requestModel;
    private $model;

    public function __construct()
    {
    }

    public function register()
    {
        add_action('wp_ajax_h5vp_ajax_handler', [$this, 'prepareAjax']);


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

    private function getAllowedActions(): array
    {
        return [
            'Video' => ['class' => \H5VP\Model\Video::class, 'methods' => ['create']],
        ];
    }

    public function prepareAjax()
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('403 Forbidden');
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'h5vp_ajax_handler')) {
            wp_send_json_error('403 Forbidden');
        }

        // Raw request data. Models are responsible for whitelisting/sanitizing
        // every field before it reaches the DB layer.
        $this->params = wp_unslash($_POST);
        $this->requestType = 'POST';
        $this->proceedRequest();
    }

    public function proceedRequest()
    {
        $data = $this->params;

        $this->requestModel = sanitize_text_field($this->isset($data, 'model', ''));
        $this->requestMethod = sanitize_text_field($this->isset($data, 'method', ''));

        $allowedActions = $this->getAllowedActions();

        if (!isset($allowedActions[$this->requestModel]) || !\in_array($this->requestMethod, $allowedActions[$this->requestModel]['methods'], true)) {
            wp_send_json_error('Action not allowed!');
        }

        // The class name comes solely from the server-side allow-list — request
        // data is only ever used as an array key, never to build a class name.
        $this->model = $allowedActions[$this->requestModel]['class'];

        if (!class_exists($this->model)) {
            wp_send_json_error('Model does not exists!');
        }

        $model = new $this->model();


        if (method_exists($model, $this->requestMethod)) {
            unset($this->params['method'], $this->params['action'], $this->params['nonce'], $this->params['model']);

            $result = $model->{$this->requestMethod}($this->params);
            wp_send_json_success($result);
        } else {
            wp_send_json_error('Method does not exists!');
        }


    }

}
