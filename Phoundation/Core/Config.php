<?php

namespace Phoundation\Core;

use Phoundation\Core\Exception\ConfigException;
use Phoundation\Core\Exception\ConfigNotExistsException;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exceptions;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;
use Throwable;



/**
 * Class Config
 *
 * This class contains the methods to read, write and manage configuration options. Default configuration values are
 * specified in the classes themselves whereas users can add configuration sections in the YAML file
 * PATH_ROOT/config/ENVIRONMENT/CLASSNAME and this class will apply those values.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Config
{
    /**
     * Singleton variable
     *
     * @var Config|null $instance
     */
    protected static ?Config $instance = null;

    /**
     * Keeps track of configuration failures
     *
     * @var bool $fail
     */
    protected static bool $fail = false;

    /**
     * The generic system register to store data
     *
     * @var array $data
     */
    protected static array $data = array();

    /**
     * Configuration files that have been read
     *
     * @var array $files
     */
    protected static array $files = [];

    /**
     * Configuration cache
     *
     * @var array
     */
    protected static array $cache = [];



    /**
     * Config constructor
     */
    protected function __construct()
    {
        self::read(ENVIRONMENT);
    }



    /**
     * Singleton, ensure to always return the same Log object.
     *
     * @return Config
     */
    public static function getInstance(): Config
    {
        if (!isset(self::$instance)) {
            self::$instance = new Config();
        }

        return self::$instance;
    }



    /**
     * Return configuration data for the specified key path
     *
     * @param string|array $path    The key path to search for. This should be specified either as an array with key
     *                              names or a . separated string
     * @param mixed|null $default   The default value to return if no value was found in the configuration files
     * @param mixed|null $specified A value that might have been specified by a calling function. IF this value is not
     *                              NULL, it will automatically be returned as we will assume that that is the user
     *                             (developer) specified value we should be using, overriding configuration and defaults
     * @return mixed
     */
    public static function get(string|array $path, mixed $default = null, mixed $specified = null): mixed
    {
        Debug::counter('Config::get()')->increase();

        if (self::$fail) {
            // Config class failed, always return all default values
            return $default;
        }

        // Do we have cached configuration information?
        $cache_key = Strings::force($path, '.');

        if (array_key_exists($cache_key, self::$cache)) {
            return self::$cache[$cache_key];
        }

        self::getInstance();

        if ($specified) {
            return $specified;
        }

        $path = Arrays::force($path, '.');
        $data = &static::$data;

        // Go over each key and if the value for the key is an array, request a subsection
        foreach ($path as $section) {
            if (!is_array($data)) {
//                echo "<pre>";var_dump($path);var_dump($section);var_dump($data);echo "\n";

                if ($data !== null) {
                    Log::warning(tr('Encountered invalid configuration structure whilst looking for ":path". Section ":section" should contain sub values but does not. Please check your configuration files that this structure exists correctly', [
                        ':path' => $path,
                        ':section' => $section
                    ]));
                }

                // This section is missing in config files. No biggie, initialize it as an array
                $data = [];
            }

            if (!array_key_exists($section, $data)) {
                // The requested key does not exist
                if ($default === null) {
                    // We have no default configuration either
                    throw new ConfigNotExistsException(tr('The configuration section ":section" from key path ":path" does not exist', [
                        ':section' => $section,
                        ':path'    => $path
                    ]));
                }

                // The requested key does not exist in configuration, return the default value instead
                return self::$cache[$cache_key] = $default;
            }

            // Get the requested subsection. This subsection must be an array!
            $data = &$data[$section];
        }

        return self::$cache[$cache_key] = $data;
    }



    /**
     * Return if the specified configuration key path exists or not
     *
     * @param string|array $keys The key path to search for. This should be specified either as an array with key names
     *                           or a . separated string
     * @return bool
     */
    public static function exists(string|array $keys): bool
    {
        $keys = Arrays::force($keys, '.');
        $data = &static::$data;

        // Go over each key and if the value for the key is an array, request a subsection
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                // The requested key does not exist
                return false;
            }

            // Get the requested subsection
            $data = &$data[$key];
        }

        return true;
    }



    /**
     * Return configuration data for the specified key path
     *
     * @param string|array $path    The key path to search for. This should be specified either as an array with key
     *                              names or a . separated string
     * @param mixed $value
     * @return mixed
     */
    public static function set(string|array $path, mixed $value = null): mixed
    {
        $cache_key = Strings::force($path, '.');
        $path      = Arrays::force($path, '.');
        $data      = &static::$data;

        // Go over each key and if the value for the key is an array, request a subsection
        foreach ($path as $section) {
            if (!is_array($data)) {
                // Oops, this data section should be an array
                throw new ConfigException(tr('The configuration section ":section" from requested path ":path" does not exist', [':section' => $section, ':path' => $path]));
            }

            if (!array_key_exists($section, $data)) {
                // The requested key does not exist, initialize with an array just in case
                $data[$section] = [];
            }

            // Get the requested subsection
            $data = &$data[$section];
        }

        // The variable $data should now be the correct leaf node. Assign it $value and return it.
        $data = $value;
        return self::$cache[$cache_key] = $value;
    }


    /**
     * Scan the entire project from ROOT for Config::get() and Config::set() and generate a config/default.yaml file
     * with all default values
     *
     * @return int The amount of configuration paths processed
     */
    public static function generateDefaultYaml(): int
    {
        $count = 0;
        $store = [];

        // Scan all files for Config::get() and Config::set() calls
        File::each()
            ->setPath(PATH_ROOT)
            ->addSkipPaths([PATH_DATA, PATH_ROOT . 'tests', PATH_ROOT . 'garbage'])
            ->setRecurse(true)
            ->setRestrictions(new Restrictions(PATH_ROOT))
            ->execute(function(string $file) use (&$store) {
            $results = File::grep($file, ['Config::get(\'', 'Config::set(\'']);

            foreach ($results as $lines){
                foreach ($lines as $line) {
                    // Extract the configuration path and default value for each call
                    if (!preg_match_all('/Config::[gs]et\s?\([\'"](.+?)[\'"](?:.+?)?(?:,\s?(.+?))\)/i', $line, $matches)) {
                        if (!preg_match_all('/Config::[gs]et\s?\([\'"](.+?)[\'"](?:.+?)?(?:,\s?(.+?))?\)/i', $line, $matches)) {
                            Log::warning(tr('Failed to extract a Config::get() or Config::set() from line ":line" in file ":file"', [
                                ':file' => $file,
                                ':line' => $line
                            ]));

                            continue;
                        }
                    }

                    // Pass over all matches
                    foreach ($matches[0] as $match => $value) {
                        $path    = str_replace(['"', "'"], '', trim($matches[1][$match]));
                        $default = str_replace(['"', "'"], '', trim($matches[2][$match]));

                        // Log all Config::get() and Config::set() calls that have the same configuration path but different
                        // default values
                        if (array_key_exists($path, $store)) {
                            if ($store[$path] !== $default) {
                                Log::warning(tr('Configuration path ":path" has two different default values ":1" and ":2"', [
                                    ':path' => $path,
                                    ':1'    => $default,
                                    ':2'    => $store[$path],
                                ]));
                            }
                        }

                        // Store the configuration path
                        $store[$path] = $default;
                    }
                }
            }
        });

        // Convert all entries ending in . to array values (these typically have variable subkeys following)
        foreach ($store as $path => $default) {
            if (str_ends_with($path, '.')) {
                $store[substr($path, 0, -1)] = [];
                unset($store[$path]);
            }
        }

        // Fix all entries that have variables or weird values
        foreach ($store as $path => $default) {
            if (!is_scalar($default)) {
                continue;
            }

            if (str_starts_with($default, '$')) {
                $store[$path] = null;
            }

            if (str_contains($default, '::')) {
                $store[$path] = null;
            }

            if (str_contains($default, ',')) {
                $store[$path] = Strings::from($store[$path], ',');
                $store[$path] = trim($store[$path]);
            }
        }

        // Sort so that we have a nice alphabetically ordered list
        asort($store);

        // Great, we have all used configuration paths and their default values! Now construct the config/default.yaml
        // file
        $data = [];

        // Convert the store to an array map
        foreach ($store as $path => $default) {
            $path    = explode('.', $path);
            $section = &$data;
            $count++;

            foreach ($path as $key) {
                if (!array_key_exists($key, $section)) {
                    // Initialize with sub array and jump in
                    $section[$key] = [];
                }

                $section = &$section[$key];
            }

            $section = $default;
            unset($section);
        }

        // Convert the data into yaml and store the data in the default file
        $data = yaml_emit($data);
        file_put_contents('config/default.yaml', $data);
    }



    /**
     * Reads the configuration file for the specified configuration environment
     *
     * @param string $environment
     * @return void
     * @throws ConfigException
     * @throws OutOfBoundsException
     */
    protected static function read(string $environment): void
    {
        // What environments should be read?
        if ($environment === 'production') {
            $environments = ['production'];
        } else {
            $environments = ['production', $environment];
        }

        // Read the section for each environment
        foreach ($environments as $environment) {
            $file = PATH_ROOT . 'config/' . $environment . '.yaml';

            // Check if a configuration file exists for this environment
            if (!file_exists($file)) {
                // Do NOT use tr() here as it will cause endless loops!
                throw Exceptions::ConfigException('Configuration file "' . Strings::from($file, PATH_ROOT) . '" for environment "' . Strings::log($environment) . '" does not exist')->makeWarning();
            }

            try {
                // Read the configuration data and merge it in the internal configuration data array
                $data = yaml_parse_file($file);
            } catch (Throwable $e) {
                // Failed to read YAML data from configuration file
                self::$fail = true;
                throw $e;
            }

            if (!is_array($data)) {
                throw new OutOfBoundsException(tr('Configuration data in file ":file" has an invalid format', [':file' => $file]));
            }

            self::$data = Arrays::mergeFull(self::$data, $data);
        }
    }
}