<?php

/**
 * Class Process
 *
 * This class embodies a process that will be executed
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 * @uses      TraitProcessVariables
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes;

use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;


class Process extends ProcessCore
{
    /**
     * Processes constructor.
     *
     * @param string|null                                         $command
     * @param PhoRestrictionsInterface|PhoDirectoryInterface|null $execution_directory_or_restrictions
     * @param string|null                                         $operating_system
     * @param string|null                                         $packages
     * @param bool                                                $which_command
     */
    public function __construct(?string $command = null, PhoRestrictionsInterface|PhoDirectoryInterface|null $execution_directory_or_restrictions = null, ?string $operating_system = null, ?string $packages = null, bool $which_command = true)
    {
        parent::__construct($execution_directory_or_restrictions);

        if ($operating_system or $packages) {
            $this->setPackages($operating_system, $packages);
        }

        if ($command) {
            $this->setCommand($command, $which_command);
        }
    }


    /**
     * Create a new process factory
     *
     * @param string|null                                         $command
     * @param PhoRestrictionsInterface|PhoDirectoryInterface|null $execution_directory_or_restrictions
     * @param string|null                                         $operating_system
     * @param string|null                                         $packages
     * @param bool                                                $which_command
     *
     * @return static
     */
    public static function new(?string $command = null, PhoRestrictionsInterface|PhoDirectoryInterface|null $execution_directory_or_restrictions = null, ?string $operating_system = null, ?string $packages = null, bool $which_command = true): static
    {
        return new static($command, $execution_directory_or_restrictions, $operating_system, $packages, $which_command);
    }
}
