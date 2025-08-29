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
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Config;

use Exception;
use Phoundation\Accounts\Config\Exception\ConfigDataTypeException;
use Phoundation\Accounts\Config\Exception\ConfigEnvironmentDoesNotExistException;
use Phoundation\Accounts\Config\Exception\ConfigException;
use Phoundation\Accounts\Config\Exception\ConfigFailedException;
use Phoundation\Accounts\Config\Exception\ConfigFileDoesNotExistsException;
use Phoundation\Accounts\Config\Exception\ConfigParseFailedException;
use Phoundation\Accounts\Config\Exception\ConfigPathDoesNotExistsException;
use Phoundation\Accounts\Config\Exception\ConfigReadFailedException;
use Phoundation\Accounts\Config\Interfaces\ConfigInterface;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Date\Enums\EnumDateFormat;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Developer\Project\Configuration;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Stringable;
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
     * @var string|null $section
     */
    protected ?string $section = null;

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
     * @param string|null $section
     * @param string|null $environment
     */
    public function __construct(?string $section = null, ?string $environment = null)
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
        return $this->section ?? ENVIRONMENT;
    }


    /**
     * Lets the Config object use the specified (or if not specified, the current global) environment
     *
     * @param string|null $section
     *
     * @return static
     */
    protected function setSection(?string $section): static
    {
        $this->section = get_null(strtolower(trim((string) $section)));
        return $this;
    }


    /**
     * Returns a Config object for the specified section and environment
     *
     * If $section is not defined, "default" will be used instead
     * If $environment is not defined, the value of the default_environment variable will be used instead
     *
     * @param string|null $section
     * @param string|null $environment
     *
     * @return ConfigInterface
     */
    public static function fromSection(?string $section = null, ?string $environment = null): ConfigInterface
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
     * @param string|array $path                     The key path to search for. This should be specified either as an
     *                                               array with key names or a "." separated string
     * @param mixed|null   $default                  The default value to return if the configuration path doesn't
     *                                               exist. If not specified, or NULL, an exception will be thrown when
     *                                               the path doesn't exist
     * @param bool         $allow_user_configuration If true will allow user configuration to override system
     *                                               configuration
     * @param bool         $use_cache                If true will allow user configuration to be stored in and read from
     *                                               cache
     *
     * @return mixed
     *
     */
    public function get(string|array $path = '', mixed $default = null, bool $allow_user_configuration = false, bool $use_cache = true): mixed
    {
        if (empty($this->environment)) {
            // We don't really have an environment, don't check configuration
            if (!static::$allow_empty_environment) {
                // NOTE: Don't use tr() or ts() here as functions may not be loaded yet, and it can lead to endless loops
                throw ConfigException::new('Cannot access configuration, environment has not been determined yet');
            }

            // Non environment configuration access will ALWAYS return the default value
            return $default;
        }

        Debug::counter('Config::get()')->increase();

        if ($this->failed) {
            // Config class failed, return all default values when not NULL
            if ($default === null) {
                // NOTE: Don't use tr() or ts() here as functions may not be loaded yet, and it can lead to endless loops
                throw new ConfigFailedException('Cannot get configuration, Config object has failed status "' . $this->failed . '"');
            }

            return $default;
        }

        // Is there a cached configuration available?
        $path = Strings::force($path, '.');

        if ($use_cache and array_key_exists($path, $this->cache)) {
            return $this->cache[$path];
        }

        if (!$path) {
            // No path specified, return everything
            return $this->fixKeys($this->data);
        }

        // Allow user configuration to override system configuration?
        if ($allow_user_configuration) {
            if (Core::isReady()) {
                $data = sql()->getColumn('SELECT `value` FROM `accounts_configurations` WHERE `users_id` = :users_id AND `path` = :path', [
                    ':users_id' => Session::getUserObject()->getId(),
                    ':path'     => $path,
                ]);

                if ($data !== null) {
                    if ($data !== '') {
                        // Cache result and return
                        return $this->cache[$path] = $data;
                    }

                    config()->deleteUserPath($path);

                    Incident::new()
                            ->setType('invalid-data')
                            ->setTitle(tr('Invalid user configuration data'))
                            ->setBody(tr('Configuration path ":path" for user ":user" contains invalid value ":value". The configuration value will be ignored and has been removed from the database', [
                                ':path'  => $path,
                                ':user'  => Session::getUserObject()->getLogId(),
                                ':value' => $data,
                            ]))
                            ->setData([
                                'path'  => $path,
                                'value' => $data,
                                'user'  => Session::getUserObject()->getLogId(),
                            ])
                            ->setLog(9)
                            ->setNotifyRoles('developer')
                            ->save();
                    }
            }
        }

        // Replace escaped "." in the path
        $path = str_replace('\\.', ':', $path);
        $data = &$this->data;

        // Go over each key and if the value for the key is an array, request a subsection
        foreach (Arrays::force($path, '.') as $part) {
            $part = str_replace(':', '.', $part);

            if (!is_array($data)) {
                if ($data !== null) {
                    Log::warning(ts('Encountered invalid configuration structure whilst looking for ":path". Section ":section" should contain sub values but does not. Please check your configuration files that this structure exists correctly', [
                        ':path'    => $path,
                        ':section' => $part,
                    ]));
                }

                // This section is missing in config files. No biggie, initialize it as an array
                $data = [];
            }

            if (!array_key_exists($part, $data)) {
                // The requested key doesn't exist
                if ($default === null) {
                    // We have no default configuration either
                    if (ENVIRONMENT === 'production') {
                        throw ConfigPathDoesNotExistsException::new(tr('The configuration part ":part" from configuration path ":path" does not exist. Please check "ROOT/config/environments/production/:section.yaml"', [
                            ':section' => $this->getSection(),
                            ':part'    => $part,
                            ':path'    => Strings::force($path, '.'),
                        ]));
                    }

                    throw ConfigPathDoesNotExistsException::new(tr('The configuration part ":part" from configuration path ":path" does not exist. Please check "ROOT/config/environments/production/:section.yaml" AND "ROOT/config/environments/:environment/:section.yaml"', [
                        ':environment' => $this->getEnvironment(),
                        ':section'     => $this->getSection(),
                        ':part'        => $part,
                        ':path'        => Strings::force($path, '.'),
                    ]));
                }

                // The requested key doesn't exist in configuration, return the default value instead
                return $this->cache[$path] = $default;
            }

            // Get the requested subsection. This subsection must be an array!
            $data = &$data[$part];
        }

        return $this->cache[$path] = $data;
    }


    /**
     * Return configuration BOOLEAN for the specified key path
     *
     * @note Will throw a ConfigException if a non-boolean value is returned
     *
     * @param string|array $path                     The configuration path for which the value should be returned
     * @param bool|null    $default                  The default value to return if the configuration path doesn't
     *                                               exist. If not specified, or NULL, an exception will be thrown when
     *                                               the path doesn't exist
     * @param bool         $allow_user_configuration If true will allow user configuration to override system
     *                                               configuration
     * @param bool         $use_cache                If true will allow user configuration to be stored in and read from
     *                                               cache
     *
     * @return bool                                  The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getBoolean(string|array $path, ?bool $default = null, bool $allow_user_configuration = false, bool $use_cache = true): bool
    {
        $return = $this->get($path, $default, $allow_user_configuration, $use_cache);

        try {
            if (is_bool($return)) {
                return $return;
            }

            // Try to interpret as boolean
            return Strings::toBoolean($return);

        } catch (OutOfBoundsException $e) {
            throw ConfigDataTypeException::new(tr('The configuration path ":path" should hold a boolean value (Accepted are true, "true", "yes", "y", "1", false, "false", "no", "n", or 1), but has ":value" instead', [
                ':path'  => $path,
                ':value' => $return,
            ]), $e)->addData([
                                 'value'      => $return,
                                 'value_type' => gettype($return)
                             ]);
        }
    }


    /**
     * Return configuration BOOLEAN or NULL for the specified key path
     *
     * @note Will throw a ConfigException if a non-boolean, non-ternary value is returned
     *
     * @note Accepted ternary values are NULL or "auto". "auto" will be converted to NULL.
     *
     * @param string|array $path                     The configuration path for which the value should be returned
     * @param bool|null    $default                  The default value to return if the configuration path doesn't
     *                                               exist. If not specified, or NULL, an exception will be thrown when
     *                                               the path doesn't exist
     * @param bool         $allow_user_configuration If true will allow user configuration to override system
     *                                               configuration
     * @param bool         $use_cache                If true will allow user configuration to be stored in and read from
     *                                               cache
     *
     * @return bool|null                             The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getTristate(string|array $path, ?bool $default = null, bool $allow_user_configuration = false, bool $use_cache = true): ?bool
    {
        $return = $this->get($path, $default, $allow_user_configuration, $use_cache);

        try {
            if (is_bool($return)) {
                return $return;
            }

            // Try to interpret as boolean
            return Strings::toBoolean($return);

        } catch (OutOfBoundsException $e) {
            // Could still be the ternary value!
            if (($return === null) or ($return === 'auto')) {
                return null;
            }

            throw ConfigDataTypeException::new(tr('The configuration path ":path" should hold a boolean value (Accepted are true, "true", "yes", "y", "1", false, "false", "no", "n", or 1), but has ":value" instead', [
                ':path'  => $path,
                ':value' => $return,
            ]), $e)->addData([
                'value'      => $return,
                'value_type' => gettype($return)
            ]);
        }
    }


    /**
     * Return configuration INTEGER for the specified key path
     *
     * @note Will throw a ConfigException if a non-integer value is returned
     *
     * @param string|array $path                     The configuration path for which the value should be returned
     * @param int|null     $default                  The default value to return if the configuration path doesn't
     *                                               exist. If not specified, or NULL, an exception will be thrown when
     *                                               the path doesn't exist
     * @param bool         $allow_user_configuration If true will allow user configuration to override system
     *                                               configuration
     * @param bool         $use_cache                If true will allow user configuration to be stored in and read from
     *                                               cache
     *
     * @return int                                   The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getInteger(string|array $path, ?int $default = null, bool $allow_user_configuration = false, bool $use_cache = true): int
    {
        $return = $this->get($path, $default, $allow_user_configuration, $use_cache);

        if (is_numeric_integer($return)) {
            return (int) $return;
        }

        throw ConfigDataTypeException::new(tr('The configuration path ":path" should hold an integer value but has ":value" instead', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value'      => $return,
            'value_type' => gettype($return)
        ]);
    }


    /**
     * Return configuration INTEGER for the specified key path
     *
     * @note Will throw a ConfigException if a non-integer value is returned
     *
     * @param string|array                   $path                     The configuration path for which the value should be returned
     * @param IteratorInterface|array|string $in_array                 The values in which the configured value must lie
     * @param string|float|int|null          $default                  The default value to return if the configuration path doesn't
     *                                                                 exist. If not specified, or NULL, an exception will be thrown when
     *                                                                 the path doesn't exist
     * @param bool                           $allow_user_configuration If true will allow user configuration to override system
     *                                                                 configuration
     * @param bool                           $use_cache                If true will allow user configuration to be stored in and read from
     *                                                                 cache
     *
     * @return string|float|int                                        The value for the requested path
     *
     */
    public function getInArray(string|array $path, IteratorInterface|array|string $in_array, string|float|int|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): string|float|int
    {
        $in_array = Arrays::force($in_array);
        $return   = $this->get($path, $default, $allow_user_configuration, $use_cache);

        if (in_array($return, $in_array)) {
            return $return;
        }

        throw ConfigDataTypeException::new(tr('The configuration path ":path" should be one of ":in" but has value ":value" instead', [
            ':path'  => $path,
            ':value' => $return,
            ':in'    => Strings::force($in_array),
        ]))->addData([
            'path'       => $path,
            'value'      => $return,
            'must_be_in' => $in_array,
            'value_type' => gettype($return)
        ]);
    }


    /**
     * Return configuration POSITIVE INTEGER for the specified key path
     *
     * @note Will throw an OutOfBoundsException if a non-positive integer default is specified
     * @note Will throw a ConfigException if a non-positive integer value is returned
     *
     * @param string|array $path                     The configuration path for which the value should be returned
     * @param int|null     $default                  The default value to return if the configuration path doesn't
     *                                               exist. If not specified, or NULL, an exception will be thrown when
     *                                               the path doesn't exist
     * @param bool         $allow_user_configuration If true will allow user configuration to override system
     *                                               configuration
     * @param bool         $use_cache                If true will allow user configuration to be stored in and read from
     *                                               cache
     *
     * @return int                                   The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException | OutOfBoundsException
     */
    public function getPositiveInteger(string|array $path, ?int $default = null, bool $allow_user_configuration = false, bool $use_cache = true): int
    {
        if ($default < 0) {
            throw new OutOfBoundsException(tr('The specified default ":default" for configuration path ":path" should hold a positive integer number but is negative', [
                ':path'    => $path,
                ':default' => $default,
            ]));
        }

        $return = static::getInteger($path, $default, $allow_user_configuration, $use_cache);

        if ($return >= 0) {
            return $return;
        }

        throw ConfigDataTypeException::new(tr('The configuration path ":path" should hold a positive integer number but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value'      => $return,
            'value_type' => gettype($return)
        ]);
    }


    /**
     * Return configuration NEGATIVE INTEGER for the specified key path
     *
     * @note Will throw an OutOfBoundsException if a non-negative integer default is specified
     * @note Will throw a ConfigException if a non-negative integer value is returned
     *
     * @param string|array $path                     The configuration path for which the value should be returned
     * @param int|null     $default                  The default value to return if the configuration path doesn't
     *                                               exist. If not specified, or NULL, an exception will be thrown when
     *                                               the path doesn't exist
     * @param bool         $allow_user_configuration If true will allow user configuration to override system
     *                                               configuration
     * @param bool         $use_cache                If true will allow user configuration to be stored in and read from
     *                                               cache
     *
     * @return int                                   The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException | OutOfBoundsException
     */
    public function getNegativeInteger(string|array $path, ?int $default = null, bool $allow_user_configuration = false, bool $use_cache = true): int
    {
        if ($default < 0) {
            throw new OutOfBoundsException(tr('The specified default ":default" for configuration path ":path" should hold a positive integer number but is negative', [
                ':path'    => $path,
                ':default' => $default,
            ]));
        }

        $return = static::getInteger($path, $default, $allow_user_configuration, $use_cache);

        if ($return <= 0) {
            return $return;
        }

        throw ConfigDataTypeException::new(tr('The configuration path ":path" should hold a negative integer number but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value'      => $return,
            'value_type' => gettype($return)
        ]);
    }


    /**
     * Return configuration NUMBER for the specified key path
     *
     * @note Will throw an OutOfBoundsException if a non-positive integer default is specified
     * @note Will throw a ConfigException if a non-positive integer value is returned
     *
     * @param string|array $path                     The configuration path for which the value should be returned
     * @param int|null     $default                  The default value to return if the configuration path doesn't
     *                                               exist. If not specified, or NULL, an exception will be thrown
     *                                               when the path doesn't exist
     * @param bool         $allow_user_configuration If true will allow user configuration to override system
     *                                               configuration
     * @param bool         $use_cache                If true will allow user configuration to be stored in and read from
     *                                               cache
     *
     * @return int                                   The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getNatural(string|array $path, int|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): int
    {
        try {
            return static::getPositiveInteger($path, $default, $allow_user_configuration, $use_cache);

        } catch (ConfigDataTypeException $e) {
            throw ConfigDataTypeException::new(tr('The configuration path ":path" should return a natural number', [
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
     * @param string|array $path                     The configuration path for which the value should be returned
     * @param int|null     $default                  The default value to return if the configuration path doesn't
     *                                               exist. If not specified, or NULL, an exception will be thrown
     *                                               when the path doesn't exist
     * @param bool         $allow_user_configuration If true will allow user configuration to override system
     *                                               configuration
     * @param bool         $use_cache                If true will allow user configuration to be stored in and read from
     *                                               cache
     *
     * @return int                                   The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getDbId(string|array $path, int|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): int
    {
        try {
            return static::getPositiveInteger($path, $default, $allow_user_configuration, $use_cache);

        } catch (ConfigDataTypeException $e) {
            throw ConfigDataTypeException::new(tr('The configuration path ":path" should return a database id', [
                ':path'  => $path,
            ]), $e);
        }
    }


    /**
     * Return configuration NUMBER for the specified key path
     *
     * @note Will throw a ConfigException if a non-float value is returned
     *
     * @param string|array   $path                     The configuration path for which the value should be returned
     * @param int|float|null $default                  The default value to return if the configuration path doesn't
     *                                                 exist. If not specified, or NULL, an exception will be thrown
     *                                                 when the path doesn't exist
     * @param bool           $allow_user_configuration If true will allow user configuration to override system
     *                                                 configuration
     * @param bool           $use_cache                If true will allow user configuration to be stored in and read
     *                                                 from cache
     *
     * @return int|float                               The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getFloat(string|array $path, int|float|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): int|float
    {
        $return = $this->get($path, $default, $allow_user_configuration, $use_cache);

        if (is_numeric_integer($return)) {
            return (int) $return;
        }

        if (is_numeric($return)) {
            return (float) $return;
        }

        throw ConfigDataTypeException::new(tr('The configuration path ":path" should hold a float but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value'      => $return,
            'value_type' => gettype($return)
        ]);
    }


    /**
     * Return configuration IteratorInterface for the specified key path
     *
     * @note Will throw a ConfigException if a non-IteratorInterface value is returned
     *
     * @param string|array                 $path                     The configuration path for which the value should
     *                                                               be returned
     * @param IteratorInterface|array|null $default                  The default value to return if the configuration
     *                                                               path doesn't exist. If not specified, or NULL, an
     *                                                               exception will be thrown when the path doesn't
     *                                                               exist
     * @param bool                         $allow_user_configuration If true will allow user configuration to override
     *                                                               system configuration
     * @param bool                         $use_cache                If true will allow user configuration to be stored
     *                                                               in and read from cache
     *
     * @return IteratorInterface                                     The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getIteratorObject(string|array $path, IteratorInterface|array|null $default = null, array|string|null $require_keys = null, bool $allow_user_configuration = false, bool $use_cache = true): IteratorInterface
    {
        try {
            return new Iterator(static::getArray($path, $default, $require_keys, $allow_user_configuration, $use_cache));

        } catch (ConfigDataTypeException $e) {
            throw ConfigDataTypeException::new(tr('The configuration path ":path" should return an IteratorInterface object', [
                ':path'  => $path,
            ]), $e);
        }
    }


    /**
     * Return configuration ARRAY for the specified key path
     *
     * @note Will throw a ConfigException if a non-array value is returned
     *
     * @param string|array $path                     The configuration path for which the value should be returned
     * @param array|null   $default                  The default value to return if the configuration path doesn't
     *                                               exist. If not specified, or NULL, an exception will be thrown when
     *                                               the path doesn't exist
     * @param bool         $allow_user_configuration If true will allow user configuration to override system
     *                                               configuration
     * @param bool         $use_cache                If true will allow user configuration to be stored in and read from
     *                                               cache
     *
     * @return array                                 The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getArray(string|array $path, array|null $default = null, array|string|null $require_keys = null, bool $allow_user_configuration = false, bool $use_cache = true): array
    {
        $return = $this->get($path, $default, $allow_user_configuration, $use_cache);

        if (is_array($return)) {
            $return = $this->fixKeys($return);
            $return = $this->checkKeys($path, $return, $require_keys);

            return $return;
        }

        throw ConfigDataTypeException::new(tr('The configuration path ":path" should hold an "array" value but has ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value'      => $return,
            'value_type' => gettype($return)
        ]);
    }


    /**
     * Return configuration STRING for the specified key path
     *
     * @note Will throw an exception if a non-string value is returned!
     *
     * @param string|array           $path                     The configuration path for which the value should be
     *                                                         returned
     * @param Stringable|string|null $default                  The default value to return if the configuration path
     *                                                         doesn't exist. If not specified, or NULL, an exception
     *                                                         will be thrown when the path doesn't exist
     * @param bool                   $allow_user_configuration If true will allow user configuration to override system
     *                                                         configuration
     * @param bool                   $use_cache                If true will allow user configuration to be stored in and
     *                                                         read from cache
     *
     * @return string                                The value for the requested path
     *
     */
    public function getString(string|array $path, Stringable|string|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): string
    {
        $return = $this->get($path, (string) $default, $allow_user_configuration, $use_cache);

        if (is_string($return)) {
            return $return;
        }

        throw ConfigDataTypeException::new(tr('The configuration path ":path" should hold a string but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value'      => $return,
            'value_type' => gettype($return)
        ]);
    }


    /**
     * Return configuration STRING for the specified key path in uppercase
     *
     * @note Will throw an exception if a non-string value is returned!
     *
     * @param string|array           $path                     The configuration path for which the value should be
     *                                                         returned
     * @param Stringable|string|null $default                  The default value to return if the configuration path
     *                                                         doesn't exist. If not specified, or NULL, an exception
     *                                                         will be thrown when the path doesn't exist
     * @param bool                   $allow_user_configuration If true will allow user configuration to override system
     *                                                         configuration
     * @param bool                   $use_cache                If true will allow user configuration to be stored in and
     *                                                         read from cache
     *
     * @return string                                The value for the requested path
     *
     */
    public function getStringUppercase(string|array $path, Stringable|string|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): string
    {
        return strtoupper($this->getString($path, $default, $allow_user_configuration, $use_cache));
    }


    /**
     * Return configuration STRING for the specified key path in lowercase
     *
     * @note Will throw an exception if a non-string value is returned!
     *
     * @param string|array           $path                     The configuration path for which the value should be
     *                                                         returned
     * @param Stringable|string|null $default                  The default value to return if the configuration path
     *                                                         doesn't exist. If not specified, or NULL, an exception
     *                                                         will be thrown when the path doesn't exist
     * @param bool                   $allow_user_configuration If true will allow user configuration to override system
     *                                                         configuration
     * @param bool                   $use_cache                If true will allow user configuration to be stored in and
     *                                                         read from cache
     *
     * @return string                                The value for the requested path
     *
     */
    public function getStringLowercase(string|array $path, Stringable|string|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): string
    {
        return strtolower($this->getString($path, $default, $allow_user_configuration, $use_cache));
    }


    /**
     * Return configuration STRING for the specified key path
     *
     * @note Will throw an exception if a non-string value is returned!
     *
     * @param string|array $path                     The configuration path for which the value should be returned
     * @param string|int|null  $default              The default value to return if the configuration path doesn't
     *                                               exist. If not specified, or NULL, an exception will be thrown when
     *                                               the path doesn't exist
     * @param bool         $allow_user_configuration If true will allow user configuration to override system
     *                                               configuration
     * @param bool         $use_cache                If true will allow user configuration to be stored in and read from
     *                                               cache
     *
     * @return string|int                            The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getStringInteger(string|array $path, string|int|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): string|int
    {
        $return = $this->get($path, $default, $allow_user_configuration, $use_cache);

        if (is_int($return)) {
            return $return;
        }

        if (is_string($return)) {
            return $return;
        }

        throw ConfigDataTypeException::new(tr('The configuration path ":path" should hold a string but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value'      => $return,
            'value_type' => gettype($return)
        ]);
    }


    /**
     * Return configuration ARRAY or STRING for the specified key path
     *
     * @note Will throw an exception if a non-string non-array value is returned!
     *
     * @param string|array      $path                     The configuration path for which the value should be returned
     * @param array|string|null $default                  The default value to return if the configuration path doesn't
     *                                                    exist. If not specified, or NULL, an exception will be thrown
     *                                                    when the path doesn't exist
     * @param bool              $allow_user_configuration If true will allow user configuration to override system
     *                                                    configuration
     * @param bool              $use_cache                If true will attempt to use cached configuration paths
     *
     * @return array|string                               The value for the requested path
     *
     * @throws ConfigFailedException
     * @throws ConfigPathDoesNotExistsException
     * @throws ConfigException
     * @throws ConfigDataTypeException
     */
    public function getArrayString(string|array $path, array|string|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): array|string
    {
        $return = $this->get($path, $default, $allow_user_configuration, $use_cache);

        if (is_string($return)) {
            return $return;
        }

        if (is_array($return)) {
            return $return;
        }

        throw ConfigDataTypeException::new(tr('The configuration path ":path" should hold an array or a string but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value'      => $return,
            'value_type' => gettype($return)
        ]);
    }


    /**
     * Return configuration ARRAY or BOOLEAN for the specified key path
     *
     * @note Will throw an exception if a non-string non-array value is returned!
     *
     * @param string|array    $path                     The configuration path for which the value should be returned
     * @param array|bool|null $default                  The default value to return if the configuration path doesn't
     *                                                  exist. If not specified, or NULL, an exception will be thrown
     *                                                  when the path doesn't exist
     * @param bool            $allow_user_configuration If true will allow user configuration to override system
     *                                                  configuration
     * @param bool            $use_cache                If true will allow user configuration to be stored in and read
     *                                                  from cache
     *
     * @return array|bool                               The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getArrayBoolean(string|array $path, array|bool|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): array|bool
    {
        $return = $this->get($path, $default, $allow_user_configuration, $use_cache);

        if (is_bool($return)) {
            return $return;
        }

        if (is_array($return)) {
            return $return;
        }

        throw ConfigDataTypeException::new(tr('The configuration path ":path" should hold an array or a boolean but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value'      => $return,
            'value_type' => gettype($return)
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
     * @param string|array     $path                     The configuration path for which the value should be returned
     * @param string|bool|null $default                  The default value to return if the configuration path doesn't
     *                                                   exist. If not specified, or NULL, an exception will be thrown
     *                                                   when the path doesn't exist
     * @param bool             $allow_user_configuration If true will allow user configuration to override system
     *                                                   configuration
     * @param bool             $use_cache                If true will allow user configuration to be stored in and read
     *                                                   from cache
     *
     * @return string|bool                               The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getStringBoolean(string|array $path, string|bool|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): string|bool
    {
        $return = $this->get($path, $default, $allow_user_configuration, $use_cache);

        try {
            // First try to return boolean. If that fails, we'll try to return a string
            return Strings::toBoolean($return);

        } catch (OutOfBoundsException $e) {
            if (is_string($return)) {
                return $return;
            }

            // fall through
        }

        throw ConfigDataTypeException::new(tr('The configuration path ":path" should hold a string or a boolean value but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]), $e)->addData([
            'value'      => $return,
            'value_type' => gettype($return)
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
     * @param string|array     $path                      The configuration path for which the value should be returned
     * @param string|bool|null $default                   The default value to return if the configuration path doesn't
     *                                                    exist. If not specified, or NULL, an exception will be thrown
     *                                                    when the path doesn't exist
     * @param bool             $allow_user_configuration  If true will allow user configuration to override system
     *                                                    configuration
     * @param bool             $use_cache                 If true will allow user configuration to be stored in and read
     *                                                    from cache
     *
     * @return integer|bool                               The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getIntegerBoolean(string|array $path, string|bool|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): int|bool
    {
        $return = $this->get($path, $default, $allow_user_configuration, $use_cache);

        if (is_bool($return)) {
            return $return;
        }

        if (is_int($return)) {
            return $return;
        }

        throw ConfigDataTypeException::new(tr('The configuration path ":path" should hold a integer or a boolean value but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]))->addData([
            'value'      => $return,
            'value_type' => gettype($return)
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
                // Oops, this data section should hold an array
                throw ConfigException::new(tr('Cannot set configuration path ":path", the configuration section ":section" does not exist', [
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
                 ->setRestrictionsObject(new PhoRestrictions(DIRECTORY_ROOT))
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
                                         Log::warning(ts('Failed to extract a config()->get() or config()->set() from line ":line" in file ":file"', [
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
                                             Log::warning(ts('Configuration path ":path" has two different default values ":1" and ":2"', [
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
     * Deletes the specified configuration path for the specified user
     *
     * @note If no users_id was specified, the current session users id will be used
     *
     * @param string   $path
     * @param int|null $users_id
     *
     * @return static
     */
    public function deleteUserPath(string $path, ?int $users_id = null): static
    {
        sql()->delete('accounts_configurations', [
            'users_id' => $users_id ?? Session::getUserObject()->getid(),
            'path'     => $path
        ]);

        return $this;
    }


    /**
     * Updates the specified user configuration path to the specified value
     *
     * @note If no users_id was specified, the current session users id will be used
     *
     * @param mixed    $value
     * @param string   $path
     * @param int|null $users_id
     *
     * @return static
     */
    public function updateUserPath(mixed $value, string $path, ?int $users_id = null): static
    {
        if (is_bool($value)) {
            $value = $value ? 1 : 0;
        }

        sql()->insert('accounts_configurations', [
            'users_id'    => $users_id ?? Session::getUserObject()->getid(),
            'value'       => $value,
            'path'        => $path,
        ], [
            'value'       => $value,
        ]);

        return $this;
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

        Log::action(ts('Saving section ":section" from environment ":environment"', [
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
                            'type'   => EnumDateFormat::mysql_datetime,
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
     * @param string|int $path
     *
     * @return string
     */
    public static function escape(string|int $path): string
    {
        return str_replace('.', '\\.', str_replace('_', '-', (string) $path));
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
            if ($this->failed) {
                // The Config class is in failed state, do not load more configuration files
                return false;
            }

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
                    $file = DIRECTORY_ROOT . 'config/environments/' . $environment . '/' . ($this->section ?? $environment) . '.yaml';

                    if (Core::isReady()) {
                        // Only check restrictions if Core is ready to avoid endless loops
                        PhoRestrictions::new(DIRECTORY_ROOT . 'config/')
                                       ->check($file, false);
                    }

                    // Production configuration files are the only ones that MUST exist, the rest is optional
                    if (!file_exists($file)) {
                        if ($environment === 'production') {
                            // Do NOT use tr() here as it will cause endless loops!
                            throw ConfigFileDoesNotExistsException::new('Production configuration file "' . Strings::from($file, DIRECTORY_ROOT) . '" does not exist')
                                                                  ->makeWarning();
                        }

                        // Check if the directory for this environment exists. If it does not, then we conclude that the
                        // environment does not exist
                        if (!file_exists(dirname($file))) {
                            // Do NOT use tr() here as it will cause endless loops!
                            throw ConfigEnvironmentDoesNotExistException::new('Environment "' . $environment . '" does not exist')
                                                                        ->makeWarning();
                        }

                        continue;
                    }

                    // Read the configuration data and merge it in the internal configuration data array
                    try {
                        // TODO Add test on what happens when yaml_parse_file() parses an empty file. It SHOULD pass, empty files are allowed
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


    /**
     * Returns the source of this configuration object
     *
     * @return array
     */
    public function getSource(): array
    {
        return $this->data;
    }


    public function getEnvironments(): IteratorInterface
    {

    }
}
