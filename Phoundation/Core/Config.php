<?php

namespace Phoundation\Core;

use Phoundation\Core\Exception\ConfigException;
use Phoundation\Exception\OutOfBoundsException;

/**
 * Class Config
 *
 * This class contains the methods to read, write and manage configuration options. Default configuration values are
 * specified in the classes themselves whereas users can add configuration sections in the YAML file
 * ROOT/config/ENVIRONMENT/CLASSNAME and this class will apply those values.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 <copyright@capmega.com>
 * @package Phoundation\Core
 */
class Config{
    /**
     * The generic system register to store data
     *
     * @var array $data
     */
    protected static array $data = [];
 v
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
     * Return configuration data for the specified key path
     *
     * @param string $keys
     * @param mixed|null $default
     * @return mixed
     */
    public static function get(string $keys, mixed $default = null): mixed
    {
        return self::getSection($keys, $default);
    }



    /**
     * Return configuration data for the specified key path
     *
     * @param string $keys
     * @param mixed $value
     * @return mixed
     */
    public static function set(string $keys, mixed $value = null): mixed
    {
        // Get the section and store the data in the return value
        $section = self::getSection($keys, $value);
        $return = $section;

        // Update the sectoin data
        $section = $value;
        return $return;
    }



    /**
     * Return a byreference configuration data section for the specified key path
     *
     * @param string|array $keys
     * @param mixed $default
     * @return mixed
     */
    protected static function &getSection(string|array $keys, mixed $default = null): mixed
    {
        $keys = explode($keys, '.');
        $data = &static::$data;

        // Go over each key and if the value for the key is an array, request a subsection
        foreach ($keys as $key) {
            if (!isset($data[$key])) {
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
//            $files = array_force($files);
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