<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


use Phoundation\Utils\Strings;

/**
 * Trait DataAction
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataStaticExecuted
{
    /**
     * Tracks the path that is executed
     *
     * @var string $executed_path
     */
    protected static string $executed_path = '_none_';

    /**
     * Tracks the file that is executed
     *
     * @var string $executed_file
     */
    protected static string $executed_file = '_none_';


    /**
     * Returns the executed file name
     *
     * @return string
     */
    public static function getExecutedFile(): string
    {
        return static::$executed_file;
    }


    /**
     * Returns the executed path
     *
     * @return string
     */
    public static function getExecutedPath(): string
    {
        return static::$executed_path;
    }


    /**
     * Sets the executed path
     *
     * @param string $executed_path
     * @return void
     */
    protected static function setExecutedPath(string $executed_path): void
    {
        static::$executed_path = Strings::from($executed_path, DIRECTORY_ROOT);
        static::$executed_file = Strings::fromReverse(static::$executed_path, '/');
    }
}