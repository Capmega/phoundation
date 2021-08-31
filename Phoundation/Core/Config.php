<?php

namespace Phoundation\Core;

/**
 * Class Config
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2021 <copyright@capmega.com>
 * @package Phoundation\Core
 */
class Config{
    /**
     * The generic system register to store data
     *
     * @var array $data
     */
    protected static array $data = [];

    /**
     * Configuration files that have been read
     *
     * @var array $sections
     */
    protected static array $sections = [];



    /**
     * Reads the configuration file for the specified configuration section
     *
     * @param string $section
     * @return bool True if any configuration files were read, false if the file already was read before, or if no
     *      configuration files are available for the specified section
     */
    public function read(string $section): bool
    {
        if (isset(self::$sections[$section])) {
            return false;
        }

        $read = false;

        // Read the section for each environment
        foreach (['production', ENVIRONMENT] as $environment) {
            $file = ROOT . 'config/' . $environment . '/' . $section . '.php';

            // Check if a configuration file exists
            if (!file_exists($file)) {
                // Read the configuration data and merge it in the internal configuration data array
                $read = true;
                $data = yaml_parse_file($file);
                self::$data[$section] = array_merge(self::$data[$section], $data);
            }
        }

        return $read;
    }



    /**
     * Only set the specified configuration data for the specified key, if the specified key does not yet exist in
     * memory
     *
     * @param string $keys
     * @param mixed $value
     * @return bool True if the default value was applied, false if not
     */
    public function default(string $keys, mixed $value): bool
    {
        if (self::exists($keys)) {
            return false;
        }

        self::set($keys, $value);
        return true;
    }



    /**
     * Set configuration data for the specified . separated keys
     *
     * @param string $keys
     * @param mixed $value
     */
    public static function set(string $keys, mixed $value): void
    {
        $keys = explode($keys, '.');
        $data = &self::$data;

        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                // Create the specified key
                $data[$key] = [];
            }

            $data = &$data[$key];
        }

        $data = $value;
    }



    /**
     * Get configuration data for the specified key
     *
     * @param string $keys
     * @return mixed
     * @throws ConfigException
     */
    public static function get(string $keys, mixed $default = null): mixed
    {
        $keys = explode($keys, '.');
        $data = &static::$data;

        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                if ($default === null) {
                    throw new ConfigException(tr('The configuration key ":key" from ":keys" does not exist', [':key' => $key, ':keys' => $keys]));
                }

                return $default;
            }

            $data = &$data[$key];
        }

        return $data;
    }



    /**
     * Returns true if the specified configuration keys exist, false if not
     *
     * @param string $keys
     * @return bool
     */
    public static function exists(string $keys): bool
    {
        $keys = explode($keys, '.');
        $data = &static::$data;

        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                return false;
            }

            $data = &$data[$key];
        }

        return true;
    }
}