<?php

declare(strict_types=1);

namespace Phoundation\Processes;

use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Commands\Command;
use Phoundation\Processes\Commands\Exception\CommandsException;
use Phoundation\Processes\Enum\ExecuteMethod;
use Phoundation\Processes\Enum\Interfaces\ExecuteMethodInterface;
use Phoundation\Processes\Exception\ProcessException;
use Phoundation\Processes\Exception\ProcessFailedException;
use Phoundation\Processes\Interfaces\ProcessInterface;
use Phoundation\Processes\Interfaces\ProcessVariablesInterface;
use Phoundation\Servers\Server;


/**
 * Class Process
 *
 * This class embodies a process that will be executed
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 * @uses \Phoundation\Processes\ProcessVariables
 */
Class Process extends ProcessCore implements ProcessInterface
{
    /**
     * Processes constructor.
     *
     * @param string|null $command
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param string|null $packages
     */
    public function __construct(?string $command = null, RestrictionsInterface|array|string|null $restrictions = null, ?string $packages = null)
    {
        // Ensure that the run files directory is available
        Path::new(PATH_ROOT . 'data/run/', Restrictions::new(PATH_DATA . 'run', true))->ensure();

        $this->setRestrictions($restrictions);

        if ($packages) {
            $this->setPackages($packages);
        }

        if ($command) {
            $this->setInternalCommand($command);
        }
    }


    /**
     * Create a new process factory
     *
     * @param string|null $command
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param string|null $packages
     * @return static
     */
    public static function new(?string $command = null, RestrictionsInterface|array|string|null $restrictions = null, ?string $packages = null): static
    {
        return new static($command, $restrictions, $packages);
    }


    /**
     * Set the command to be executed for this process
     *
     * @param string|null $command
     * @param bool $which_command
     * @return static This process so that multiple methods can be chained
     */
    public function setCommand(?string $command, bool $which_command = true): static
    {
        return $this->setInternalCommand($command, $which_command);
    }


    /**
     * Command exception handler
     *
     * @param string $command
     * @param Exception $e
     * @param callable|null $function
     * @return void
     */
    protected static function handleException(string $command, Exception $e, ?callable $function = null): void
    {
        if ($e->getData()['output']) {
            $data       = $e->getData()['output'];
            $first_line = Arrays::firstValue($data);
            $first_line = strtolower($first_line);
            $last_line  = Arrays::lastValue($data);
            $last_line  = strtolower($last_line);

            // Process specified handlers
            if ($function) {
                $function($first_line, $last_line, $e);
            }

            // Handlers were unable to make a clear exception out of this, show the standard command exception
            throw new CommandsException(tr('The command :command failed with ":output"', [
                ':command' => $command,
                ':output' => $data
            ]));
        }

        // The process generated no output. Process specified handlers
        if ($function) {
            $function(null, null, $e);
        }

        // Something else went wrong, no CLI output available
        throw new CommandsException(tr('The command :command failed for unknown reasons', [
            ':command' => $command
        ]));
    }
}
