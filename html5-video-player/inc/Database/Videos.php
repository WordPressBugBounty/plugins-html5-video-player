<?php

namespace H5VP\Database;

use H5VP\Database\Table;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Videos
{
    protected $table;
    protected $version = 2;
    protected $name = 'h5vp_videos';

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function getName()
    {
        global $wpdb;
        return $wpdb->prefix . $this->name;
    }

    /**
     * Add videos table
     * This is used for global video analytics
     *
     * @return void
     */
    public function install()
    {
        return $this->table->create($this->name, "
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title VARCHAR(256) NOT NULL,
            src varchar(256) NOT NULL,
            type varchar(100) NOT NULL,
            user_id VARCHAR(256) NOT NULL,
            post_id bigint NULL,
            external_id VARCHAR(150) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY  (`id`),
            KEY deleted_at (deleted_at)
            ", $this->version);
    }

    /**
     * Uninstall tables
     *
     * @return void
     */
    public function uninstall()
    {
        $this->table->drop($this->getName());
    }
}
