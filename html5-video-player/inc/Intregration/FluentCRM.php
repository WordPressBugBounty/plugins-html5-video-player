<?php

namespace H5VP\Intregration;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class FluentCRM
{
    /**
     * Check if the FluentCRM plugin is installed and active.
     *
     * @return bool True if FluentCRM's Subscriber model class exists.
     */
    public static function is_active(): bool
    {
        return class_exists('\FluentCrm\App\Models\Subscriber');
    }

    /**
     * Get all lists from FluentCRM.
     *
     * Returns a simplified array of lists with id, title, and subscriber count.
     * Results are cached for 1 hour using WordPress transients.
     *
     * @return array{success: bool, data: mixed}
     */
    public static function get_lists(): array
    {
        if (!self::is_active()) {
            return [
                'success' => false,
                'data' => 'FluentCRM is not installed or activated.',
            ];
        }

        $cache_key = 'h5vp_fluentcrm_lists';
        $cache_value = wp_cache_get($cache_key);

        if ($cache_value) {
            return $cache_value;
        }

        try {
            $lists = \FluentCrm\App\Models\Lists::all();

            $result = array_map(function ($list) {
                return [
                    'id' => $list->id,
                    'name' => $list->title,
                    'subscriber_count' => $list->totalCount() ?? 0,
                ];
            }, $lists->toArray() ? $lists->all() : []);

            $response = [
                'success' => true,
                'data' => $result,
            ];

            wp_cache_set($cache_key, $response);

            return $response;
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'data' => $th->getMessage(),
            ];
        }
    }

    /**
     * Get all tags from FluentCRM.
     *
     * Returns a simplified array of tags with id and title.
     * Results are cached for 1 hour using WordPress transients.
     *
     * @return array{success: bool, data: mixed}
     */
    public static function get_tags(): array
    {
        if (!self::is_active()) {
            return [
                'success' => false,
                'data' => 'FluentCRM is not installed or activated.',
            ];
        }

        $cache_key = 'h5vp_fluentcrm_tags';
        $cache_value = wp_cache_get($cache_key);

        if ($cache_value) {
            return $cache_value;
        }

        try {
            $tags = \FluentCrm\App\Models\Tag::all();

            $result = array_map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->title,
                ];
            }, $tags->toArray() ? $tags->all() : []);

            $response = [
                'success' => true,
                'data' => $result,
            ];

            wp_cache_set($cache_key, $response);

            return $response;
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'data' => $th->getMessage(),
            ];
        }
    }

    /**
     * Add (or update) a contact in FluentCRM.
     *
     * Uses FluentCRM's Subscriber model to create or update a contact,
     * optionally assigning lists and tags.
     *
     * @param string $email    The subscriber email address.
     * @param array  $lists    Optional array of list IDs to attach.
     * @param array  $tags     Optional array of tag IDs to attach.
     * @param string $status   Subscriber status: 'subscribed', 'pending', 'unsubscribed'. Default 'subscribed'.
     * @param array  $fields   Optional extra fields (e.g. ['first_name' => 'John', 'last_name' => 'Doe']).
     * @return array{success: bool, data: mixed}
     */
    public static function add_contact(
        string $email,
        array $lists = [],
        array $tags = [],
        string $status = 'subscribed',
        array $fields = []
    ): array {
        if (!self::is_active()) {
            return [
                'success' => false,
                'data' => 'FluentCRM is not installed or activated.',
            ];
        }

        try {
            $contact_data = array_merge(
                ['email' => strtolower(trim($email)), 'status' => $status],
                $fields
            );

            // Create or update the subscriber.
            $subscriber = (new \FluentCrm\App\Models\Subscriber)->updateOrCreate(
                ['email' => $contact_data['email']],
                $contact_data
            );

            if (!$subscriber) {
                return [
                    'success' => false,
                    'data' => 'Failed to create or update the subscriber.',
                ];
            }

            // Attach lists if provided.
            if (!empty($lists)) {
                $subscriber->attachLists($lists);
            }

            // Attach tags if provided.
            if (!empty($tags)) {
                $subscriber->attachTags($tags);
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $subscriber->id,
                    'email' => $subscriber->email,
                    'status' => $subscriber->status,
                ],
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'data' => $th->getMessage(),
            ];
        }
    }

    public static function get_integrations_data(): array
    {
        if (!self::is_active()) {
            return [
                'lists' => [],
                'tags' => [],
            ];
        }

        try {
            $lists = self::get_lists();
            $tags = self::get_tags();



            return [
                'lists' => $lists['data'] ?? [],
                'tags' => $tags['data'] ?? [],
            ];
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}
