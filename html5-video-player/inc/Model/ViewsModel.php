<?php

namespace H5VP\Model;

if (! defined('ABSPATH')) exit; // Exit if accessed directly

use wpdb;

class ViewsModel
{

    /** @var wpdb */
    protected $db;

    /** @var string */
    protected $table;
    protected $videosTable;

    public function __construct(?wpdb $db = null)
    {
        global $wpdb;
        $this->db = $db ?: $wpdb;
        $this->table = $this->db->prefix . 'h5vp_views';
        $this->videosTable = $this->db->prefix . 'h5vp_videos';
    }

    /**
     * Find latest view row by (session_id, instance_id, video_id).
     * This is the correct identity for multiple videos + multiple players.
     */
    public function findBySessionInstanceVideo(string $session_id, string $instance_id, int $video_id): ?int
    {
        $session_id  = sanitize_text_field($session_id);
        $instance_id = sanitize_text_field($instance_id);
        $video_id    = absint($video_id);

        if (!$session_id || !$instance_id || !$video_id) return null;

        $sql = $this->db->prepare(
            "SELECT id FROM {$this->table}
             WHERE session_id = %s
               AND instance_id = %s
               AND video_id = %d
               AND deleted_at IS NULL
             ORDER BY id DESC
             LIMIT 1",
            $session_id,
            $instance_id,
            $video_id
        );

        $id = $this->db->get_var($sql);
        return $id ? (int) $id : null;
    }

    /**
     * Create a new view row.
     */
    public function create(array $data): int
    {
        $row = $this->sanitizeRow($data);

        // Required
        if (empty($row['session_id']) || empty($row['video_id']) || !isset($row['duration'])) {
            return 0;
        }

        $ok = $this->db->insert(
            $this->table,
            $row,
            $this->insertFormats($row)
        );

        return $ok ? (int) $this->db->insert_id : 0;
    }

    /**
     * Increment duration and update progress/completed/post_id.
     * - duration += $add_seconds
     * - progress_max = max(progress_max, $progress_max)
     * - completed becomes 1 if completed=1 in update
     * - post_id set if provided (>0)
     */
    public function increment(int $id, int $add_seconds = 0, int $progress_max = 0, int $completed = 0, int $post_id = 0): bool
    {
        $id = absint($id);
        if (!$id) return false;

        $add_seconds = max(0, (int)$add_seconds);
        $progress_max = max(0, min(100, (int)$progress_max));
        $completed = $completed ? 1 : 0;
        $post_id = absint($post_id);

        $sets = [];
        $args = [];

        if ($add_seconds > 0) {
            $sets[] = "duration = duration + %d";
            $args[] = $add_seconds;
        }

        if ($progress_max > 0) {
            $sets[] = "progress_max = GREATEST(progress_max, %d)";
            $args[] = $progress_max;
        }

        if ($completed === 1) {
            $sets[] = "completed = 1";
        }

        if ($post_id > 0) {
            $sets[] = "post_id = %d";
            $args[] = $post_id;
        }

        // Nothing to update
        if (!$sets) return true;

        $args[] = $id;

        $sql = "UPDATE {$this->table}
                SET " . implode(', ', $sets) . "
                WHERE id = %d AND deleted_at IS NULL";

        $result = $this->db->query($this->db->prepare($sql, ...$args));
        return $result !== false;
    }

    /**
     * Soft delete a view row.
     */
    public function softDelete(int $id): bool
    {
        $id = absint($id);
        if (!$id) return false;

        $now = current_time('mysql', 1);

        $result = $this->db->query($this->db->prepare(
            "UPDATE {$this->table}
             SET deleted_at = %s
             WHERE id = %d AND deleted_at IS NULL",
            $now,
            $id
        ));

        return $result !== false;
    }

