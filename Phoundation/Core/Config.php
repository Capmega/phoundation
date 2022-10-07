<?php

namespace Phoundation\Core;

use Phoundation\Core\Exception\ConfigException;



/**
 * Class Config
 *
 * This class contains the methods to read, write and manage configuration options. Default configuration values are
 * specified in the classes themselves whereas users can add configuration sections in the YAML file
 * ROOT/config/ENVIRONMENT/CLASSNAME and this class will apply those values.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Config{
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
     * Reads the configuration file for the specified configuration environment
     *
     * @param string $environment
     * @return bool True if any configuration files were read, false if the file already was read before, or if no
     *      configuration files are available for the specified section
     */
    public function read(string $environment): bool
    {
        $read = false;

        // Read the section for each environment
        foreach (['production', ENVIRONMENT] as $environment) {
            $file = ROOT . 'config/' . $environment . '.yaml';

            // Check if a configuration file exists for this environment
            if (!file_exists($file)) {
                // Read the configuration data and merge it in the internal configuration data array
                $read = true;
                $data = yaml_parse_file($file);
                self::$data = array_merge(self::$data, $data);
            }
        }

        return $read;
    }



    /**
     * Return configuration data for the specified key path
     *
     * @param string|array $keys    The key path to search for. This should be specified either as an array with key
     *                              names or a . separated string
     * @param mixed|null $default   The default value to return if no value was found in the configuration files
     * @param mixed|null $specified A value that might have been specified by a calling function. IF this value is not
     *                              NULL, it will automatically be returned as we will assume that that is the user
     *                             (developer) specified value we should be using, overriding configuration and defaults
     * @return mixed
     */
    public static function get(string|array $keys, mixed $default = null, mixed $specified = null): mixed
    {
        if ($specified) {
            return $specified;
        }

        $keys = Arrays::force($keys, '.');
        $data = &static::$data;

        // Go over each key and if the value for the key is an array, request a subsection
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                // The requested key does not exist
                if ($default === null) {
                    // We have no default configuration either
                    throw new ConfigException(tr('The configuration key ":key" from key path ":keys" does not exist', [':key' => $key, ':keys' => $keys]));
                }

                // The requested key does not exist in configuration, return the default value instead
                return $default;
            }

            // Get the requested subsection
            $data = &$data[$key];
        }

        return $data;
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
     * @param string|array $keys    The key path to search for. This should be specified either as an array with key
     *                              names or a . separated string
     * @param mixed $value
     * @return mixed
     */
    public static function set(string|array $keys, mixed $value = null): mixed
    {
        $keys = Arrays::force($keys, '.');
        $data = &static::$data;

        // Go over each key and if the value for the key is an array, request a subsection
        foreach ($keys as $key) {
            if (!is_array($data)) {
                // Oops, this data section should be an array
                throw new ConfigException(tr('The configuration key ":key" from key path ":keys" does not exist', [':key' => $key, ':keys' => $keys]));
            }

            if (!array_key_exists($key, $data)) {
                // The requested key does not exist, initialize with an array just in case
                $data[$key] = [];
            }

            // Get the requested subsection
            $data = &$data[$key];
        }

        // The variable $data should now be the correct leaf node. Assign it $value and return it.
        $data = $value;
        return $data;
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
//     * The function will then load the files ROOT/config/base/NAME.php, ROOT/config/base/production_NAME.php, and on non "production" environments, ROOT/config/base/ENVIRONMENT_NAME.php
//     * For example, if you want to load the "fprint" configuration, use load_config('fprint'); The function will load ROOT/config/base/fprint.php, ROOT/config/base/production_fprint.php, and on (for example) the "local" environment, ROOT/config/base/local_fprint.php
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
//                $paths = array(ROOT . 'config/base/',
//                    ROOT . 'config/production',
//                    ROOT . 'config/' . ENVIRONMENT);
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
//                include(ROOT . 'config/deploy.php');
//                return $_CONFIG;
//            }
//
//            if ($file) {
//                if (file_exists(ROOT . 'config/base/' . $file . '.php')) {
//                    $loaded = true;
//                    include(ROOT . 'config/base/' . $file . '.php');
//                }
//
//                $file = '_' . $file;
//
//            } else {
//                $loaded = true;
//                include(ROOT . 'config/base/default.php');
//            }
//
//            if (file_exists(ROOT . 'config/production' . $file . '.php')) {
//                $loaded = true;
//                include(ROOT . 'config/production' . $file . '.php');
//            }
//
//            if (file_exists(ROOT . 'config/' . $environment . $file . '.php')) {
//                $loaded = true;
//                include(ROOT . 'config/' . $environment . $file . '.php');
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