<?php

declare(strict_types=1);

namespace Phoundation\Cli\Interfaces;

use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;

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
