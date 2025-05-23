<?php

namespace Phoundation\Accounts\Config\Interfaces;

use Exception;
use Phoundation\Accounts\Config\Exception\ConfigDataTypeException;
use Phoundation\Accounts\Config\Exception\ConfigException;
use Phoundation\Accounts\Config\Exception\ConfigFailedException;
use Phoundation\Accounts\Config\Exception\ConfigPathDoesNotExistsException;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Developer\Project\Configuration;
use Phoundation\Exception\OutOfBoundsException;

interface ConfigInterface
{
    /**
     * Returns the current environment for the configuration object
     *
     * @return string|null
     */
    public function getEnvironment(): ?string;


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
     * @param bool         $use_cache                If true will attempt to use cached configuration paths
     *
     * @return mixed
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException
     */
    public function get(string|array $path = '', mixed $default = null, bool $allow_user_configuration = false, bool $use_cache = true): mixed;


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
     * @param bool         $use_cache                If true will attempt to use cached configuration paths
     *
     * @return bool                                  The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getBoolean(string|array $path, ?bool $default = null, bool $allow_user_configuration = false, bool $use_cache = true): bool;


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
     * @param bool         $use_cache                If true will attempt to use cached configuration paths
     *
     * @return int                                   The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getInteger(string|array $path, ?int $default = null, bool $allow_user_configuration = false, bool $use_cache = true): int;


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
     * @param bool         $use_cache                If true will attempt to use cached configuration paths
     *
     * @return int                                   The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException | OutOfBoundsException
     */
    public function getPositiveInteger(string|array $path, ?int $default = null, bool $allow_user_configuration = false, bool $use_cache = true): int;


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
     * @param bool         $use_cache                If true will attempt to use cached configuration paths
     *
     * @return int                                   The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException | OutOfBoundsException
     */
    public function getNegativeInteger(string|array $path, ?int $default = null, bool $allow_user_configuration = false, bool $use_cache = true): int;


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
     * @param bool         $use_cache                If true will attempt to use cached configuration paths
     *
     * @return int                                   The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getNatural(string|array $path, int|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): int;


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
     * @param bool         $use_cache                If true will attempt to use cached configuration paths
     *
     * @return int                                   The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getDbId(string|array $path, int|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): int;


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
     * @param bool           $use_cache                If true will attempt to use cached configuration paths
     *
     * @return int|float                               The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getFloat(string|array $path, int|float|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): int|float;


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
     * @param bool $use_cache                                        If true will attempt to use cached configuration
     *                                                               paths
     *
     * @return IteratorInterface                                     The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getIteratorObject(string|array $path, IteratorInterface|array|null $default = null, array|string|null $require_keys = null, bool $allow_user_configuration = false, bool $use_cache = true): IteratorInterface;


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
     * @param bool         $use_cache                If true will attempt to use cached configuration paths
     *
     * @return array                                 The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getArray(string|array $path, array|null $default = null, array|string|null $require_keys = null, bool $allow_user_configuration = false, bool $use_cache = true): array;


    /**
     * Return configuration STRING for the specified key path
     *
     * @note Will throw an exception if a non-string value is returned!
     *
     * @param string|array $path                     The configuration path for which the value should be returned
     * @param string|null  $default                  The default value to return if the configuration path doesn't
     *                                               exist. If not specified, or NULL, an exception will be thrown when
     *                                               the path doesn't exist
     * @param bool         $allow_user_configuration If true will allow user configuration to override system
     *                                               configuration
     * @param bool         $use_cache                If true will attempt to use cached configuration paths
     *
     * @return string                                The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getString(string|array $path, string|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): string;


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
    public function getArrayString(string|array $path, array|string|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): array|string;


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
    public function getArrayBoolean(string|array $path, array|bool|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): array|bool;


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
     * @param bool             $use_cache                If true will attempt to use cached configuration paths
     *
     * @return string|bool                               The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getStringBoolean(string|array $path, string|bool|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): string|bool;


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
     * @param bool             $use_cache                 If true will attempt to use cached configuration paths
     *
     * @return integer|bool                               The value for the requested path
     *
     * @throws ConfigFailedException | ConfigPathDoesNotExistsException | ConfigException | ConfigDataTypeException
     */
    public function getIntegerBoolean(string|array $path, string|bool|null $default = null, bool $allow_user_configuration = false, bool $use_cache = true): int|bool;


    /**
     * Checks if the specified configuration keys are available
     *
     * @param string|array      $path
     * @param array             $source
     * @param array|string|null $keys
     *
     * @return array
     */
    public function checkKeys(string|array $path, array $source, array|string|null $keys): array;


    /**
     * Returns true of the specified configuration path exists
     *
     * @param string|array $path The key path to search for. This should be specified either as an array with key names
     *                           or a . separated string
     *
     * @return bool
     */
    public function exists(string|array $path): bool;


    /**
     * Return configuration data for the specified key path
     *
     * @param string|array $path    The key path to search for. This should be specified either as an array with key
     *                              names or a . separated string
     * @param mixed        $value
     *
     * @return mixed
     */
    public function set(string|array $path, mixed $value = null): mixed;


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
    public function updateUserPath(mixed $value, string $path, ?int $users_id = null): static;


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
    public function deleteUserPath(string $path, ?int $users_id = null): static;


    /**
     * Save the configuration as currently in memory to the configuration file
     *
     * @param array|null $data
     *
     * @return void
     */
    public function save(?array $data = null): void;


    /**
     * Import data from the specified setup configuration and save it in a yaml config file for the current environment
     *
     * @param Configuration $configuration
     *
     * @return void
     * @throws Exception
     */
    public function import(Configuration $configuration): void;


    /**
     * Returns true if the Config object has failed
     *
     * @return string|false
     */
    public function getFailed(): string|false;


    /**
     * Returns the source of this configuration object
     *
     * @return array
     */
    public function getSource(): array;
}