    /**
     * Optional: update updated_at (your schema already auto-updates, so not needed)
     */
    public function touch(int $id): bool
    {
        $id = absint($id);
        if (!$id) return false;

        $result = $this->db->query($this->db->prepare(
            "UPDATE {$this->table}
             SET updated_at = updated_at
             WHERE id = %d AND deleted_at IS NULL",
            $id
        ));

        return $result !== false;
    }

    /**
     * Sanitize/normalize input row for insert.
     */
    protected function sanitizeRow(array $data): array
    {
        $row = [];

        // Nullable
        $row['user_id'] = isset($data['user_id']) ? (absint($data['user_id']) ?: null) : null;
        $row['post_id'] = isset($data['post_id']) ? (absint($data['post_id']) ?: null) : null;

        // Required
        $row['session_id'] = isset($data['session_id']) ? sanitize_text_field($data['session_id']) : '';
        $row['video_id']   = isset($data['video_id']) ? absint($data['video_id']) : 0;
        $row['duration']   = isset($data['duration']) ? max(0, (int)$data['duration']) : 0;

        // Optional
        $row['instance_id']  = isset($data['instance_id']) ? sanitize_text_field($data['instance_id']) : null;
        $row['completed']    = isset($data['completed']) ? ((int)$data['completed'] ? 1 : 0) : 0;

        $pm = isset($data['progress_max']) ? (int)$data['progress_max'] : 0;
        $row['progress_max'] = max(0, min(100, $pm));

        $row['ip_address']   = isset($data['ip_address']) ? sanitize_text_field($data['ip_address']) : '';

        // Let MySQL defaults handle created_at/updated_at
        $row['deleted_at'] = null;

        return $row;
    }

    /**
     * Formats for wpdb->insert based on fields present.
     */
    protected function insertFormats(array $row): array
    {
        $formats = [];
        foreach ($row as $key => $val) {
            switch ($key) {
                case 'user_id':
                case 'post_id':
                case 'video_id':
                case 'duration':
                case 'completed':
                case 'progress_max':
                    $formats[] = '%d';
                    break;
                case 'deleted_at':
                    $formats[] = '%s';
                    break;
                default:
                    // session_id, instance_id, ip_address
                    $formats[] = '%s';
            }
        }
        return $formats;
    }

