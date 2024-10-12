<?php

/**
 * Class Process
 *
 * This class embodies a process that will be executed
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 * @uses      ProcessVariables
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes;

use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;


class Process extends ProcessCore
{
    /**
     * Processes constructor.
     *
     * @param string|null                                       $command
     * @param FsRestrictionsInterface|FsDirectoryInterface|null $execution_directory_or_restrictions
     * @param string|null                                       $operating_system
     * @param string|null                                       $packages
     */
    public function __construct(?string $command = null, FsRestrictionsInterface|FsDirectoryInterface|null $execution_directory_or_restrictions = null, ?string $operating_system = null, ?string $packages = null)
    {
        parent::__construct($execution_directory_or_restrictions);

        if ($operating_system or $packages) {
            $this->setPackages($operating_system, $packages);
        }

        if ($command) {
            $this->setCommand($command);
        }
    }


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
    public static function new(?string $command = null, FsRestrictionsInterface|FsDirectoryInterface $execution_directory_or_restrictions = null, ?string $operating_system = null, ?string $packages = null): static
    {
        return new static($command, $execution_directory_or_restrictions, $operating_system, $packages);
    }
}
