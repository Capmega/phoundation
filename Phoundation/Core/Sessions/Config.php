<?php

namespace Phoundation\Core\Sessions;

use Phoundation\Core\Exception\ConfigException;
use Phoundation\Core\Sessions\Interfaces\ConfigInterface;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Class Config
 *
 * This class will try to return configuration data from the user or if missing, the system
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Config extends \Phoundation\Core\Config implements ConfigInterface
{
    /**
     * Singleton variable for main config object
     *
     * @var ConfigInterface|null $session_instance
     */
    protected static ?ConfigInterface $session_instance = null;


    /**
     * Singleton, ensure to always return the same Log object.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (!isset(static::$session_instance)) {
            static::$session_instance = new static();
        }

        return static::$session_instance;
    }


    /**
     * Gets session configuration if available, or default configuration if not
     *
     * @param string|array $path
     * @param mixed|null $default
     * @param mixed|null $specified
     * @return mixed
     */
    public static function get(string|array $path, mixed $default = null, mixed $specified = null): mixed
    {
        // TODO Add support for user configuration
        return parent::get($path, $default, $specified);
    }


//    /**
//     * Return configuration BOOLEAN for the specified key path
//     *
//     * @note Will cause an exception if a non-boolean value is returned!
//     * @param string|array $path
//     * @param bool|null $default
//     * @param mixed|null $specified
//     * @return bool
//     */
//    public static function getBoolean(string|array $path, ?bool $default = null, mixed $specified = null): bool
//    {
//        $return = static::get($path, $default, $specified);
//
//        try {
//            if (is_bool($return)) {
//                return $return;
//            }
//
//            // Try to interpret as boolean
//            return Strings::toBoolean($return);
//
//        } catch(OutOfBoundsException) {
//            // Do nothing, following exception will do the job
//        }
//
//        throw new ConfigException(tr('The configuration path ":path" should be a boolean value (Accepted are true, "true", "yes", "y", "1", false, "false", "no", "n", or 1), but has value ":value" instead', [
//            ':path'  => $path,
//            ':value' => $return
//        ]));
//    }
//
//
//    /**
//     * Return configuration INTEGER for the specified key path
//     *
//     * @note Will cause an exception if a non integer value is returned!
//     * @param string|array $path
//     * @param int|null $default
//     * @param mixed|null $specified
//     * @return int
//     */
//    public static function getInteger(string|array $path, ?int $default = null, mixed $specified = null): int
//    {
//        $return = static::get($path, $default, $specified);
//
//        if (is_integer($return)) {
//            return $return;
//        }
//
//        throw new ConfigException(tr('The configuration path ":path" should be an integer number but has value ":value"', [
//            ':path'  => $path,
//            ':value' => $return
//        ]));
//    }
//
//
//    /**
//     * Return configuration NUMBER for the specified key path
//     *
//     * @note Will cause an exception if a non-numeric value is returned!
//     * @param string|array $path
//     * @param int|float|null $default
//     * @param mixed|null $specified
//     * @return int|float
//     */
//    public static function getNatural(string|array $path, int|float|null $default = null, mixed $specified = null): int|float
//    {
//        $return = static::get($path, $default, $specified);
//
//        if (is_natural($return)) {
//            return $return;
//        }
//
//        throw new ConfigException(tr('The configuration path ":path" should be a natural number, integer 0 or above, but has value ":value"', [
//            ':path'  => $path,
//            ':value' => $return
//        ]));
//    }
//
//
//    /**
//     * Return configuration NUMBER for the specified key path
//     *
//     * @note Will cause an exception if a non-numeric value is returned!
//     * @param string|array $path
//     * @param int|float|null $default
//     * @param mixed|null $specified
//     * @return int|float
//     */
//    public static function getFloat(string|array $path, int|float|null $default = null, mixed $specified = null): int|float
//    {
//        $return = static::get($path, $default, $specified);
//
//        if (is_float($return)) {
//            return $return;
//        }
//
//        throw new ConfigException(tr('The configuration path ":path" should be a number but has value ":value"', [
//            ':path'  => $path,
//            ':value' => $return
//        ]));
//    }
//
//
//    /**
//     * Return configuration ARRAY for the specified key path
//     *
//     * @note Will cause an exception if a non array value is returned!
//     * @param string|array $path
//     * @param array|null $default
//     * @param mixed|null $specified
//     * @return array
//     */
//    public static function getArray(string|array $path, array|null $default = null, mixed $specified = null): array
//    {
//        $return = static::get($path, $default, $specified);
//
//        if (is_array($return)) {
//            return static::fixKeys($return);
//        }
//
//        throw new ConfigException(tr('The configuration path ":path" should be an array but has value ":value"', [
//            ':path'  => $path,
//            ':value' => $return
//        ]));
//    }
//
//
//    /**
//     * Return configuration STRING for the specified key path
//     *
//     * @note Will cause an exception if a non string value is returned!
//     * @param string|array $path
//     * @param string|null $default
//     * @param mixed|null $specified
//     * @return string
//     */
//    public static function getString(string|array $path, string|null $default = null, mixed $specified = null): string
//    {
//        $return = static::get($path, $default, $specified);
//
//        if (is_string($return)) {
//            return $return;
//        }
//
//        throw new ConfigException(tr('The configuration path ":path" should be a string but has value ":value"', [
//            ':path'  => $path,
//            ':value' => $return
//        ]));
//    }
//
//
//    /**
//     * Return configuration STRING or BOOLEAN for the specified key path
//     *
//     * @note Will cause an exception if a non string or bool value is returned!
//     * @param string|array $path
//     * @param string|bool|null $default
//     * @param mixed|null $specified
//     * @return string|bool
//     */
//    public static function getBoolString(string|array $path, string|bool|null $default = null, mixed $specified = null): string|bool
//    {
//        $return = static::get($path, $default, $specified);
//
//        if (is_string($return) or is_bool($return)) {
//            return $return;
//        }
//
//        throw new ConfigException(tr('The configuration path ":path" should be a string but has value ":value"', [
//            ':path'  => $path,
//            ':value' => $return
//        ]));
//    }
}