<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Interfaces;

use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;

interface ProcessInterface extends ProcessCoreInterface
{
    /**
     * Create a new process factory
     *
     * @param string|null                                       $command
     * @param FsRestrictionsInterface|FsDirectoryInterface|null $execution_directory_or_restrictions
     * @param string|null                                       $operating_system
     * @param string|null                                       $packages
     *
     * @return static
     */
    public static function new(?string $command = null, FsRestrictionsInterface|FsDirectoryInterface|null $execution_directory_or_restrictions = null, ?string $operating_system = null, ?string $packages = null): static;


    /**
     * Set the command to be executed for this process
     *
     * @param string|null $command
     * @param bool        $which_command
     * @param bool        $clear_arguments
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setCommand(?string $command, bool $which_command = true, bool $clear_arguments = true): static;

    /**
     * Returns true if the process can execute the specified command with sudo privileges
     *
     * @param string $command
     * @param bool   $exception
     *
     * @return bool
     * @todo Find a better option than "--version" which may not be available for everything. What about shell commands
     *       like "true", or "which", etc?
     */
    public function sudoAvailable(string $command, bool $exception = false): bool;
}
