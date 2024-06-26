<?php

declare(strict_types=1);

namespace Phoundation\Developer;

use DateTime;
use Phoundation\Filesystem\FsDirectory;
use Stringable;

/**
 * Class Mtime
 *
 * This class can check if specified files have an mtime equal to DIRECTORY_DATA/system/mtime and indicate if they have
 * changed since or not. This can be used for caching purposes to speed up certain processes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */
class Mtime
{
    /**
     * Singleton
     *
     * @var Mtime $instance
     */
    protected static Mtime $instance;

    /**
     * The root directory for this class
     *
     * @var string $directory
     */
    protected static string $directory = DIRECTORY_DATA . 'system/mtime/';


    /**
     * Mtime constructor
     */
    protected function __construct()
    {
        FsDirectory::new(static::$directory)
                 ->ensure();
    }


    /**
     * Returns true if the mtime for the specified file is different than the cached mtime, meaning the file has been
     * modified
     *
     * @param Stringable|string $file
     * @param string            $class
     *
     * @return bool
     */
    public static function isModified(Stringable|string $file, string $class = 'default'): bool
    {
        return filemtime($file) === static::read($class);
    }


    /**
     * Updates the mtime for the specified class
     *
     * @param string $class
     *
     * @return int|null
     */
    protected static function read(string $class): ?int
    {
        static::getInstance();
        if (file_exists(static::$directory . $class)) {
            return filemtime(static::$directory . $class);
        }

        return null;
    }


    /**
     * Returns the singleton
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }


    /**
     * Updates the mtime for the specified class
     *
     * @param string            $class
     * @param DateTime|int|null $datetime
     *
     * @return void
     */
    public static function updateClass(string $class = 'default', DateTime|int|null $datetime = null): void
    {
        static::write($class, $datetime);
    }


    /**
     * Updates the mtime for the specified class
     *
     * @param string            $class
     * @param DateTime|int|null $datetime
     *
     * @return void
     */
    protected static function write(string $class, DateTime|int|null $datetime): void
    {
        static::getInstance();
        if ($datetime) {
            if ($datetime instanceof DateTime) {
                $datetime = $datetime->getTimestamp();
            }
        }
        touch(static::$directory . $class, $datetime);
    }
}
