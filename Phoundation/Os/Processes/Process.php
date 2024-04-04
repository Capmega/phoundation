<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes;

use Phoundation\Exception\Exception;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Os\Processes\Commands\Exception\CommandNotFoundException;
use Phoundation\Os\Processes\Commands\Exception\CommandsException;
use Phoundation\Os\Processes\Commands\Exception\NoSudoException;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Interfaces\ProcessInterface;
use Phoundation\Utils\Arrays;


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
class Process extends ProcessCore implements ProcessInterface
{
    /**
     * Processes constructor.
     *
     * @param string|null                             $command
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param string|null                             $operating_system
     * @param string|null                             $packages
     */
    public function __construct(?string $command = null, RestrictionsInterface|array|string|null $restrictions = null, ?string $operating_system = null, ?string $packages = null)
    {
        parent::__construct($restrictions);

        if ($operating_system or $packages) {
            $this->setPackages($operating_system, $packages);
        }

        if ($command) {
            $this->setCommand($command);
        }
    }

    /**
     * Command exception handler
     *
     * @param string        $command
     * @param Exception     $e
     * @param callable|null $function
     *
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
                ':output'  => $data,
            ]));
        }

        // The process generated no output. Process specified handlers
        if ($function) {
            $function(null, null, $e);
        }

        // Something else went wrong, no CLI output available
        throw new CommandsException(tr('The command :command failed for unknown reasons', [
            ':command' => $command,
        ]));
    }

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
    public function sudoAvailable(string $command, bool $exception = false): bool
    {
        try {
            Process::new($this->command, $this->getRestrictions())
                   ->setSudo(true)
                   ->addArgument('--version')
                   ->executeReturnArray();

            return true;

        } catch (CommandNotFoundException) {
            if ($exception) {
                throw new NoSudoException(tr('Cannot check for sudo privileges for the ":command" command, the command was not found', [
                    ':command' => $command,
                ]));
            }

        } catch (ProcessFailedException) {
            if ($exception) {
                throw new NoSudoException(tr('The current process owner has no sudo privileges available for the ":command" command', [
                    ':command' => $command,
                ]));
            }
        }

        return false;
    }

    /**
     * Create a new process factory
     *
     * @param string|null                             $command
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param string|null                             $operating_system
     * @param string|null                             $packages
     *
     * @return static
     */
    public static function new(?string $command = null, RestrictionsInterface|array|string|null $restrictions = null, ?string $operating_system = null, ?string $packages = null): static
    {
        return new static($command, $restrictions, $operating_system, $packages);
    }
}
