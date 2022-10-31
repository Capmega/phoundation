<?php

namespace Phoundation\Core;

use Phoundation\Core\Exception\ConfigException;
use Phoundation\Core\Exception\ConfigNotExistsException;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exceptions;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpException;
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
class Config{
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

            self::$data = array_merge(self::$data, $data);
        }
    }



//    /**
//     * Returns true if the specified configuration keys exist, false if not
//     *
//     * @param string $keys
//     * @return bool
//     */
//    public static function exists(string $keys): bool
//    {
//        $keys = explode($keys, '.');
//        $data = &static::$data;
//
//        foreach ($keys as $key) {
//            if (!isset($data[$key])) {
//                return false;
//            }
//
//            $data = &$data[$key];
//        }
//
//        return true;
//    }
//
//
//
//
//    /*
//     * Load specified configuration files. All files must be specified by their section name only, no extension nor environment.
//     * The function will then load the files PATH_ROOT/config/base/NAME.php, PATH_ROOT/config/base/production_NAME.php, and on non "production" environments, PATH_ROOT/config/base/ENVIRONMENT_NAME.php
//     * For example, if you want to load the "fprint" configuration, use load_config('fprint'); The function will load PATH_ROOT/config/base/fprint.php, PATH_ROOT/config/base/production_fprint.php, and on (for example) the "local" environment, PATH_ROOT/config/base/local_fprint.php
//     *
//     * Examples:
//     * load_config('fprint');
//     * load_config('fprint,buks');
//     * load_libs(array('fprint', 'buks'));
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     *
//     * @param mixed $files Either array or CSV string with the libraries to be loaded
//     * @return void
//     */
//    function load_config($files = '')
//    {
//        global $_CONFIG, $core;
//        static $paths;
//
//        try {
//            if (!$paths) {
//                $paths = array(PATH_ROOT . 'config/base/',
//                    PATH_ROOT . 'config/production',
//                    PATH_ROOT . 'config/' . ENVIRONMENT);
//            }
//
//            $files = Arrays::force($files);
//
//            foreach ($files as $file) {
//                $loaded = false;
//                $file = trim($file);
//
//                /*
//                 * Include first the default configuration file, if available, then
//                 * production configuration file, if available, and then, if
//                 * available, the environment file
//                 */
//                foreach ($paths as $id => $path) {
//                    if (!$file) {
//                        /*
//                         * Trying to load default configuration files again
//                         */
//                        if (!$id) {
//                            $path .= 'default.php';
//
//                        } else {
//                            $path .= '.php';
//                        }
//
//                    } else {
//                        if ($id) {
//                            $path .= '_' . $file . '.php';
//
//                        } else {
//                            $path .= $file . '.php';
//                        }
//                    }
//
//                    if (file_exists($path)) {
//                        include($path);
//                        $loaded = true;
//                    }
//                }
//
//                if (!$loaded) {
//                    throw new OutOfBoundsException(tr('load_config(): No configuration file was found for requested configuration ":file"', array(':file' => $file)), 'not-exists');
//                }
//            }
//
//            /*
//             * Configuration has been loaded succesfully, from here all debug
//             * functions will work correctly. This is
//             */
//            $core->register['ready'] = true;
//
//        } catch (Exception $e) {
//            throw new OutOfBoundsException(tr('load_config(): Failed to load some or all of config file(s) ":file"', array(':file' => $files)), $e);
//        }
//    }
//
//
//    /*
//     * Returns the configuration array from the specified file and specified environment
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     * @version 2.0.7: Fixed loading bugs, improved error handling
//     * @version 2.4.62: Fixed bug with "deploy" config
//     *
//     * @param string $file
//     * @param string $environment
//     * @return array The requested configuration array
//     */
//    function read_config($file = null, $environment = null)
//    {
//        try {
//            if (!$environment) {
//                $environment = ENVIRONMENT;
//            }
//
//            if ($file === 'deploy') {
//                include(PATH_ROOT . 'config/deploy.php');
//                return $_CONFIG;
//            }
//
//            if ($file) {
//                if (file_exists(PATH_ROOT . 'config/base/' . $file . '.php')) {
//                    $loaded = true;
//                    include(PATH_ROOT . 'config/base/' . $file . '.php');
//                }
//
//                $file = '_' . $file;
//
//            } else {
//                $loaded = true;
//                include(PATH_ROOT . 'config/base/default.php');
//            }
//
//            if (file_exists(PATH_ROOT . 'config/production' . $file . '.php')) {
//                $loaded = true;
//                include(PATH_ROOT . 'config/production' . $file . '.php');
//            }
//
//            if (file_exists(PATH_ROOT . 'config/' . $environment . $file . '.php')) {
//                $loaded = true;
//                include(PATH_ROOT . 'config/' . $environment . $file . '.php');
//            }
//
//            if (empty($loaded)) {
//                throw new OutOfBoundsException(tr('The specified configuration ":config" does not exist', array(':config' => $file)), 'not-exists');
//            }
//
//            return $_CONFIG;
//
//        } catch (Exception $e) {
//            throw new OutOfBoundsException('read_config(): Failed', $e);
//        }
//    }
//
}