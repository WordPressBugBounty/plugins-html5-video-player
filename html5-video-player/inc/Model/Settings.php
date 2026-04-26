<?php

namespace H5VP\Model;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Settings
{
    const PREFIX = 'h5vp_option';

    /** @var array|false|null In-memory cache of the option array. */
    public static $settings = null;

    /**
     * Get all settings, or a single key with an optional default.
     *
     * @param string|null $key     Optional key to retrieve.
     * @param mixed       $default Default value when the key is absent.
     *
     * @return mixed Full settings array when $key is null, otherwise the key's value.
     */
    public static function get($key = null, $default = null)
    {
        if (self::$settings === null) {
            self::$settings = get_option(self::PREFIX, []);
        }

        if ($key === null) {
            return self::$settings;
        }

        return isset(self::$settings[$key]) ? self::$settings[$key] : $default;
    }

    /**
     * Add new settings data without overwriting existing keys.
     *
     * @param array $data Associative array of new settings to add.
     *
     * @return bool True on success, false on failure.
     */
    public static function add(array $data)
    {
        $current = self::get();
        // Only insert keys that are not already present
        $merged = array_merge($data, (array) $current);
        self::$settings = $merged;

        return update_option(self::PREFIX, $merged);
    }

    /**
     * Update (set or replace) a single settings key.
     *
     * @param string $key   The settings key to update.
     * @param mixed  $value The new value.
     *
     * @return bool True on success, false on failure.
     */
    public static function update($key, $value)
    {
        $current = (array) self::get();
        $current[$key] = $value;
        self::$settings = $current;

        return update_option(self::PREFIX, $current);
    }

    /**
     * Delete a single settings key, or the entire option when no key is given.
     *
     * @param string|null $key Key to remove. Pass null to delete all settings.
     *
     * @return bool True on success, false on failure.
     */
    public static function delete($key = null)
    {
        if ($key === null) {
            self::$settings = [];
            return delete_option(self::PREFIX);
        }

        $current = (array) self::get();
        if (!array_key_exists($key, $current)) {
            return false;
        }

        unset($current[$key]);
        self::$settings = $current;

        return update_option(self::PREFIX, $current);
    }
}