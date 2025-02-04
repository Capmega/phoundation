<?php

/**
 * Class Config
 *
 * This class contains the methods to read, write and manage configuration options. Default configuration values are
 * specified in the Config::get() calls themselves whereas users can add configuration sections in the YAML file
 * DIRECTORY_ROOT/config/environments/ENVIRONMENT/SECTION and this class will return those values.
 *
 * Configuration values are stored in YAML files which contain a tree of keys. Configuration values can be read by
 * specifying the configuration path that will traverse the configuration tree. The path may be specified either with an
 * array containing all the required keys, or (more conveniently) a path string containing all the keys to reach the
 * requested value keys, separated by dots. So for example, the value for the section
 * database: connectors: system: driver: can be accessed with the path "database.connectors.system.driver"
 *
 * Config::get() can be used to read configuration values. If a leaf node was specified, then the value for that leaf
 * will be returned. If a branch node (a key that contains other keys) was specified, the entire branch with all the
 * keys will be returned as an array.
 *
 * Configuration keys should always be specified with a - (dash) and not _ (underscore) Config::get() will automatically
 * replace all - in keys to _
 *
 * Each configuration call requires specifying what section and environment are required by using
 * Config::fromSection($section, $environment)->get($path)
 *
 * The "functions" library contains a config($section, $environment) method that is a convenient shortcut for
 * Config::fromSection($section, $environment). No setup is required to access configuration, one can just use
 * config($section, $environment)->get('path', $default_value) where both $section and $environment are optional and
 * default to the default configuration for the current environment. Most configuration calls therefor will be as simple
 * as config()->get('path', $default_value)
 *
 * If the requested configuration key does not exist, then the default value will be returned. If no default value was
 * specified, a ConfigPathDoesNotExistsException will be thrown
 *
 * To improve configuration reliability, it is very much recommended to use one of the datatype sensitive Config::get()
 * calls. These calls will throw exceptions if the value for the specified configuration path does not match the
 * expected datatype. Current datatype sensitive configuration get calls are:
 *
 * Config::getBoolean(string|array $path, ?bool $default = null): bool
 * Config::getInteger(string|array $path, ?int $default = null): int
 * Config::getPositiveInteger(string|array $path, ?int $default = null): int
 * Config::getNegativeInteger(string|array $path, ?int $default = null): int
 * Config::getNatural(string|array $path, int|float|null $default = null): int
 * Config::getDbId(string|array $path, int|float|null $default = null): int
 * Config::getFloat(string|array $path, int|float|null $default = null): int|float
 * Config::getIteratorObject(string|array $path, IteratorInterface|array|null $default = null): IteratorInterface
 * Config::getArray(string|array $path, array|null $default = null, array|string|null $require_keys = null): array
 * Config::getString(string|array $path, string|null $default = null): string
 * Config::getBoolString(string|array $path, string|bool|null $default = null): string|bool
 *
 * Config will ALWAYS read in the production environment file for the requested section, after which it will read the
 * file for the current environment. The file for the current environment only needs to contain configuration keys
 * that need to differ from production
 *
 * @note All sections MUST have a production environment configuration file. A configuration file for other environments
 *       is optional, and only needs to exist if it contains keys with values that should differ from production.
 *
 * @note When the Core class enters the shutdown phase (Core::isStateShutdown() === true) Config::read() will no longer
 *       read any files to avoid endless loop issues
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils;

use Exception;
use Phoundation\Core\Core;
use Phoundation\Core\Interfaces\ConfigInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Debug;
use Phoundation\Developer\Project\Configuration;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Utils\Exception\ConfigException;
use Phoundation\Utils\Exception\ConfigFailedException;
use Phoundation\Utils\Exception\ConfigFileDoesNotExistsException;
use Phoundation\Utils\Exception\ConfigParseFailedException;
use Phoundation\Utils\Exception\ConfigPathDoesNotExistsException;
use Phoundation\Utils\Exception\ConfigReadFailedException;
use Throwable;


class Config implements ConfigInterface
{
    /**
     * Keeps track of configuration failures
     *
     * @var string|false $failed
     */
    protected string|false $failed = false;

    /**
     * The generic system register to store data
     *
     * @var array $data
     */
    protected array $data = [];

    /**
     * Configuration files that have been read
     *
     * @var array $files
     */
    protected array $files = [];

    /**
     * Configuration cache
     *
     * @var array
     */
    protected array $cache = [];

    /**
     * The configuration section used by this configuration object
     *
     * @var string $section
     */
    protected string $section = 'default';

    /**
     * The environment used by this specific configuration object
     *
     * @var string|null $environment
     */
    protected ?string $environment = null;

    /**
     * The default environment used for all configuration objects
     *
     * @var string|null $default_environment
     */
    protected static ?string $default_environment = null;

    /**
     * Stores all configuration section objects
     *
     * @var array $sections
     */
    protected static array $sections = [];

    /**
     * Tracks if configuration access is allowed without environment available
     *
     * @var bool
     */
    protected static bool $allow_empty_environment = false;


    /**
     * Config class constructor
     *
     * @param string      $section
     * @param string|null $environment
     */
    public function __construct(string $section = 'default', ?string $environment = null)
    {
        $this->setSection($section)
             ->setEnvironment($environment)
             ->read();
    }


    /**
     * Returns the current environment for the configuration object
     *
     * @return string|null
     */
    public static function getDefaultEnvironment(): ?string
    {
        return static::$default_environment;
    }


    /**
     * Sets the configuration default environment
     *
     * @param string|null $environment
     *
     * @return void
     */
    public static function setDefaultEnvironment(?string $environment): void
    {
        if (empty($environment)) {
            if (!static::$allow_empty_environment) {
                throw ConfigException::new('Cannot set empty default environment, this is currently not allowed');
            }

            // Environment was specified as "", use no environment!
            static::$default_environment = null;

        } else {
            // Use the specified environment
            static::$default_environment = strtolower(trim($environment));
        }
    }


    /**
     * Returns the current environment for the configuration object
     *
     * @return string|null
     */
    public function getEnvironment(): ?string
    {
        return $this->environment;
    }


    /**
     * Sets the configuration default environment
     *
     * @param string|null $environment
     *
     * @return static
     */
    protected function setEnvironment(?string $environment): static
    {
        if (empty($environment)) {
            $environment = static::$default_environment;

            // Still empty? This means we don't have a default environment either
            if (empty($environment)) {
                if (!static::$allow_empty_environment) {
                    throw ConfigException::new('Cannot set empty environment, this is currently not allowed');
                }
                // Environment was specified as "", use no environment!
                $this->environment = null;
            }

        } else {
            // Use the specified environment
            $this->environment = strtolower(trim($environment));
        }

        return $this;
    }


    /**
     * Returns the section used by this Config object
     *
     * @return string
     */
    protected function getSection(): string
    {
        return $this->section;
    }


    /**
     * Lets the Config object use the specified (or if not specified, the current global) environment
     *
     * @param string $section
     *
     * @return static
     */
    protected function setSection(string $section): static
    {
        $this->section = (get_null(strtolower(trim($section))) ?? 'default');
        return $this;
    }


    /**
     * Returns a Config object for the specified section and environment
     *
     * If $section is not defined, "default" will be used instead
     * If $environment is not defined, the value of the default_environment variable will be used instead
     *
     * @param string      $section
     * @param string|null $environment
     *
     * @return ConfigInterface
     */
    public static function fromSection(string $section = 'default', ?string $environment = null): ConfigInterface
    {
        if ($environment === null) {
            $environment = static::$default_environment;
        }

        // Check if the requested section / environment exists in memory. If not, create it
        if (empty(static::$sections[$section . $environment])) {
            static::$sections[$section . $environment] = new static($section, $environment);
        }

        return static::$sections[$section . $environment];
    }


    /**
     * Return configuration data for the specified key path
     *
     * @note An environment must be available when calling this method. If (during early startup) the environment is not
     *       available (yet) a ConfigException will be thrown
     *
     * @note If no environment is available (usually during early startup) and static::$allow_no_environment is true,
     *       this method will not throw an exception but will always return the default value instead
     *
     * @param string|array $path    The key path to search for. This should be specified either as an array with key
     *                              names or a "." separated string
     * @param mixed|null   $default The default value to return if no value was found in the configuration files
     *
     * @return mixed
     * @throws ConfigPathDoesNotExistsException | ConfigException
     */
    public function get(string|array $path = '', mixed $default = null): mixed
    {
        if (empty($this->environment)) {
            // We don't really have an environment, don't check configuration
            // NOTE: DO NOT USE TR() HERE AS THE FUNCTIONS FILE MAY NOT YET BE LOADED
            if (!static::$allow_empty_environment) {
                throw ConfigException::new('Cannot access configuration, environment has not been determined yet');
            }

            // Non environment configuration access will ALWAYS return the default value
            return $default;
        }

        Debug::counter('Config::get()')->increase();

        if ($this->failed) {
            // Config class failed, return all default values when not NULL
            if ($default === null) {
                throw new ConfigFailedException('Cannot get configuration, Config object has failed status "' . $this->failed . '"');
            }

            return $default;
        }

        // Do we have cached configuration information?
        $path = Strings::force($path, '.');

        if (array_key_exists($path, $this->cache)) {
            return $this->cache[$path];
        }

        if (!$path) {
            // No path specified, return everything
            return $this->fixKeys($this->data);
        }

        // Replace escaped "." in the path
        $path = str_replace('\\.', ':', $path);
        $data = &$this->data;

        // Go over each key and if the value for the key is an array, request a subsection
        foreach (Arrays::force($path, '.') as $section) {
            $section = str_replace(':', '.', $section);

            if (!is_array($data)) {
//                echo "<pre>";var_dump($path);var_dump($section);var_dump($data);echo PHP_EOL;
                if ($data !== null) {
                    Log::warning(tr('Encountered invalid configuration structure whilst looking for ":path". Section ":section" should contain sub values but does not. Please check your configuration files that this structure exists correctly', [
                        ':path'    => $path,
                        ':section' => $section,
                    ]));
                }

                // This section is missing in config files. No biggie, initialize it as an array
                $data = [];
            }

            if (!array_key_exists($section, $data)) {
                // The requested key does not exist
                if ($default === null) {
                    // We have no default configuration either
                    if (ENVIRONMENT === 'production') {
                        throw ConfigPathDoesNotExistsException::new(tr('The configuration section ":section" from configuration path ":path" does not exist. Please check "ROOT/config/environment/production/:section.yaml"', [
                            ':section'     => $section,
                            ':path'        => Strings::force($path, '.'),
                        ]));
                    }

                    throw ConfigPathDoesNotExistsException::new(tr('The configuration section ":section" from configuration path ":path" does not exist. Please check "ROOT/config/environment/production/:section.yaml" AND ":environment.yaml"', [
                        ':environment' => ENVIRONMENT,
                        ':section'     => $section,
                        ':path'        => Strings::force($path, '.'),
                    ]));
                }

                // The requested key does not exist in configuration, return the default value instead
                return $this->cache[$path] = $default;
            }

            // Get the requested subsection. This subsection must be an array!
            $data = &$data[$section];
        }

        return $this->cache[$path] = $data;
    }


    /**
     * Return configuration BOOLEAN for the specified key path
     *
     * @note Will throw a ConfigException if a non-boolean value is returned
     *
     * @param string|array $path    The configuration path for which the value should be returned
     * @param bool|null    $default The default value to return if the configuration path does not exist. If not specified, or NULL, a
     *
     * @return bool                 The value for the requested path
     * @throws ConfigPathDoesNotExistsException | ConfigException
     */
    public function getBoolean(string|array $path, ?bool $default = null): bool
    {
        $return = $this->get($path, $default);

        try {
            if (is_bool($return)) {
                return $return;
            }

            // Try to interpret as boolean
            return Strings::toBoolean($return);

        } catch (OutOfBoundsException $e) {
            throw ConfigException::new(tr('The configuration path ":path" should be a boolean value (Accepted are true, "true", "yes", "y", "1", false, "false", "no", "n", or 1), but has value ":value" instead', [
                ':path'  => $path,
                ':value' => $return,
            ]), $e)->addData([
                'value' => $return
            ]);
        }
    }


    /**
     * Return configuration INTEGER for the specified key path
     *
     * @note Will throw a ConfigException if a non-integer value is returned
     *
     * @param string|array $path
     * @param int|null     $default
     *
     * @return int
     * @throws ConfigPathDoesNotExistsException | ConfigException
     */
    public function getInteger(string|array $path, ?int $default = null): int
    {
        $return = $this->get($path, $default);

        if (is_numeric_integer($return)) {
            return (int) $return;
        }

        throw ConfigException::new(tr('The configuration path ":path" should be an integer number but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value' => $return
        ]);
    }


    /**
     * Return configuration POSITIVE INTEGER for the specified key path
     *
     * @note Will throw an OutOfBoundsException if a non-positive integer default is specified
     * @note Will throw a ConfigException if a non-positive integer value is returned
     *
     * @param string|array $path
     * @param int|null     $default
     *
     * @return int
     * @throws ConfigPathDoesNotExistsException | ConfigException| OutOfBoundsException
     */
    public function getPositiveInteger(string|array $path, ?int $default = null): int
    {
        if ($default < 0) {
            throw new OutOfBoundsException(tr('The specified default ":default" for configuration path ":path" should be a positive integer number but is negative', [
                ':path'    => $path,
                ':default' => $default,
            ]));
        }

        $return = static::getInteger($path, $default);

        if ($return >= 0) {
            return $return;
        }

        throw ConfigException::new(tr('The configuration path ":path" should be a positive integer number but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value' => $return
        ]);
    }


    /**
     * Return configuration NEGATIVE INTEGER for the specified key path
     *
     * @note Will throw an OutOfBoundsException if a non-negative integer default is specified
     * @note Will throw a ConfigException if a non-negative integer value is returned
     *
     * @param string|array $path
     * @param int|null     $default
     *
     * @return int
     * @throws ConfigPathDoesNotExistsException | ConfigException| OutOfBoundsException
     */
    public function getNegativeInteger(string|array $path, ?int $default = null): int
    {
        if ($default < 0) {
            throw new OutOfBoundsException(tr('The specified default ":default" for configuration path ":path" should be a positive integer number but is negative', [
                ':path'    => $path,
                ':default' => $default,
            ]));
        }

        $return = static::getInteger($path, $default);

        if ($return <= 0) {
            return $return;
        }

        throw ConfigException::new(tr('The configuration path ":path" should be a negative integer number but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value' => $return
        ]);
    }


    /**
     * Return configuration NUMBER for the specified key path
     *
     * @note Will throw an OutOfBoundsException if a non-positive integer default is specified
     * @note Will throw a ConfigException if a non-positive integer value is returned
     *
     * @param string|array   $path
     * @param int|float|null $default
     *
     * @return int
     * @throws ConfigPathDoesNotExistsException | ConfigException| OutOfBoundsException
     */
    public function getNatural(string|array $path, int|float|null $default = null): int
    {
        try {
            return static::getPositiveInteger($path, $default);

        } catch (ConfigException $e) {
            throw ConfigException::new(tr('The configuration path ":path" should return a natural number', [
                ':path'  => $path,
            ]), $e);
        }
    }


    /**
     * Return configuration NUMBER for the specified key path
     *
     * @note Will throw an OutOfBoundsException if a non-positive integer default is specified
     * @note Will throw a ConfigException if a non-positive integer value is returned
     *
     * @param string|array   $path
     * @param int|float|null $default
     *
     * @return int
     * @throws ConfigPathDoesNotExistsException | ConfigException| OutOfBoundsException
     */
    public function getDbId(string|array $path, int|float|null $default = null): int
    {
        try {
            return static::getPositiveInteger($path, $default);

        } catch (ConfigException $e) {
            throw ConfigException::new(tr('The configuration path ":path" should return a database id', [
                ':path'  => $path,
            ]), $e);
        }
    }


    /**
     * Return configuration NUMBER for the specified key path
     *
     * @note Will throw a ConfigException if a non-float value is returned
     *
     * @param string|array   $path
     * @param int|float|null $default
     *
     * @return int|float
     * @throws ConfigPathDoesNotExistsException | ConfigException
     */
    public function getFloat(string|array $path, int|float|null $default = null): int|float
    {
        $return = $this->get($path, $default);

        if (is_numeric_integer($return)) {
            return (int) $return;
        }

        if (is_numeric($return)) {
            return (float) $return;
        }

        throw ConfigException::new(tr('The configuration path ":path" should be a float but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value' => $return
        ]);
    }


    /**
     * Return configuration IteratorInterface for the specified key path
     *
     * @note Will throw a ConfigException if a non-IteratorInterface value is returned
     *
     * @param string|array                 $path
     * @param IteratorInterface|array|null $default
     *
     * @return IteratorInterface
     * @throws ConfigPathDoesNotExistsException | ConfigException
     */
    public function getIteratorObject(string|array $path, IteratorInterface|array|null $default = null): IteratorInterface
    {
        try {
            return new Iterator(static::getArray($path, $default));

        } catch (ConfigException $e) {
            throw ConfigException::new(tr('The configuration path ":path" should return an IteratorInterface object', [
                ':path'  => $path,
            ]), $e);
        }
    }


    /**
     * Return configuration ARRAY for the specified key path
     *
     * @note Will throw a ConfigException if a non-array value is returned
     *
     * @param string|array      $path
     * @param array|null        $default
     * @param array|string|null $require_keys
     *
     * @return array
     */
    public function getArray(string|array $path, array|null $default = null, array|string|null $require_keys = null): array
    {
        $return = $this->get($path, $default);

        if (is_array($return)) {
            $return = $this->fixKeys($return);
            $return = $this->checkKeys($path, $return, $require_keys);

            return $return;
        }

        throw ConfigException::new(tr('The configuration path ":path" should be an array but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value' => $return
        ]);
    }


    /**
     * Return configuration STRING for the specified key path
     *
     * @note Will throw an exception if a non string value is returned!
     *
     * @param string|array $path
     * @param string|null  $default
     *
     * @return string
     */
    public function getString(string|array $path, string|null $default = null): string
    {
        $return = $this->get($path, $default);

        if (is_string($return)) {
            return $return;
        }

        throw ConfigException::new(tr('The configuration path ":path" should be a string but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value' => $return
        ]);
    }


    /**
     * Return configuration STRING or BOOLEAN for the specified key path
     *
     * @note Will throw an exception if a non-string and non-bool value is returned!
     *
     * @note Will automatically convert a variety of values that can be interpreted as boolean, as boolean.
     *       Converted values: FALSE: FALSE, "false", "no" , "n", "off", "0", 0
     *                         TRUE : TRUE , "true" , "yes", "y", "on" , "1", 1
     *
     * @param string|array     $path
     * @param string|bool|null $default
     *
     * @return string|bool
     */
    public function getBoolString(string|array $path, string|bool|null $default = null): string|bool
    {
        $return = $this->get($path, $default);

        try {
            // First try to return boolean. If that fails, we'll try to return a string
            return Strings::toBoolean($return);

        } catch (OutOfBoundsException $e) {
            if (is_string($return)) {
                return $return;
            }

            // fall through
        }

        throw ConfigException::new(tr('The configuration path ":path" should be a string or a boolean value but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]), $e)->addData([
            'value' => $return
        ]);
    }


    /**
     * Fixes configuration key names, - will be replaced with _
     *
     * @param array $data
     *
     * @return array
     */
    protected function fixKeys(array $data): array
    {
        $return = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Recurse
                $value = static::fixKeys($value);
            }

            $return[str_replace('-', '_', (string) $key)] = $value;
        }

        return $return;
    }


    /**
     * Checks if the specified configuration keys are available
     *
     * @param string|array      $path
     * @param array             $source
     * @param array|string|null $keys
     *
     * @return array
     */
    public function checkKeys(string|array $path, array $source, array|string|null $keys): array
    {
        if ($keys) {
            $keys = Arrays::force($keys);

            foreach ($keys as $key) {
                if (!array_key_exists($key, $source)) {
                    throw ConfigException::new(tr('The configuration path ":path" does not have the required key ":key" specified', [
                        ':path' => $path,
                        ':key'  => $key,
                    ]));
                }
            }
        }

        return $source;
    }


    /**
     * Returns true of the specified configuration path exists
     *
     * @param string|array $path The key path to search for. This should be specified either as an array with key names
     *                           or a . separated string
     *
     * @return bool
     */
    public function exists(string|array $path): bool
    {
        $uuid = Strings::getUuid();

        if ($this->get($path, $uuid) === $uuid) {
            // We got the default value, the requested path does not exist
            return false;
        }

        return true;
    }


    /**
     * Return configuration data for the specified key path
     *
     * @param string|array $path    The key path to search for. This should be specified either as an array with key
     *                              names or a . separated string
     * @param mixed        $value
     *
     * @return mixed
     */
    public function set(string|array $path, mixed $value = null): mixed
    {
        $path = Strings::force($path, '.');
        $path = str_replace('\\.', ':', $path);
        $data = &$this->data;

        // Go over each key and if the value for the key is an array, request a subsection
        foreach (Arrays::force($path, '.') as $section) {
            $section = str_replace(':', '.', $section);

            if (!is_array($data)) {
                // Oops, this data section should be an array
                throw ConfigException::new(tr('The configuration section ":section" from requested path ":path" does not exist', [
                    ':section' => $section,
                    ':path'    => $path,
                ]))->makeWarning();
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

        return $this->cache[$path] = $value;
    }


    /**
     * Returns true if a configuration file for the specified environment exists, false if not
     *
     * @param string $environment
     *
     * @return bool
     */
    public static function environmentExists(string $environment): bool
    {
        return file_exists(DIRECTORY_ROOT . 'config/environments/' . $environment);
    }


    /**
     * Scan the entire project from ROOT for config()->get() and config()->set() and generate a config/default.yaml file
     * with all default values
     *
     * @return int The number of configuration paths processed
     */
    public static function generateDefaultYaml(): int
    {
        $count = 0;
        $store = [];

        // Scan all files for config()->get() and config()->set() calls
        PhoDirectory::new(DIRECTORY_ROOT, PhoRestrictions::newWritableObject(DIRECTORY_ROOT))
                 ->execute()
                 ->addSkipDirectories([
                     DIRECTORY_DATA,
                     DIRECTORY_ROOT . 'tests',
                     DIRECTORY_ROOT . 'garbage',
                 ])
                 ->setRecurse(true)
                 ->setRestrictions(new PhoRestrictions(DIRECTORY_ROOT))
                 ->onFiles(function (string $file) use (&$store) {
                     $files = PhoFile::new($file, PhoRestrictions::newReadonlyObject(DIRECTORY_ROOT))
                                     ->grep([
                                      'config()->get(\'',
                                      'config()->set(\'',
                                  ]);

                     foreach ($files as $file) {
                         foreach ($file as $lines) {
                             foreach ($lines as $line) {
                                 // Extract the configuration path and default value for each call
                                 if (!preg_match_all('/Config::[gs]et\s?\([\'"](.+?)[\'"](?:.+?)?(?:,\s?(.+?))\)/i', $line, $matches)) {
                                     if (!preg_match_all('/Config::[gs]et\s?\([\'"](.+?)[\'"](?:.+?)?(?:,\s?(.+?))?\)/i', $line, $matches)) {
                                         Log::warning(tr('Failed to extract a config()->get() or config()->set() from line ":line" in file ":file"', [
                                             ':file' => $file,
                                             ':line' => $line,
                                         ]));
                                         continue;
                                     }
                                 }

                                 // Pass over all matches
                                 foreach ($matches[0] as $match => $value) {
                                     $path    = str_replace(['"', "'"], '', trim($matches[1][$match]));
                                     $default = str_replace(['"', "'"], '', trim($matches[2][$match]));

                                     // Log all config()->get() and config()->set() calls that have the same configuration path but different
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
                     }
                 });

        // Convert all entries ending in "." to array values (these typically have variable sub-keys following)
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
                    // Initialize with subarray and jump in
                    $section[$key] = [];
                }

                $section = &$section[$key];
            }

            $section = $default;
            unset($section);
        }

        // Save and return count
        static::save();

        return $count;
    }


    /**
     * Save the configuration as currently in memory to the configuration file
     *
     * @param array|null $data
     *
     * @return void
     */
    public function save(?array $data = null): void
    {
        if ($data === null) {
            // Save the data from this Config object
            $data = $this->data;
        }

        // Convert the data into yaml and store the data in the default file
        $data = yaml_emit($data);
        $data = Strings::from($data, PHP_EOL);
        $data = Strings::untilReverse($data, PHP_EOL);
        $data = Strings::untilReverse($data, PHP_EOL) . PHP_EOL;

        Log::action(tr('Saving section ":section" from environment ":environment"', [
            ':environment' => static::$default_environment,
            ':section'     => $this->section
        ]));

        file_put_contents(DIRECTORY_ROOT . 'config/environments/' . static::$default_environment . '/' . $this->section . '.yaml', $data);
    }


    /**
     * Import data from the specified setup configuration and save it in a yaml config file for the current environment
     *
     * @param Configuration $configuration
     *
     * @return void
     * @throws Exception
     */
    public function import(Configuration $configuration): void
    {
        // Reset data, then import data
        static::reset();

        $this->data = [
            'security'      => [
                'seed' => Strings::getRandom(random_int(16, 32)),
            ],
            'debug'         => [
                'enabled'    => (static::$default_environment !== 'production'),
                'production' => (static::$default_environment === 'production'),
            ],
            'project'       => [
                'name'    => $configuration->getProject(),
                'version' => '0.0.0',
            ],
            'languages'     => [
                'supported' => ['en'],
                'default'   => 'en',
            ],
            'databases'     => [
                'sql' => [
                    'debug'     => (static::$default_environment === 'production'),
                    'instances' => [
                        'system' => [
                            'type'   => 'mysql',
                            'server' => $configuration->getDatabase()
                                                      ->getHost(),
                            'name'   => $configuration->getDatabase()
                                                      ->getName(),
                            'user'   => $configuration->getDatabase()
                                                      ->getUser(),
                            'pass'   => $configuration->getDatabase()
                                                      ->getPass(),
                        ],
                    ],
                ],
                'memcached' => [
                    'instances' => [
                        'system' => null,
                    ],
                ],
            ],

            'notifications' => [
                'groups' => [
                    'developer' => [
                        $configuration->getEmail(),
                    ],
                ],
            ],

            'web'           => [
                'minify'   => false,
                'sessions' => [
                    'cookies' => [
                        'secure' => false,
                        'domain' => 'auto',
                    ],
                ],

                'domains'  => [
                    'primary'     => [
                        'www' => 'http://' . $configuration->getDomain() . '/:LANGUAGE/',
                        'cdn' => 'http://cdn.' . $configuration->getDomain() . '/:LANGUAGE/',
                    ],
                    'whitelabel1' => [
                        'www' => 'https://whitelabel1.phoundation.org/:LANGUAGE/',
                        'cdn' => 'https://cdn.whitelabel1.phoundation.org/:LANGUAGE/',
                    ],
                ],

                'route'    => [
                    'known-hacks' => [],
                ],
            ],
        ];
    }


    /**
     * Reset this Config object
     *
     * @return void
     */
    protected function reset(): void
    {
        $this->failed = false;
        $this->files  = [];
        $this->data   = [];
        $this->cache  = [];
    }


    /**
     * Escapes . in the specified path section
     *
     * @param string $path
     *
     * @return string
     */
    public static function escape(string $path): string
    {
        return str_replace('.', '\\.', str_replace('_', '-', $path));
    }


    /**
     * Reads the configuration file for the specified configuration environment and section
     *
     * @note When the Core class enters the shutdown phase (Core::isStateShutdown() === true) Config::read() will no longer
     *        read any files to avoid endless loop issues
     *
     * @param bool $include_production
     *
     * @return bool
     */
    protected function read(bool $include_production = true): bool
    {
        try {
            if (!$this->environment) {
                // We don't really have an environment, don't read configuration
                return false;
            }

            if (Core::isStateShutdown()) {
                // During the shutdown phase, no configuration files may be read to avoid endless looping issues.
                // During the shutdown phase, any config::get() requests that required reading this file will have to
                // return the default values
                return false;
            }

            // What environments should be read?
            if ($this->environment === 'production') {
                $environments = [
                    'production',
                ];

            } elseif ($include_production) {
                $environments = [
                    'production',
                    static::$default_environment,
                ];

            } else {
                // Read only the specified environment
                $environments = [static::$default_environment];
            }

            // Read the section for each environment
            foreach ($environments as $environment) {
                try {
                    $file = DIRECTORY_ROOT . 'config/environments/' . $environment . '/' . $this->section . '.yaml';
                    if (Core::isReady()) {
                        // Only check restrictions if Core is ready to avoid endless loops
                        PhoRestrictions::new(DIRECTORY_ROOT . 'config/')
                                       ->check($file, false);
                    }
                    // Production configuration files are the only ones that MUST exist, the rest is optional
                    if (!file_exists($file)) {
                        if ($environment === 'production') {
                            // Do NOT use tr() here as it will cause endless loops!
                            throw ConfigFileDoesNotExistsException::new('Production Configuration file "' . Strings::from($file, DIRECTORY_ROOT) . '" does not exist')
                                                                  ->makeWarning();
                        }
                    }
                    // Read the configuration data and merge it in the internal configuration data array
                    try {
                        $data = yaml_parse_file($file);
                        if ($data === false) {
                            throw new ConfigParseFailedException($this->generateExceptionMessage($file));
                        }
                        if (!is_array($data)) {
                            if ($data) {
                                throw new ConfigParseFailedException(tr('Configuration data in file ":file" has an invalid format', [
                                    ':file' => $file,
                                ]));
                            }
                            // It looks like the configuration file was empty
                            $data = [];
                        }
                    } catch (Throwable $e) {
                        throw new ConfigParseFailedException($this->generateExceptionMessage($file), $e);
                    }

                } catch (ConfigParseFailedException $f) {
                    // Keep on throwing, we want to know it's a parsing error
                    throw $f;

                } catch (Throwable $f) {
                    // Failed to read YAML data from the configuration file
                    throw ConfigReadFailedException::new($this->generateExceptionMessage($file), $f)->makeWarning();
                }

                // Merge parsed data over the static configuration data array
                $this->data = Arrays::mergeFull($this->data, $data);
            }

            return true;

        } catch (ConfigException $e) {
            // Do NOT use Log class here as log class requires config which just now failed... Same goes for tr()!
            $this->failed = 'Failed to load configuration file "' . isset_get($file) . '" because: ' . $e->getMessage();
            throw $e;
        }
    }


    /**
     * Returns true if the Confg object has any sections available
     *
     * @return bool
     */
    public static function hasSections(): bool
    {
        return (bool) count(static::$sections);
    }


    /**
     * Sets (and returns) the Config::$failed variable using the specified $file and $message
     *
     * @param string $file
     *
     * @return string
     */
    protected function generateExceptionMessage(string $file): string
    {
        $this->failed = 'Failed to get data from configuration file "' . Strings::from($file, DIRECTORY_ROOT) . '" for environment "' . Strings::log($this->environment) . '" and section "' . $this->section . '"';
        return $this->failed;
    }


    /**
     * Will allow config()->get() calls with ENVIRONMENT not (yet) defined
     *
     * @return void
     */
    public static function allowNoEnvironment(): void
    {
        static::$allow_empty_environment = true;
    }


    /**
     * Will not allow config()->get() calls with ENVIRONMENT not (yet) defined
     *
     * @return void
     */
    public static function disallowNoEnvironment(): void
    {
        static::$allow_empty_environment = false;
    }


    /**
     * Returns true if the Config object has failed
     *
     * @return string|false
     */
    public function getFailed(): string|false
    {
        return $this->failed;
    }
}
