<?php

declare(strict_types=1);

namespace Phoundation\Cli\Interfaces;

use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;

/**
 * Class RunFile
 *
 * This class manages run files for Phoundation command processes
 *
 * Phoundation command processes are all commands that are available in the ROOT/system/commands/ directory
 *
 * Run files are stored as ROOT/system/run/PROCESS/PROCESSID
 *
 * If PROCESS is "accounts/users/create" with PID 6345 then the run file is ROOT/system/run/accounts/users/create/6345
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cli
 */
interface CliRunFileInterface extends FsFileInterface
{
    /**
     * Returns the command for this runfile
     *
     * @return string
     */
    public function getCommand(): string;

    /**
     * Returns the pid for this runfile
     *
     * @return int
     */
    public function getPid(): int;

    /**
     * Returns the path where all run files are located
     *
     * @return FsDirectoryInterface
     */
    public function getDirectory(): FsDirectoryInterface;

    /**
     * Returns the run file for this process
     *
     * @return FsFileInterface
     */
    public function getFile(): FsFileInterface;

    /**
     * Returns the first found PID for the specified command, if it currently runs. NULL otherwise
     *
     * @return int|null
     */
    public function getPidForCommand(): ?int;

    /**
     * Returns an array with all PIDs for the specified command, if it currently runs.
     *
     * @return array
     */
    public function getPidsForCommand(): array;

    /**
     * Return the number of this command being run
     *
     * @return int
     */
    public function getCount(): int;
}
