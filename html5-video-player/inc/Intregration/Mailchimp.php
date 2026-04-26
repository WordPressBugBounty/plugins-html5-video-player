<?php

namespace H5VP\Intregration;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Mailchimp
{
    /**
     * Mailchimp API base URL template.
     * The placeholder <dc> is replaced with the actual data center.
     */
    private const API_BASE = 'https://<dc>.api.mailchimp.com/3.0';

    /**
     * Get the stored Mailchimp API key from plugin options.
     *
     * @return string|null The API key, or null if not configured.
     */
    public static function get_api_key(): ?string
    {
        if (!function_exists('h5vp_get_option')) {
            return null;
        }

        $get_option = h5vp_get_option('h5vp_option');
        $api_key = $get_option('mailchimp_api_key', null);

        return !empty($api_key) ? $api_key : null;
    }

    /**
     * Extract the data center from the API key.
     *
     * Mailchimp API keys end with `-usXX` where `usXX` is the data center.
     *
     * @param string $api_key The Mailchimp API key.
     * @return string The data center identifier (e.g. "us11").
     */
    private static function get_data_center(string $api_key): string
    {
        $parts = explode('-', $api_key);
        return end($parts);
    }

    /**
     * Build the full API URL for a given endpoint.
     *
     * @param string $api_key  The Mailchimp API key.
     * @param string $endpoint The API endpoint path (e.g. "/lists").
     * @return string The full URL.
     */
    private static function build_url(string $api_key, string $endpoint): string
    {
        $dc = self::get_data_center($api_key);
        $base = str_replace('<dc>', $dc, self::API_BASE);
        return $base . '/' . ltrim($endpoint, '/');
    }

    /**
     * Make an authenticated request to the Mailchimp API.
     *
     * @param string $method   HTTP method (GET, POST, PUT, etc.).
     * @param string $endpoint API endpoint (e.g. "lists").
     * @param array  $body     Optional request body (will be JSON-encoded for non-GET).
     * @return array{success: bool, data: mixed, status_code: int}
     */
    private static function request(string $method, string $endpoint, array $body = []): array
    {
        $api_key = self::get_api_key();
        if (!$api_key) {
            return [
                'success' => false,
                'data' => 'Mailchimp API key is not configured.',
                'status_code' => 0,
            ];
        }

        $url = self::build_url($api_key, $endpoint);

        $args = [
            'method' => strtoupper($method),
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ];

        if (!empty($body) && strtoupper($method) !== 'GET') {
            $args['body'] = wp_json_encode($body);
        }

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
    }

    /**
     * Get all audience (list) entries from Mailchimp.
     *
     * Returns a simplified array of audiences with id, name, and member count.
     *
     * @param int $count  Number of lists to retrieve (max 1000). Default 100.
     * @param int $offset Pagination offset. Default 0.
     * @return array{success: bool, data: mixed}
     */
    public static function get_audiences(int $count = 100, int $offset = 0): array
    {
        // cache data for 1 hour - wp_set_cache 
        $cache_key = 'h5vp_mailchimp_audiences';
        $cache_value = get_transient($cache_key);

        if ($cache_value) {
            return $cache_value;
        }

        $result = self::request('GET', "lists?count={$count}&offset={$offset}");

        if (!$result['success']) {
            return $result;
        }

        $lists = $result['data']['lists'] ?? [];

        $audiences = array_map(function ($list) {
            return [
                'id' => $list['id'],
                'name' => $list['name'],
                'member_count' => $list['stats']['member_count'] ?? 0,
            ];
        }, $lists);

        set_transient($cache_key, $audiences, 3600);

        return $audiences;
    }

    /**
     * Add (or update) a subscriber in a Mailchimp audience.
     *
     * Uses the PUT method on the members endpoint (upsert), so calling this
     * for an already-subscribed email will update rather than error out.
     *
     * @param string $list_id The Mailchimp audience/list ID.
     * @param string $email   The subscriber email address.
     * @param string $status  Subscription status: 'subscribed', 'pending', 'unsubscribed', 'cleaned'.
     *                        Use 'pending' to trigger Mailchimp's double opt-in confirmation email.
     *                        Default is 'subscribed'.
     * @param array  $merge_fields Optional merge fields (e.g. ['FNAME' => 'John', 'LNAME' => 'Doe']).
     * @param array  $tags         Optional array of tag names to assign to the subscriber.
     * @return array{success: bool, data: mixed, status_code: int}
     */
    public static function add_subscriber(
        string $list_id,
        string $email,
        string $status = 'subscribed',
        array $merge_fields = [],
        array $tags = []
    ): array {
        $subscriber_hash = md5(strtolower(trim($email)));

        $body = [
            'email_address' => strtolower(trim($email)),
            'status_if_new' => $status,
        ];

        if (!empty($merge_fields)) {
            $body['merge_fields'] = $merge_fields;
        }

        $result = self::request('PUT', "lists/{$list_id}/members/{$subscriber_hash}", $body);

        // If tags were provided and the member was added/updated successfully, assign them.
        if ($result['success'] && !empty($tags)) {
            $tag_body = array_map(function ($tag) {
                return ['name' => $tag, 'status' => 'active'];
            }, $tags);

            self::request('POST', "lists/{$list_id}/members/{$subscriber_hash}/tags", [
                'tags' => $tag_body,
            ]);
        }

        return $result;
    }
}
