<?php
namespace H5VP\Services;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use H5VP\Model\ViewsModel;
use WP_REST_Request;
use WP_REST_Response;

class AnalyticsService {

    protected ViewsModel $views;

    public function __construct(ViewsModel $views = null) {
        $this->views = $views ?: new ViewsModel();
    }

    /**
     * Main entry point called by REST controller.
     */
    public function handle(WP_REST_Request $request, ?array $token_data = null): WP_REST_Response {
        $body   = $request->get_json_params();
        $events = isset($body['events']) && is_array($body['events']) ? $body['events'] : [];

        if (!$events) {
            return new WP_REST_Response(['ok' => true, 'message' => 'No events'], 200);
        }

        // Extract identity (must be consistent in batch)
        $identity = $this->extractIdentity($events, $token_data);
        if ($identity instanceof WP_REST_Response) {
            return $identity;
        }

        [
            'video_id'    => $video_id,
            'session_id'  => $session_id,
            'instance_id' => $instance_id,
            'post_id'     => $post_id,
            'user_id'     => $user_id,
            'ip_address'  => $ip_address,
        ] = $identity;

        // Aggregate analytics signals
        $aggregate = $this->aggregateEvents($events);

        // Ignore micro/no-op batches
        if (
            $aggregate['add_seconds'] <= 0 &&
            $aggregate['progress_max'] <= 0 &&
            $aggregate['completed'] === 0
        ) {
            return new WP_REST_Response(['ok' => true, 'message' => 'Nothing to update'], 200);
        }

        // Find or create view row
        $row_id = $this->views->findBySessionInstanceVideo(
            $session_id,
            $instance_id,
            $video_id
        );

        if ($row_id) {
            $this->views->increment(
                $row_id,
                $aggregate['add_seconds'],
                $aggregate['progress_max'],
                $aggregate['completed'],
                $post_id
            );
        } else {
            $this->views->create([
                'user_id'      => $user_id,
                'session_id'   => $session_id,
                'post_id'      => $post_id ?: null,
                'instance_id'  => $instance_id,
                'completed'    => $aggregate['completed'],
                'progress_max' => $aggregate['progress_max'],
                'duration'     => $aggregate['add_seconds'],
                'video_id'     => $video_id,
                'ip_address'   => $ip_address,
            ]);
        }

        return new WP_REST_Response(['ok' => true], 200);
    }

    /**
     * Extract and validate identity data from events + token.
     */
    protected function extractIdentity(array $events, ?array $token_data) {
        $first = $events[0];

        $video_id    = isset($first['video_id']) ? absint($first['video_id']) : 0;
        $session_id  = isset($first['session_id']) ? sanitize_text_field($first['session_id']) : '';
        $instance_id = isset($first['instance_id']) ? sanitize_text_field($first['instance_id']) : '';
        $post_id     = isset($first['post_id']) ? absint($first['post_id']) : 0;

        if (!$video_id) {
            return new WP_REST_Response(['ok' => false, 'error' => 'Missing video_id'], 400);
        }
        if (!$session_id) {
            return new WP_REST_Response(['ok' => false, 'error' => 'Missing session_id'], 400);
        }
        if (!$instance_id) {
            return new WP_REST_Response(['ok' => false, 'error' => 'Missing instance_id'], 400);
        }

        // user_id resolution (token > WP)
        $user_id = null;
        if ($token_data && isset($token_data['uid'])) {
            $uid = absint($token_data['uid']);
            $user_id = $uid ?: null;
        } else {
            $uid = get_current_user_id();
            $user_id = $uid ?: null;
        }

        // post_id fallback from token
        if (!$post_id && $token_data && isset($token_data['pid'])) {
            $post_id = absint($token_data['pid']);
        }

        // IP
        $ip_address = $this->getIpAddress() ?: '';

        return [
            'video_id'    => $video_id,
            'session_id'  => $session_id,
            'instance_id' => $instance_id,
            'post_id'     => $post_id,
            'user_id'     => $user_id,
            'ip_address'  => $ip_address,
        ];
    }

    /**
     * Aggregate batch events into DB-ready values.
     */
    protected function aggregateEvents(array $events): array {
        $watched = 0;
        $progress_max = 0;
        $completed = 0;

        foreach ($events as $ev) {
            $event = isset($ev['event']) ? sanitize_text_field($ev['event']) : '';

            // watch time
            if ($event === 'watch_delta') {
                $sec = isset($ev['meta']['seconds']) ? (float)$ev['meta']['seconds'] : 0;
                if ($sec > 0 && $sec < 10) {
                    $watched += $sec;
                }
            }

            // progress
            if (isset($ev['progress_max'])) {
                $pm = absint($ev['progress_max']);
                if ($pm > $progress_max) $progress_max = $pm;
            }

            if ($event === 'milestone' && isset($ev['meta']['milestone'])) {
                $m = absint($ev['meta']['milestone']);
                if ($m > $progress_max) $progress_max = $m;
            }

            // completion
            if (!empty($ev['completed']) && (int)$ev['completed'] === 1) {
                $completed = 1;
            }

            if ($event === 'ended') {
                $completed = 1;
                $progress_max = max($progress_max, 100);
            }
        }

        return [
            'add_seconds' => (int) round($watched),
            'progress_max'=> min(100, $progress_max),
            'completed'   => $completed,
        ];
    }

    /**
     * IP helper (same as your controller).
     */
    protected function getIpAddress(): ?string {
        $keys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($keys as $k) {
            if (!empty($_SERVER[$k])) {
                $ip = sanitize_text_field( wp_unslash(  $_SERVER[$k] ));
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return null;
    }
}
