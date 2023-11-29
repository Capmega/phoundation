<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Utils\Strings;


/**
 * Trait DataStaticIsExecutedPath
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
    use DataStaticIsExecutedPath;


    /**
     * Tracks the path that is executed
     *
     * @var array $executed_path
     */
    protected static array $executed_path = [];

    /**
     * Tracks the file that is executed
     *
     * @var array $executed_file
     */
    protected static array $executed_file = [];


    /**
     * Returns the executed file name
     *
     * @return string
     */
    public static function getExecutedFile(): string
    {
        if (empty(static::$executed_file)) {
            return '_none_';
        }

        return implode(' > ', static::$executed_file);
    }


    /**
     * Returns the executed path
     *
     * @return string
     */
    public static function getExecutedPath(): string
    {
        if (empty(static::$executed_path)) {
            return '_none_';
        }

        return implode(' > ', static::$executed_path);
    }


    /**
     * Sets the executed path
     *
     * @param string $executed
     * @return void
     */
    protected static function addExecuted(string $executed): void
    {
        $executed = Strings::from($executed, DIRECTORY_ROOT);

        static::$executed_path[] = $executed;
        static::$executed_file[] = Strings::fromReverse($executed, '/');
    }
}