<?php

/**
 * Trait TraitDataStaticIsExecutedPath
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Utils\Strings;

trait TraitDataStaticExecuted
{
    use TraitDataStaticIsExecutedPath;

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
     * @param bool $from_root
     *
     * @return string
     */
    public static function getExecutedPath(bool $from_root = false): string
    {
        if (empty(static::$executed_path)) {
            return '_none_';
        }

        $return = static::$executed_path;

        if ($from_root) {
            foreach ($return as &$path) {
                $path = Strings::from($path, DIRECTORY_DATA);
                $path = Strings::from($path, 'data/system/cache/');
            }
        }

        unset($path);

        return implode(', ', $return);
    }


    /**
     * Returns the executed path
     *
     * @param bool $from_root
     *
     * @return string
     */
    public static function getCurrentExecutedPath(bool $from_root = false): string
    {
        if (empty(static::$executed_path)) {
            return '_none_';
        }

        $path = end(static::$executed_path);

        if ($from_root) {
            $path = Strings::from($path, DIRECTORY_DATA);
            $path = Strings::from($path, 'data/system/cache/');
        }

        return $path;
    }


    /**
     * Sets the executed path
     *
     * @param string $executed
     *
     * @return void
     */
    protected static function addExecutedPath(string $executed): void
    {
        $executed = Strings::from($executed, DIRECTORY_ROOT);

        static::$executed_path[] = $executed;
        static::$executed_file[] = Strings::fromReverse($executed, '/');
    }
}
