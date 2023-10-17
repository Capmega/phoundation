<?php

namespace Phoundation\Core\Sessions\Interfaces;


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
interface ConfigInterface
{
    /**
     * Gets session configuration if available, or default configuration if not
     *
     * @param string|array $path
     * @param mixed|null $default
     * @param mixed|null $specified
     * @return mixed
     */
    public function get(string|array $path, mixed $default = null, mixed $specified = null): mixed;

    /**
     * Return configuration BOOLEAN for the specified key path
     *
     * @note Will cause an exception if a non-boolean value is returned!
     * @param string|array $path
     * @param bool|null $default
     * @param mixed|null $specified
     * @return bool
     */
    public function getBoolean(string|array $path, ?bool $default = null, mixed $specified = null): bool;

    /**
     * Return configuration INTEGER for the specified key path
     *
     * @note Will cause an exception if a non integer value is returned!
     * @param string|array $path
     * @param int|null $default
     * @param mixed|null $specified
     * @return int
     */
    public function getInteger(string|array $path, ?int $default = null, mixed $specified = null): int;

    /**
     * Return configuration NUMBER for the specified key path
     *
     * @note Will cause an exception if a non-numeric value is returned!
     * @param string|array $path
     * @param int|float|null $default
     * @param mixed|null $specified
     * @return int|float
     */
    public function getNatural(string|array $path, int|float|null $default = null, mixed $specified = null): int|float;

    /**
     * Return configuration NUMBER for the specified key path
     *
     * @note Will cause an exception if a non-numeric value is returned!
     * @param string|array $path
     * @param int|float|null $default
     * @param mixed|null $specified
     * @return int|float
     */
    public function getFloat(string|array $path, int|float|null $default = null, mixed $specified = null): int|float;

    /**
     * Return configuration ARRAY for the specified key path
     *
     * @note Will cause an exception if a non array value is returned!
     * @param string|array $path
     * @param array|null $default
     * @param mixed|null $specified
     * @return array
     */
    public function getArray(string|array $path, array|null $default = null, mixed $specified = null): array;

    /**
     * Return configuration STRING for the specified key path
     *
     * @note Will cause an exception if a non string value is returned!
     * @param string|array $path
     * @param string|null $default
     * @param mixed|null $specified
     * @return string
     */
    public function getString(string|array $path, string|null $default = null, mixed $specified = null): string;

    /**
     * Return configuration STRING or BOOLEAN for the specified key path
     *
     * @note Will cause an exception if a non string or bool value is returned!
     * @param string|array $path
     * @param string|bool|null $default
     * @param mixed|null $specified
     * @return string|bool
     */
    public function getBoolString(string|array $path, string|bool|null $default = null, mixed $specified = null): string|bool;
}