    public function getById(int $id): ?array
    {
        $id = absint($id);
        if (!$id) return null;

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table}
            WHERE id = %d AND deleted_at IS NULL
            LIMIT 1",
            $id
        );

        $row = $this->db->get_row($sql, ARRAY_A);
        return $row ?: null;
    }

    public function getByVideo(int $video_id, ?string $from = null, ?string $to = null, int $limit = 100, int $offset = 0): array
    {
        $video_id = absint($video_id);
        if (!$video_id) return [];

        $where = ["video_id = %d", "deleted_at IS NULL"];
        $args  = [$video_id];

        if ($from) {
            $where[] = "created_at >= %s";
            $args[] = $from;
        }

        if ($to) {
            $where[] = "created_at <= %s";
            $args[] = $to;
        }

        $args[] = $limit;
        $args[] = $offset;

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table}
            WHERE " . implode(" AND ", $where) . "
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
            ...$args
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function getByPost(int $post_id, ?string $from = null, ?string $to = null): array
    {
        $post_id = absint($post_id);
        if (!$post_id) return [];

        $where = ["post_id = %d", "deleted_at IS NULL"];
        $args  = [$post_id];

        if ($from) {
            $where[] = "created_at >= %s";
            $args[] = $from;
        }

        if ($to) {
            $where[] = "created_at <= %s";
            $args[] = $to;
        }

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table}
            WHERE " . implode(" AND ", $where) . "
            ORDER BY created_at DESC",
            ...$args
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function getByUser(array $args = []): array
    {
        $args = $this->prepare_date_range($args);
        $user_id = absint($args['user_id']);
        $from = $args['from'] ?? null;
        $to = $args['to'] ?? null;

        if (!$user_id) return [];

        $where = ["user_id = %d", "deleted_at IS NULL", "duration != 0"];
        $args  = [$user_id];

        if ($from) {
            $where[] = "created_at >= %s";
            $args[] = $from;
        }

        if ($to) {
            $where[] = "created_at <= %s";
            $args[] = $to;
        }

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table}
            WHERE " . implode(" AND ", $where) . "
            ORDER BY created_at DESC",
            ...$args
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function count(array $filters = []): int
    {
        $where = ["deleted_at IS NULL"];
        $args  = [];

        if (!empty($filters['video_id'])) {
            $where[] = "video_id = %d";
            $args[] = absint($filters['video_id']);
        }

        if (!empty($filters['post_id'])) {
            $where[] = "post_id = %d";
            $args[] = absint($filters['post_id']);
        }

        if (!empty($filters['user_id'])) {
            $where[] = "user_id = %d";
            $args[] = absint($filters['user_id']);
        }

        if (!empty($filters['from'])) {
            $where[] = "created_at >= %s";
            $args[] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $where[] = "created_at <= %s";
            $args[] = $filters['to'];
        }

        $sql = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table}
            WHERE " . implode(" AND ", $where),
            ...$args
        );

        return (int) $this->db->get_var($sql);
    }


    public function getVideoStats(array $args = []): array
    {
        $video_id = absint($args['video_id']);
        $from = $args['from'] ?? null;
        $to = $args['to'] ?? null;
        if (!$video_id) return [];

        $where = ["video_id = %d", "deleted_at IS NULL"];
        $args  = [$video_id];

        if ($from) {
            $where[] = "created_at >= %s";
            $args[] = $from;
        }

        if ($to) {
            $where[] = "created_at <= %s";
            $args[] = $to;
        }

        $sql = $this->db->prepare(
            "SELECT
                COUNT(*)                         AS views,
                COUNT(DISTINCT session_id)       AS sessions,
                COUNT(DISTINCT user_id)          AS users,
                SUM(duration)                    AS total_watch_time,
                AVG(duration)                    AS avg_watch_time,
                SUM(completed)                   AS completed_views,
                AVG(progress_max)                AS avg_progress
            FROM {$this->table}
            WHERE " . implode(" AND ", $where),
            ...$args
        );

        return $this->db->get_row($sql, ARRAY_A) ?: [];
    }

    public function getCompletionRate(array $args = []): float
    {
        $args = $this->prepare_date_range($args);
        $video_id = absint($args['video_id']);

        if (!$video_id) return 0.0;

        $stats = $this->getVideoStats($args);

        if (empty($stats['views'])) return 0.0;

        return round(
            // ((int)$stats['completed_views'] / (int)$stats['views') * 100,
            2
        );
    }


    // public function getTopVideos(int $limit = 10,?string $from = null,?string $to = null): array {
    public function getTopVideos(array $args = []): array
    {

        $limit = $args['limit'] ?? 10;
        $from = $args['from'] ?? null;
        $to = $args['to'] ?? null;

        $where = ["deleted_at IS NULL"];
        $args  = [];

        if ($from) {
            $where[] = "created_at >= %s";
            $args[] = $from;
        }

        if ($to) {
            $where[] = "created_at <= %s";
            $args[] = $to;
        }

        $args[] = $limit;


        $sql = $this->db->prepare(
            "SELECT
                video_id,
                COUNT(*) AS views,
                SUM(duration) AS total_watch_time,
                AVG(progress_max) AS avg_progress
            FROM {$this->table}
            WHERE " . implode(" AND ", $where) . "
            GROUP BY video_id
            ORDER BY total_watch_time DESC
            LIMIT %d",
            ...$args
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function getViews(array $args = []): array
    {
        $defaults = [
            'video_id'   => null,
            'post_id'    => null,
            'user_id'    => null,
            'session_id' => null,
            'from'       => null,  // 'YYYY-mm-dd HH:ii:ss'
            'to'         => null,
            'with_video' => false,
            'limit'      => 50,
            'offset'     => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ["{$this->table}.deleted_at IS NULL"];
        $where[] = "{$this->table}.duration > 0";
        $binds = [];

        if (!empty($args['video_id'])) {
            $where[] = "{$this->table}.video_id = %d";
            $binds[] = absint($args['video_id']);
        }

        if (!empty($args['post_id'])) {
            $where[] = "{$this->table}.post_id = %d";
            $binds[] = absint($args['post_id']);
        }

        if (!empty($args['user_id'])) {
            $where[] = "{$this->table}.user_id = %d";
            $binds[] = absint($args['user_id']);
        }

        if (!empty($args['session_id'])) {
            $where[] = "{$this->table}.session_id = %s";
            $binds[] = sanitize_text_field($args['session_id']);
        }

        if (!empty($args['from'])) {
            $where[] = "{$this->table}.created_at >= %s";
            $binds[] = $args['from'];
        }

        if (!empty($args['to'])) {
            $where[] = "{$this->table}.created_at <= %s";
            $binds[] = $args['to'];
        }

        $limit  = max(1, (int)$args['limit']);
        $offset = max(0, (int)$args['offset']);

        $binds[] = $limit;
        $binds[] = $offset;

        // ✅ alias views.video_id to avoid name clash with joined v.id AS video_id
        $sql = $this->db->prepare(
            "SELECT
                {$this->table}.*,
                {$this->table}.video_id AS view_video_id
                {$this->videoSelectSql((bool)$args['with_video'])}
            FROM {$this->table}
            {$this->videoJoinSql((bool)$args['with_video'])}
            WHERE " . implode(" AND ", $where) . "
            ORDER BY {$this->table}.created_at DESC
            LIMIT %d OFFSET %d",
            ...$binds
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function getTopVideosWithTitles(array $args = []): array
    {
        $where = ["vw.deleted_at IS NULL"];
        $binds = [];
        $limit = $args['limit'] ?? 10;
        $from = $args['from'] ?? null;
        $to = $args['to'] ?? null;

        if ($from) {
            $where[] = "vw.created_at >= %s";
            $binds[] = $from;
        }
        if ($to) {
            $where[] = "vw.created_at <= %s";
            $binds[] = $to;
        }

        $binds[] = max(1, $limit);

        $sql = $this->db->prepare(
            "SELECT
                vw.video_id,
                vw.created_at,
                v.title AS video_title,
                COUNT(*) AS views,
                SUM(vw.duration) AS total_watch_time,
                AVG(vw.duration) AS avg_watch_time,
                SUM(vw.completed) AS completed_views,
                AVG(vw.progress_max) AS avg_progress
            FROM {$this->table} AS vw
            LEFT JOIN {$this->videosTable} AS v
            ON v.id = vw.video_id
            AND v.deleted_at IS NULL
            WHERE " . implode(" AND ", $where) . "
            GROUP BY vw.video_id
            ORDER BY total_watch_time DESC
            LIMIT %d",
            ...$binds
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function prepare_date_range(array $args = []): array
    {
        $date_range = $args['date_range'] ?? 'last_90_days';

        $ranges = [
            'today' => gmdate('Y-m-d H:i:s', strtotime('yesterday 23:59:59')),
            'yesterday' => gmdate('Y-m-d H:i:s', strtotime('yesterday 00:00:00')),
            'last_7_days' => gmdate('Y-m-d H:i:s', strtotime('-6 days midnight')),
            'last_30_days' => gmdate('Y-m-d H:i:s', strtotime('-29 days midnight')),
            'last_90_days' => gmdate('Y-m-d H:i:s', strtotime('-89 days midnight'))
        ];

        $args['from'] = $ranges[$date_range];
        $args['limit'] = 5000;

        if ($date_range === 'yesterday') {
            $args['to'] = gmdate('Y-m-d H:i:s', strtotime('yesterday 23:59:59'));
        }
        return $args;
    }

    public function getAnalyticsData(array $args = []): array
    {
        $args = $this->prepare_date_range($args);

        $top_videos = $this->getTopVideosWithTitles($args);
        $top_users = $this->getTopUsersWithName($args);
        $total_views = $this->count($args);
        $chart_data = $this->getChartDataByDate($args);

        return ['top_videos' => $top_videos, 'top_users' => $top_users, 'total_views' => $total_views, 'chart_data' => $chart_data];
    }

    protected function videoJoinSql(bool $withVideo): string
    {
        if (!$withVideo) return '';
        return " LEFT JOIN {$this->videosTable} AS v
                ON v.id = {$this->table}.video_id
                AND v.deleted_at IS NULL ";
    }

    protected function videoSelectSql(bool $withVideo): string
    {
        if (!$withVideo) return '';

        // pick only what you need
        return ",
            v.id    AS video_id,
            v.title AS video_title,
            v.src   AS video_src,
            v.type  AS video_type,
            v.external_id AS video_external_id
        ";
    }

    public function getViewsWithVideo(array $args = [], bool $includeVideo = true): array
    {
        // 1) Get views (no join)
        $views = $this->getViews(array_merge($args, ['with_video' => false]));

        if (!$includeVideo || !$views) {
            // Return consistent structure
            return array_map(fn($v) => ['view' => $v, 'video' => null], $views);
        }

        // 2) Collect unique video_ids
        $videoIds = [];
        foreach ($views as $v) {
            if (!empty($v['video_id'])) $videoIds[] = (int)$v['video_id'];
        }

        // 3) Bulk load videos
        $videosModel = new VideosModel($this->db);
        $videoMap = $videosModel->getByIds($videoIds);

        // 4) Attach nested video object
        $out = [];
        foreach ($views as $v) {
            $vid = (int)($v['video_id'] ?? 0);
            $out[] = [
                'view'  => $v,
                'video' => $vid && isset($videoMap[$vid]) ? $videoMap[$vid] : null,
            ];
        }

        return $out;
    }

    public function getTopUsersWithName(array $args = []): array
    {
        global $wpdb;

        $limit = max(1, (int) $args['limit']);
        $from = $args['from'] ?? null;
        $to = $args['to'] ?? null;

        $where = [
            "vw.deleted_at IS NULL",
            "vw.user_id IS NOT NULL",
            "vw.user_id > 0",
        ];
        $binds = [];

        if ($from) {
            $where[] = "vw.created_at >= %s";
            $binds[] = $from; // 'YYYY-mm-dd HH:ii:ss'
        }
        if ($to) {
            $where[] = "vw.created_at <= %s";
            $binds[] = $to;
        }

        $binds[] = $limit;

        $sql = $this->db->prepare(
            "SELECT
                vw.user_id,
                u.display_name,
                u.user_login,
                COUNT(*)                  AS total_views,
                SUM(vw.duration)          AS total_watch_time,
                AVG(vw.duration)          AS avg_watch_time,
                SUM(vw.completed)         AS completed_views,
                AVG(vw.progress_max)      AS avg_progress
            FROM {$this->table} AS vw
            INNER JOIN {$wpdb->users} AS u
                ON u.ID = vw.user_id
            WHERE " . implode(" AND ", $where) . "
            GROUP BY vw.user_id, u.display_name, u.user_login
            ORDER BY total_views DESC
            LIMIT %d",
            ...$binds
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function getTopUsersWithNameAdvanced(array $args = []): array
    {
        global $wpdb;

        $limit = max(1, (int) $args['limit'] ?? 10);
        $from = $args['from'] ?? null;
        $to = $args['to'] ?? null;
        $orderBy = $args['order_by'] ?? 'views'; // views, watch_time, completed


        $allowed = [
            'views'      => 'total_views',
            'watch_time' => 'total_watch_time',
            'completed'  => 'completed_views',
        ];
        $orderCol = $allowed[$orderBy] ?? 'total_views';

        $where = ["vw.deleted_at IS NULL", "vw.user_id IS NOT NULL", "vw.user_id > 0"];
        $binds = [];

        if ($from) {
            $where[] = "vw.created_at >= %s";
            $binds[] = $from;
        }
        if ($to) {
            $where[] = "vw.created_at <= %s";
            $binds[] = $to;
        }

        $binds[] = $limit;

        // Note: ORDER BY uses a whitelisted column name (safe)
        $sql = $this->db->prepare(
            "SELECT
                vw.user_id,
                u.display_name,
                u.user_login,
                COUNT(*)         AS total_views,
                SUM(vw.duration) AS total_watch_time,
                AVG(vw.duration) AS avg_watch_time,
                SUM(vw.completed) AS completed_views
            FROM {$this->table} AS vw
            INNER JOIN {$wpdb->users} AS u
                ON u.ID = vw.user_id
            WHERE " . implode(" AND ", $where) . "
            GROUP BY vw.user_id, u.display_name, u.user_login
            ORDER BY {$orderCol} DESC
            LIMIT %d",
            ...$binds
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function getUserVideoViews(array $args = []): array
    {
        global $wpdb;

        $args = $this->prepare_date_range($args);
        $user_id = absint($args['user_id'] ?? 0);
        $from = $args['from'] ?? null;
        $to = $args['to'] ?? null;
        $limit = $args['limit'] ?? 50;

        if (!$user_id) return [];

        $where = [
            "vw.deleted_at IS NULL",
            "vw.user_id = %d",
            "v.deleted_at IS NULL",
            "vw.duration != 0",
        ];

        $binds = [$user_id];

        if ($from) {
            $where[] = "vw.created_at >= %s";
            $binds[] = $from;
        }

        if ($to) {
            $where[] = "vw.created_at <= %s";
            $binds[] = $to;
        }

        $binds[] = max(1, (int)$limit);

        $sql = $this->db->prepare(
            "SELECT
                vw.video_id,
                v.title AS video_title,
                COUNT(*) AS views,
                SUM(vw.duration) AS total_watch_time,
                SUM(vw.completed) AS completed_views,
                AVG(vw.progress_max) AS avg_progress
            FROM {$this->table} AS vw
            INNER JOIN {$this->videosTable} AS v
                ON v.id = vw.video_id
            WHERE " . implode(" AND ", $where) . "
            GROUP BY vw.video_id, v.title
            ORDER BY views DESC
            LIMIT %d",
            ...$binds
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function getVideoStatsExtended(array $args = []): array
    {
        $args = $this->prepare_date_range($args);
        // return $args;
        $video_id = absint($args['video_id']);
        $from = $args['from'] ?? null;
        $to = $args['to'] ?? null;
        if (!$video_id) return [];

        $where = ["vw.deleted_at IS NULL", "vw.video_id = %d", "vw.duration != 0"];
        $binds = [$video_id];

        if ($from) {
            $where[] = "vw.created_at >= %s";
            $binds[] = $from;
        }

        if ($to) {
            $where[] = "vw.created_at <= %s";
            $binds[] = $to;
        }

        $sql = $this->db->prepare(
            "SELECT
                -- Counts
                COUNT(*)                                   AS views,
                COUNT(DISTINCT vw.session_id)              AS unique_sessions,
                COUNT(DISTINCT vw.user_id)                 AS unique_users,

                -- Watch time
                SUM(vw.duration)                           AS total_watch_time,
                AVG(vw.duration)                           AS avg_watch_time,
                MAX(vw.duration)                           AS max_watch_time,

                -- Progress / completion
                SUM(vw.completed)                          AS completed_views,
                ROUND(AVG(vw.progress_max), 2)             AS avg_progress,
                MAX(vw.progress_max)                       AS max_progress,

                -- Rates
                ROUND(SUM(vw.completed) / COUNT(*) * 100, 2) AS completion_rate,
                ROUND((COUNT(*) - SUM(vw.completed)) / COUNT(*) * 100, 2) AS dropoff_rate,

                -- Replays (same session watched more than once)
                SUM(
                CASE
                    WHEN vw.session_id IN (
                    SELECT session_id
                    FROM {$this->table}
                    WHERE video_id = %d
                        AND deleted_at IS NULL
                    GROUP BY session_id
                    HAVING COUNT(*) > 1
                    )
                    THEN 1 ELSE 0
                END
                ) AS replay_views,

                -- Time
                MIN(vw.created_at)                         AS first_view_at,
                MAX(vw.created_at)                         AS last_view_at

            FROM {$this->table} AS vw
            WHERE " . implode(" AND ", $where),
            $video_id, // for subquery
            ...$binds
        );

        return $this->db->get_row($sql, ARRAY_A) ?: [];
    }

    public function getChartDataByDate(array $args = []): array
    {
        // $from = $fromDate . ' 00:00:00';
        // $to   = $toDate   . ' 23:59:59';
        $current_date_time = current_datetime();
        $formatted_time = $current_date_time->format('Y-m-d H:i:s');
        // $args = $this->prepare_date_range($args);
        $from = $args['from'];
        $to   = $args['to'] ?? $formatted_time;
        $video_id = absint($args['video_id'] ?? 0);
        $post_id = absint($args['post_id'] ?? 0);

        $where = ["deleted_at IS NULL"];
        $where[] = "duration > 0";
        $binds = [];

        if ($from) {
            $where[] = "created_at >= %s";
            $binds[] = $from;
        }

        if ($to) {
            $where[] = "created_at <= %s";
            $binds[] = $to;
        }

        if ($video_id) {
            $where[] = "video_id = %d";
            $binds[] = absint($video_id);
        }

        if ($post_id) {
            $where[] = "post_id = %d";
            $binds[] = absint($post_id);
        }



        $sql = $this->db->prepare(
            "SELECT
                DATE(created_at)               AS d,
                COUNT(*)                       AS views,
                SUM(duration)                  AS watch_time,
                SUM(completed)                 AS completed,
                ROUND(AVG(progress_max), 2)    AS avg_progress
            FROM {$this->table}
            WHERE " . implode(" AND ", $where) . "
            GROUP BY DATE(created_at)
            ORDER BY d ASC",
            ...$binds
        );

        $rows = $this->db->get_results($sql, ARRAY_A) ?: [];


        // Map by date
        $map = [];
        foreach ($rows as $r) {
            $map[$r['d']] = $r;
        }

        // Fill missing dates
        $labels = [];
        $views = [];
        $watch = [];
        $completed = [];
        $avg_progress = [];

        $start = new \DateTime($from);
        $end   = new \DateTime($to);
        $start->setTime(0, 0, 0);
        $end->setTime(0, 0, 0);
        while ($start <= $end) {
            $key = $start->format('Y-m-d');
            $labels[] = $start->format('F j');

            $views[]        = isset($map[$key]) ? (int)$map[$key]['views'] : 0;
            $watch[]        = isset($map[$key]) ? (int)$map[$key]['watch_time'] : 0;
            $completed[]    = isset($map[$key]) ? (int)$map[$key]['completed'] : 0;
            $avg_progress[] = isset($map[$key]) ? (float)$map[$key]['avg_progress'] : 0;

            $start->modify('+1 day');
        }

        return [
            'labels'        => $labels,
            'views'         => $views,
            'watch_time'    => $watch,
            'completed'     => $completed,
            'avg_progress'  => $avg_progress,
        ];
    }
    public function getVideoPageStats(array $args = []): array
    {
        global $wpdb;
        $args = $this->prepare_date_range($args);
        $video_id = absint($args['video_id'] ?? 0);
        $from = $args['from'] ?? null;
        $to   = $args['to'] ?? null;
        $limit = absint($args['limit'] ?? 10);

        if (!$video_id) return [];

        $where = [
            "vw.deleted_at IS NULL",
            "vw.video_id = %d",
            "vw.post_id IS NOT NULL",
            "vw.post_id > 0",
        ];
        $binds = [$video_id];

        if ($from) {
            $where[] = "vw.created_at >= %s";
            $binds[] = $from;
        }
        if ($to) {
            $where[] = "vw.created_at <= %s";
            $binds[] = $to;
        }

        $binds[] = max(1, (int)$limit);

        $sql = $this->db->prepare(
            "SELECT
            vw.post_id,

            -- Use WP post title if available
            p.post_title AS page_title,

            COUNT(*)                              AS views,
            COUNT(DISTINCT vw.session_id)         AS unique_sessions,
            COUNT(DISTINCT vw.user_id)            AS unique_users,

            SUM(vw.duration)                      AS total_watch_time,
            ROUND(AVG(vw.duration), 4)            AS avg_watch_time,
            MAX(vw.duration)                      AS max_watch_time,

            SUM(vw.completed)                     AS completed_views,
            ROUND(AVG(vw.progress_max), 2)        AS avg_progress,
            MAX(vw.progress_max)                  AS max_progress,

            ROUND(SUM(vw.completed) / COUNT(*) * 100, 2) AS completion_rate,
            ROUND((COUNT(*) - SUM(vw.completed)) / COUNT(*) * 100, 2) AS dropoff_rate,

            -- replay_views within THIS post (same session multiple views on same post)
            (
              SELECT COALESCE(SUM(t.cnt), 0) FROM (
                SELECT (COUNT(*) - 1) AS cnt
                FROM {$this->table}
                WHERE deleted_at IS NULL
                  AND video_id = %d
                  AND post_id = vw.post_id
                GROUP BY session_id
                HAVING COUNT(*) > 1
              ) t
            ) AS replay_views,

            MIN(vw.created_at)                    AS first_view_at,
            MAX(vw.created_at)                    AS last_view_at

         FROM {$this->table} AS vw
         LEFT JOIN {$wpdb->posts} AS p
           ON p.ID = vw.post_id

         WHERE " . implode(" AND ", $where) . "
         GROUP BY vw.post_id, p.post_title
         ORDER BY views DESC
         LIMIT %d",
            $video_id, // for replay subquery
            ...$binds
        );

        $rows = $this->db->get_results($sql, ARRAY_A) ?: [];

        // Add permalink + fix missing titles
        foreach ($rows as &$r) {
            $pid = (int)($r['post_id'] ?? 0);
            $r['page_title'] = $r['page_title'] ?: "(Post #{$pid})";
            $r['page_url']   = $pid ? get_permalink($pid) : null;

            // Cast numeric strings to numbers (nice for JS)
            $r['views'] = (int)$r['views'];
            $r['unique_sessions'] = (int)$r['unique_sessions'];
            $r['unique_users'] = (int)$r['unique_users'];
            $r['total_watch_time'] = (int)$r['total_watch_time'];
            $r['avg_watch_time'] = (float)$r['avg_watch_time'];
            $r['max_watch_time'] = (int)$r['max_watch_time'];
            $r['completed_views'] = (int)$r['completed_views'];
            $r['avg_progress'] = (float)$r['avg_progress'];
            $r['max_progress'] = (int)$r['max_progress'];
            $r['completion_rate'] = (float)$r['completion_rate'];
            $r['dropoff_rate'] = (float)$r['dropoff_rate'];
            $r['replay_views'] = (int)$r['replay_views'];
        }
        unset($r);

        return $rows;
    }
}
