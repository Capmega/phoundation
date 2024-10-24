<?php

declare(strict_types=1);

namespace Phoundation\Cli\Interfaces;

use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;

interface CliRunFileInterface extends PhoFileInterface
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
     * @return PhoDirectoryInterface
     */
    public function getDirectory(): PhoDirectoryInterface;

    /**
     * Returns the run file for this process
     *
     * @return PhoFileInterface
     */
    public function getFile(): PhoFileInterface;

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
