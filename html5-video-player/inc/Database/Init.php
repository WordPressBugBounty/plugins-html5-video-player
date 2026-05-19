<?php
namespace H5VP\Database;

use H5VP\Database\Table;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Init
{

    public static function get_tables()
    {
        return [
            Videos::class
        ];
    }

    public function register()
    {
        self::install();
    }

    /**
     * Install / upgrade every custom table. Hooked to register_activation_hook
     * so the schema work happens once at activation, not on every request.
     */
    public static function install()
    {
        foreach (self::get_tables() as $class) {
            $table = self::instantiate($class);
            if (method_exists($table, 'install')) {
                $table->install();
            }
        }
        if (defined('H5VP_VER')) {
            update_option('h5vp_db_version', H5VP_VER, false);
        }
    }

    /**
     * Run pending schema upgrades after a plugin update. Cheap no-op once the
     * stored DB version matches the current plugin version. Hooked to
     * admin_init so the front-end never pays for the check.
     */
    public static function maybe_upgrade()
    {
        if (defined('H5VP_VER') && get_option('h5vp_db_version') === H5VP_VER) {
            return;
        }
        self::install();
    }

    public static function drop()
    {
        foreach (self::get_tables() as $class) {
            $table = self::instantiate($class);
            if (method_exists($table, 'uninstall')) {
                $table->uninstall();
            }
        }
        return true;
    }

    private static function instantiate($class)
    {
        return new $class(new Table);
    }
}




