<?php

namespace H5VP\Intregration;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class ActiveCampaign
{
    /**
     * Get the stored ActiveCampaign API URL and Key from plugin options.
     *
     * ActiveCampaign requires both an API URL (unique per account)
     * and an API key for authentication.
     *
     * @return array{url: string, key: string}|null Credentials array, or null if not configured.
     */
    public static function get_credentials(): ?array
    {
        if (!function_exists('h5vp_get_option')) {
            return null;
        }

        $get_option = h5vp_get_option('h5vp_option');
        $api_url = $get_option('active_campaign_url', null);
        $api_key = $get_option('active_campaign_api_key', null);

        if (empty($api_url) || empty($api_key)) {
            return null;
        }

        return [
            'url' => rtrim($api_url, '/'),
            'key' => $api_key,
        ];
    }

    /**
     * Build the full API URL for a given endpoint.
     *
     * @param string $base_url The account's API base URL.
     * @param string $endpoint The API endpoint path (e.g. "lists").
     * @return string The full URL.
     */
    private static function build_url(string $base_url, string $endpoint): string
    {
        return $base_url . '/api/3/' . ltrim($endpoint, '/');
    }

    /**
     * Make an authenticated request to the ActiveCampaign API (v3).
     *
     * @param string $method   HTTP method (GET, POST, PUT, DELETE).
     * @param string $endpoint API endpoint (e.g. "lists").
     * @param array  $body     Optional request body (will be JSON-encoded for non-GET).
     * @return array{success: bool, data: mixed, status_code: int}
     */
    private static function request(string $method, string $endpoint, array $body = []): array
    {
        $credentials = self::get_credentials();
        if (!$credentials) {
            return [
                'success' => false,
                'data' => 'ActiveCampaign API credentials are not configured.',
                'status_code' => 0,
            ];
        }


        $url = self::build_url($credentials['url'], $endpoint);

        $args = [
            'method' => strtoupper($method),
            'headers' => [
                'Api-Token' => $credentials['key'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
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
            return [
                'success' => false,
                'data' => $th->getMessage(),
                'status_code' => 0,
            ];
        }
    }

    /**
     * Get all lists from ActiveCampaign.
     *
     * Returns a simplified array of lists with id, name, and subscriber count.
     * Results are cached for 1 hour using WordPress transients.
     *
     * @param int $limit  Number of lists to retrieve (max 100). Default 100.
     * @param int $offset Pagination offset. Default 0.
     * @return array Simplified list array on success, or error array on failure.
     */
    public static function get_lists(int $limit = 100, int $offset = 0): array
    {
        $cache_key = 'h5vp_activecampaign_lists';
        $cache_value = get_transient($cache_key);

        if ($cache_value) {
            return $cache_value;
        }

        $result = self::request('GET', "lists?limit={$limit}&offset={$offset}");

        if (!$result['success']) {
            return $result;
        }

        $lists_data = $result['data']['lists'] ?? [];

        $lists = array_map(function ($list) {
            return [
                'id' => $list['id'],
                'name' => $list['name'],
                'subscriber_count' => $list['subscriber_count'] ?? 0,
            ];
        }, $lists_data);

        set_transient($cache_key, $lists, 3600);

        return $lists;
    }

    /**
     * Add a contact and subscribe them to a list in ActiveCampaign.
     *
     * This performs a two-step process:
     * 1. Create or update the contact via the "contact/sync" endpoint (upsert).
     * 2. Subscribe the contact to the specified list via "contactLists".
     *
     * @param string $email   The subscriber email address.
     * @param string $list_id The ActiveCampaign list ID to subscribe to.
     * @param array  $fields  Optional contact fields (e.g. ['firstName' => 'John', 'lastName' => 'Doe', 'phone' => '555-1234']).
     * @param array  $tags    Optional array of tag IDs to assign to the contact.
     * @return array{success: bool, data: mixed, status_code: int}
     */
    public static function add_subscriber(
        string $email,
        string $list_id,
        array $fields = [],
        array $tags = []
    ): array {
        // Step 1: Create or update the contact (sync / upsert).
        $contact_body = [
            'contact' => array_merge(
                ['email' => strtolower(trim($email))],
                $fields
            ),
        ];

        $contact_result = self::request('POST', 'contact/sync', $contact_body);

        if (!$contact_result['success']) {
            return $contact_result;
        }

        $contact_id = $contact_result['data']['contact']['id'] ?? null;

        if (!$contact_id) {
            return [
                'success' => false,
                'data' => 'Failed to retrieve contact ID after sync.',
                'status_code' => $contact_result['status_code'],
            ];
        }

        // Step 2: Subscribe the contact to the list.
        // Status 1 = subscribed, 2 = unsubscribed.
        $list_result = self::request('POST', 'contactLists', [
            'contactList' => [
                'list' => $list_id,
                'contact' => $contact_id,
                'status' => 1,
            ],
        ]);

        // Step 3: Assign tags if provided.
        if ($list_result['success'] && !empty($tags)) {
            foreach ($tags as $tag_id) {
                self::request('POST', 'contactTags', [
                    'contactTag' => [
                        'contact' => $contact_id,
                        'tag' => $tag_id,
                    ],
                ]);
            }
        }

        return $list_result;
    }
}
