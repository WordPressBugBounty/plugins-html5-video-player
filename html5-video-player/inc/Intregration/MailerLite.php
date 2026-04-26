<?php

namespace H5VP\Intregration;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class MailerLite
{
    /**
     * MailerLite API base URL (new API, not Classic).
     */
    private const API_BASE = 'https://connect.mailerlite.com/api';

    /**
     * Get the stored MailerLite API key from plugin options.
     *
     * @return string|null The API key, or null if not configured.
     */
    public static function get_api_key(): ?string
    {
        if (!function_exists('h5vp_get_option')) {
            return null;
        }

        $get_option = h5vp_get_option('h5vp_option');
        $api_key = $get_option('mailerlite_api_key', null);

        return !empty($api_key) ? $api_key : null;
    }

    /**
     * Build the full API URL for a given endpoint.
     *
     * @param string $endpoint The API endpoint path (e.g. "groups").
     * @return string The full URL.
     */
    private static function build_url(string $endpoint): string
    {
        return self::API_BASE . '/' . ltrim($endpoint, '/');
    }

    /**
     * Make an authenticated request to the MailerLite API.
     *
     * @param string $method   HTTP method (GET, POST, PUT, DELETE).
     * @param string $endpoint API endpoint (e.g. "subscribers").
     * @param array  $body     Optional request body (will be JSON-encoded for non-GET).
     * @return array{success: bool, data: mixed, status_code: int}
     */
    private static function request(string $method, string $endpoint, array $body = []): array
    {
        $api_key = self::get_api_key();
        if (!$api_key) {
            return [
                'success' => false,
                'data' => 'MailerLite API key is not configured.',
                'status_code' => 0,
            ];
        }

        $url = self::build_url($endpoint);

        $args = [
            'method' => strtoupper($method),
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'MyPHPApp/1.0'
            ],
            'timeout' => 30,
        ];

        if (!empty($body) && strtoupper($method) !== 'GET') {
            $args['body'] = wp_json_encode($body);
        }

        try {
            $response = wp_remote_request($url, $args);

            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'data' => $response->get_error_message(),
                    'status_code' => 0,
                ];
            }

            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            return [
                'success' => $status_code >= 200 && $status_code < 300,
                'data' => $response_body,
                'status_code' => $status_code,
            ];
        } catch (\Throwable $th) {
            return [$th->getMessage()];
        }
    }

    /**
     * Get all groups from MailerLite.
     *
     * Returns a simplified array of groups with id, name, and active subscriber count.
     * Results are cached for 1 hour using WordPress transients.
     *
     * @param int $limit Number of groups to retrieve per page (max 100). Default 100.
     * @return array{success: bool, data: mixed}|array Simplified group list on success.
     */
    public static function get_groups(int $limit = 100): array
    {
        $cache_key = 'h5vp_mailerlite_groups';
        $cache_value = get_transient($cache_key);

        if ($cache_value) {
            return $cache_value;
        }

        $result = self::request('GET', "groups?limit={$limit}&sort=name");

        if (!$result['success']) {
            return $result;
        }

        $groups_data = $result['data']['data'] ?? [];

        $groups = array_map(function ($group) {
            return [
                'id' => $group['id'],
                'name' => $group['name'],
                'active_count' => $group['active_count'] ?? 0,
            ];
        }, $groups_data);

        set_transient($cache_key, $groups, 3600);

        return $groups;
    }

    /**
     * Add (or update) a subscriber in MailerLite, optionally assigning to groups.
     *
     * Uses the POST /subscribers endpoint which acts as an upsert — if the email
     * already exists, its data is updated non-destructively.
     *
     * @param string $email     The subscriber email address.
     * @param array  $group_ids Optional array of group IDs to assign the subscriber to.
     * @param array  $fields    Optional fields (e.g. ['name' => 'John', 'last_name' => 'Doe']).
     * @return array{success: bool, data: mixed, status_code: int}
     */
    public static function add_subscriber(
        string $email,
        array $group_ids = [],
        array $fields = []
    ): array {
        $body = [
            'email' => strtolower(trim($email)),
        ];

        if (!empty($group_ids)) {
            $body['groups'] = $group_ids;
        }

        if (!empty($fields)) {
            $body['fields'] = $fields;
        }

        return self::request('POST', 'subscribers', $body);
    }
}
