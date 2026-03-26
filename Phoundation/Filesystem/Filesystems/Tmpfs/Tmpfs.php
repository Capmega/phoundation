<?php

/**
 * Class Tmpfs
 *
 * This is the core TMPFS filesystem management class.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Filesystems\Tmpfs;

use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Os\Processes\Commands\Mount;


class Tmpfs
{
    /**
     * Tracks the directory where this tmpfs is mounted
     *
     * @var PhoDirectoryInterface $_directory
     */
    public PhoDirectoryInterface $_directory;

    /**
     * Tracks the size of this tmpfs device
     *
     * @var int $size
     */
    public int $size;


    /**
     * Tmpfs class constructor
     */
    public function __construct(PhoDirectoryInterface $_directory)
    {
        $this->_directory = $_directory;
    }


    /**
     * Returns a new Tmpfs object
     */
    public static function new(PhoDirectoryInterface $_directory): static
    {
        return new static($_directory);
    }


    /**
     * Returns all currently mounted tmpfs devices and basic information about each
     *
     * @return array
     */
    public static function getCurrent(): array
    {

    }


    /**
     * Creates a new tmpfs filesystem of the specified size and mounts it on the specified directory, and returns a Tmpfs object for the new filesystem
     *
     * In essence, this executes "mount -o size=16G -t tmpfs none $_directory"
     *
     * @param PhoDirectoryInterface $_directory
     * @param int                   $size
     * @return static
     */
    public static function create(PhoDirectoryInterface $_directory, int $size): static
    {
        Mount::new()
             ->executeNoReturn();

        return static::new($_directory);
    }
}
