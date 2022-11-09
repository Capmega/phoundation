<?php

namespace Phoundation\Processes;

use Phoundation\Core\Arrays;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
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
     * Returns a commands object for the specified server so that we can execute commands there
     *
     * @param Server|null $server
     * @return static
     */
    public static function server(?Server $server = null): static
    {
        return new static($server);
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
     * Returns the realpath for the specified command
     *
     * @param string $command The command for which the realpath must be known
     * @return string The real path for the specified command
     */
    public function which(string $command): string
    {
        $process = Process::new('which', $this->server)
            ->addArgument($command)
            ->setRegisterRunfile(false)
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
     * Returns the user, group
     *
     * @param string $section
     * @return int
     */
    public function id(string $section): int
    {
        if (($section != 'u') and ($section != 'g')) {
            throw new OutOfBoundsException(tr('Invalid section ":section" specified. This value can only be "u" or "g"', [':section' => $section]));
        }

        $process = Process::new('id', $this->server)
            ->addArgument('-' . $section)
            ->setTimeout(1);

        try {
            $output = $process->executeReturnArray();
            $result = reset($output);

            if (!$result or !is_numeric($result)) {
                // So which gave us a path that doesn't exist or that we can't access
                throw new CommandsException(tr('Failed to get id'));
            }

            return (int) $result;

        } catch (ProcessFailedException $e) {
            // The command id failed
            Commands::handleException('rm', $e);
        }
    }



    /**
     * Remove the specified patterns
     *
     * @param string $patterns
     * @param bool $recursive
     * @return void
     */
    public function rm(string $patterns, bool $recursive = false): void
    {
        if (!$patterns) {
            throw new OutOfBoundsException(tr('No patterns specified'));
        }

        $process = Process::new('rm', $this->server)
            ->addArgument($patterns)
            ->addArguments($recursive ? '-rf' : null)
            ->setTimeout(5);

        try {
            $output = $process->executeReturnArray();

            if ($output) {
                // rm only shows output in case of error
                throw new CommandsException(tr('rm command failed with ":output"', [':output' => $output]));
            }

        } catch (ProcessFailedException $e) {
            // The command rm failed
            Commands::handleException('rm', $e);
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
            throw new CommandsException(tr('The command :command failed with ":output"', [':command' => $command, ':output' => $data]));
        }

        // Something else went wrong, no CLI output available
        throw new CommandsException(tr('The command :command failed for unknown reasons', [':command' => $command]));
    }
}