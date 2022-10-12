<?php

namespace Phoundation\Processes;

use Phoundation\Core\Arrays;
use Phoundation\Processes\Exception\CommandsException;
use Phoundation\Processes\Exception\ProcessFailedException;
use Phoundation\Servers\Server;
use Throwable;



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
class Commands
{
    /**
     * Where will this be executed? Locally or on the specified server
     *
     * @var Server|null $server
     */
    protected ?Server $server = null;



    /**
     * @param Server|null $server
     */
    public function __construct(?Server $server = null)
    {
        $this->server = $server;
    }



    /**
     * Returns the realpath for the specified command
     *
     * @param string $command The command for which the realpath must be known
     * @return string The real path for the specified command
     */
    public function which(string $command): string
    {
        $process = Processes::create('which', $this->server)
            ->addArgument($command)
            ->setTimeout(1);

        try {
            $output = $process->executeReturnArray();
            $result = reset($output);
            $realpath = realpath($result);

            if (!$realpath) {
                // So which gave us a path that doesn't exist or that we can't access
                throw new CommandsException(tr('Failed to get realpath for which result ":result" for command  ":command"', [':command' => $command, ':result' => $result]));
            }

            return $realpath;

        } catch (ProcessFailedException $e) {
            // The command which failed, likely it could not find the requested command
            Commands::handleException('rm', $e, function($first_line, $last_line, $e) use ($command) {
                if ($e->getCode() == 1) {
                    if (!$e->getData()['output']) {
                        throw new CommandsException(tr('The which could not find the specified command ":command"', [':command' => $command]));
                    }
                }
            });
        }
    }



    /**
     * Returns a commands object for the specified server so that we can execute commands there
     *
     * @param Server|null $server
     * @return self
     */
    public static function server(?Server $server = null): self
    {
        return new self($server);
    }



    /**
     * Returns a commands object for this local machine
     *
     * @return self
     */
    public static function local(): self
    {
        return new static();
    }



    /**
     * Command exception handler
     *
     * @param string $command
     * @param Throwable $e
     * @param callable $function
     * @return void
     */
    protected static function handleException(string $command, Throwable $e, callable $function): void
    {
        if ($e->getData()['output']) {
            $data       = $e->getData()['output'];
            $first_line = Arrays::firstValue($data);
            $first_line = strtolower($first_line);
            $last_line  = Arrays::lastValue($data);
            $last_line  = strtolower($last_line);

            // Process specified handlers
            $function($first_line, $last_line, $e);

            // Handlers were unable to make a clear exception out of this, show the standard command exception
            throw new CommandsException(tr('The command :command failed with ":output"', [':command' => $command, ':output' => $data]));
        }

        // Something else went wrong, no CLI output available
        throw new CommandsException(tr('The command :command failed for unknown reasons', [':command' => $command]));
    }
}