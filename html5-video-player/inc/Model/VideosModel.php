<?php
namespace H5VP\Model;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use wpdb;

class VideosModel {

    /** @var wpdb */
    protected $db;

    /** @var string */
    protected $table;

    public function __construct(wpdb $db = null) {
        global $wpdb;
        $this->db = $db ?: $wpdb;
        $this->table = $this->db->prefix . 'h5vp_videos';
    }

    public function getById(int $id): ?array {
        $id = absint($id);
        if (!$id) return null;

        $sql = $this->db->prepare(
            "SELECT id, title, src, type, user_id, post_id, external_id, created_at, updated_at
             FROM {$this->table}
             WHERE id = %d AND deleted_at IS NULL
             LIMIT 1",
            $id
        );

        $row = $this->db->get_row($sql, ARRAY_A);
        return $row ?: null;
    }

    /**
     * Bulk fetch videos by ids (efficient for listing views).
     * @return array<int, array> keyed by video id
     */
    public function getByIds(array $ids): array {
        $ids = array_values(array_filter(array_map('absint', $ids)));
        $ids = array_unique($ids);

        if (!$ids) return [];

        // Safe IN() building
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $sql = $this->db->prepare(
            "SELECT id, title, src, type, user_id, post_id, external_id, created_at, updated_at
             FROM {$this->table}
             WHERE id IN ($placeholders) AND deleted_at IS NULL",
            ...$ids
        );

        $rows = $this->db->get_results($sql, ARRAY_A) ?: [];
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['id']] = $r;
        }
        return $map;
    }
}
