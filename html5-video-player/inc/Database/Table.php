<?php

namespace H5VP\Database;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Table
{
    /**
     * Sanitize a SQL identifier (table or column name).
     *
     * Identifiers cannot be passed through $wpdb->prepare(), so we strip
     * everything except characters that are valid in a table name.
     *
     * @param string $identifier
     * @return string
     */
    private function sanitize_identifier($identifier)
    {
        return preg_replace('/[^A-Za-z0-9_]/', '', (string) $identifier);
    }

    /**
     * Create a database table
     *
     * @param string $name
     * @param string $columns
     * @param integer $version
     * @param array $opts
     * @return void
     */
    public function create($name, $columns, $version = 1, $opts = [])
    {
        $name            = $this->sanitize_identifier($name);
        $current_version = get_option("{$name}_database_version", 0);

        if ($version == $current_version) {
            return;
        }

        global $wpdb;

        $full_table_name = esc_sql($this->sanitize_identifier($wpdb->prefix . $name));

        $opts = wp_parse_args($opts, [
            'upgrade_method' => 'dbDelta',
            'table_options' => '',
        ]);

        $charset_collate = '';
        if ($wpdb->has_cap('collation')) {
            if (!empty($wpdb->charset)) {
                $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            }
            if (!empty($wpdb->collate)) {
                $charset_collate .= " COLLATE $wpdb->collate";
            }
        }

        $table_options = $charset_collate . ' ' . $opts['table_options'];

        // use dbDelta by default
        if ('dbDelta' == $opts['upgrade_method']) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta("CREATE TABLE $full_table_name ( $columns ) $table_options");
            update_option("{$name}_database_version", $version);
            return;
        }

        if ('delete_first' == $opts['upgrade_method']) {
            // Table names and schema cannot be passed through $wpdb->prepare();
            // identifiers are sanitized above. DDL queries are uncacheable by nature.
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
            $wpdb->query("DROP TABLE IF EXISTS $full_table_name;");
        }

        // Table names and schema cannot be passed through $wpdb->prepare();
        // identifiers are sanitized above and $columns is a hardcoded, plugin-
        // defined schema string. DDL queries are uncacheable by nature.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $wpdb->query("CREATE TABLE IF NOT EXISTS $full_table_name ( $columns ) $table_options;");

        update_option("{$name}_database_version", $version);
    }

    /**
     * Drops the table and database option
     *
     * @param string $name
     * @return void
     */
    public function drop($name)
    {
        global $wpdb;

        $name = esc_sql($this->sanitize_identifier($name));

        // Table names cannot be passed through $wpdb->prepare(); the identifier
        // is sanitized above. DDL queries are uncacheable by nature.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query("DROP TABLE IF EXISTS $name;");
        // delete_option("{$name}_database_version");
    }
}
