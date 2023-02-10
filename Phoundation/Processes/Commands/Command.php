<?php

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Arrays;
use Phoundation\Core\Core;
use Phoundation\Exception\Exception;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Commands\Exception\CommandsException;
use Phoundation\Processes\Exception\ProcessFailedException;
use Phoundation\Processes\Process;



/**
 * Class Commands
 *
 * This class contains various easy-to-use and ready-to-go command line commands in static methods.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Command
{
    /**
     * Where will this be executed? Locally or on the specified server
     *
     * @var Restrictions $restrictions
     */
    protected Restrictions $restrictions;



    /**
     * Command class constructor
     *
     * @param Restrictions|array|string|null $restrictions
     */
    public function __construct(Restrictions|array|string|null $restrictions = null)
    {
        $this->setRestrictions($restrictions);
    }



    /**
     * Returns a new Images object
     *
     * @param Restrictions|array|string|null $restrictions
     * @return static
     */
    public static function new(Restrictions|array|string|null $restrictions = null): static
    {
        return new static($restrictions);
    }



    /**
     * Sets the server for this commands object
     *
     * Sets the server by name or object, NULL for localhost
     *
     * @param Restrictions|array|string|null $restrictions
     * @return static
     */
    public function setRestrictions(Restrictions|array|string|null $restrictions = null): static
    {
        $this->restrictions = Core::ensureRestrictions($restrictions);
        return $this;
    }



    /**
     * Returns the server object for this commands object
     *
     * @return Restrictions
     */
    public function getRestrictions(): Restrictions
    {
        return $this->restrictions;
    }



    /**
     * Returns a commands object for this local machine
     *
     * @return static
     */
    public static function local(): static
    {
        return new static();
    }



    /**
     * Returns true if the process can execute the specified command with sudo privileges
     *
     * @param string $command
     * @param bool $exception
     * @return bool
     * @todo Find a better option than "--version" which may not be available for everything. What about shell commands like "true", or "which", etc?
     */
    public function sudoAvailable(string $command, bool $exception = false): bool
    {
        try {
            Process::new($command, $this->restrictions)
                ->setSudo(true)
                ->setCommand($command)
                ->addArgument('--version')
                ->executeReturnArray();

            return true;
        } catch (ProcessFailedException $e) {
            if ($exception) {
                throw $e;
            }

            return false;
        }
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