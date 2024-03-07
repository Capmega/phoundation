<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Interfaces;

use Phoundation\Filesystem\Interfaces\RestrictionsInterface;


/**
 * Interface ProcessCore
 *
 * This interface embodies a process that will be executed
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 * @uses \Phoundation\Os\Processes\ProcessVariables
 */
interface ProcessInterface extends ProcessCoreInterface
{
    /**
     * Set the command to be executed for this process
     *
     * @param string|null $command
     * @param bool $which_command
     * @param bool $clear_arguments
     * @return static This process so that multiple methods can be chained
     */
    public function setCommand(?string $command, bool $which_command = true, bool $clear_arguments = true): static;

    /**
     * Create a new process factory
     *
     * @param string|null $command
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param string|null $operating_system
     * @param string|null $packages
     * @return static
     */
    public static function new(?string $command = null, RestrictionsInterface|array|string|null $restrictions = null, ?string $operating_system = null, ?string $packages = null): static;
}
