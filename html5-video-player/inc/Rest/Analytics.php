<?php
namespace H5VP\Rest;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use H5VP\Services\AnalyticsService;
use H5VP\Model\ViewsModel;

class Analytics{

    public function register(){
        add_action('rest_api_init', [$this, 'h5vp_analytics']);
    }

    function h5vp_analytics(){
        register_rest_route('h5vp/v1', '/analytics', [
            'methods'  => 'POST',
            'callback' => [$this, 'h5vp_handle_analytics'],
            'permission_callback' => function (\WP_REST_Request $request) {
                // 1) normal fetch path (has nonce header)
                $nonce = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE'] ?? ''));
                if ($nonce && wp_verify_nonce($nonce, 'wp_rest')) {
                    return true;
                }

                // 2) beacon path: token in JSON body (or query)
                $params = $request->get_json_params();
                $token = $params['token'] ?? ($request->get_param('token') ?? '');

                $data = $this->h5vp_verify_analytics_token($token);
                return $data !== false;
            },
        ]);
    }

    public function h5vp_handle_analytics(\WP_REST_Request $request) {
        $body = $request->get_json_params();
        $token = $body['token'] ?? '';

        $token_data = $token
            ? $this->h5vp_verify_analytics_token($token)
            : null;

        if ($token_data === false) {
            $token_data = null;
        }

        $service = new AnalyticsService(new ViewsModel());

        return $service->handle($request, $token_data);
    }

    function h5vp_make_analytics_token(array $data) {
        // token payload + signature
        $payload = wp_json_encode($data);
        $sig = hash_hmac('sha256', $payload, wp_salt('auth'));
        // base64url encode
        $b64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        return $b64 . '.' . $sig;
    }

    function h5vp_verify_analytics_token($token) {
        if (!is_string($token) || strpos($token, '.') === false) return false;

        [$b64, $sig] = explode('.', $token, 2);
        $payload_json = base64_decode(strtr($b64, '-_', '+/'));
        if (!$payload_json) return false;

        $expected = hash_hmac('sha256', $payload_json, wp_salt('auth'));
        if (!hash_equals($expected, $sig)) return false;

        $data = json_decode($payload_json, true);
        if (!is_array($data)) return false;

        $iat = isset($data['iat']) ? (int)$data['iat'] : 0;
        $ttl = isset($data['ttl']) ? (int)$data['ttl'] : 0;
        if ($iat <= 0 || $ttl <= 0) return false;

        // expiry
        if (time() > ($iat + $ttl)) return false;

        return $data; // return decoded payload if valid
    }   
}