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
    public function __construct(?Server $server)
    {

    }



    /**
     * Returns the realpath for the specified command
     *
     * @param string $command The command for which the realpath must be known
     * @return string The real path for the specified command
     */
    public static function which(string $command): string
    {
        $process = Processes::create('which')
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
     * Returns the realpath for the specified command
     *
     * @param string $file
     * @param string $mode
     * @param bool $recurse
     * @return void
     */
    public static function chmod(string $file, string $mode, bool $recurse = false): void
    {
        try {
            if (is_numeric($mode)) {
                $mode = sprintf('0%o', $mode);
            }

            Processes::create('chmod', true)
                ->addArguments([$mode, $file, $recurse ?? '-R'])
                ->setTimeout(2)
                ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command chmod failed, most of the time either $file doesn't exist, or we don't have access to change the mode
            Commands::handleException('rm', $e, function($first_line, $last_line, $e) use ($file, $mode) {
                if ($e->getCode() == 1) {
                    if (str_contains($last_line, 'no such file or directory')) {
                        throw new CommandsException(tr('Failed to chmod file ":file" to ":mode" because it does not exist', [':file' => $file, ':mode' => $mode]));
                    }

                    if (str_contains($last_line, 'operation not permitted')) {
                        throw new CommandsException(tr('Failed to chmod file ":file" to ":mode" because the operation was not permitted', [':file' => $file, ':mode' => $mode]));
                    }
                }
            });
        }
    }



    /**
     * Deletes the specified file
     *
     * @param string $file The file to delete
     * @param bool $recurse If set to true and the file is a directory, all files below will also be recursively deleted
     * @param bool $cleanup If set to true, all directories above will be deleted as well IF they are empty after this
     *                      delete operation
     * @return void
     */
    public static function delete(string $file, bool $recurse = true, bool $cleanup = false): void
    {
        try {
            Processes::create('rm', true)
                ->addArguments([$file, '-f', $recurse ?? '-r'])
                ->setTimeout(10)
                ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command rm failed, most of the time either $file doesn't exist, or we don't have access to change the mode
            Commands::handleException('rm', $e, function($first_line, $last_line, $e) use ($file) {
                if ($e->getCode() == 1) {
                    if (str_contains($last_line, 'no such file or directory')) {
                        // The specified file does not exist, that is okay, we wanted it gone anyway
                        return;
                    }

                    if (str_contains($last_line, 'is a directory')) {
                        throw new CommandsException(tr('Failed to delete file ":file" to ":mode" because it is a directory and $recursive was not specified', [':file' => $file]));
                    }
                }
            });
        }
    }



    /**
     * Creates the specified directory
     *
     * @param string $file The directory to create
     * @return void
     */
    public static function mkdir(string $file): void
    {
        try {
            Processes::create('mkdir', true)
                ->addArguments([$file, '-p'])
                ->setTimeout(1)
                ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command mkdir failed, most of the time either $file doesn't exist, or we don't have access to change the mode
            Commands::handleException('mkdir', $e, function($first_line, $last_line, $e) use ($file) {
                if ($e->getCode() == 1) {
                    if (str_contains($first_line, 'no such file or directory')) {
                        // The specified file does not exist, that is okay, we wanted it gone anyway
                        return;
                    }

                    if (str_contains($first_line, 'is a directory')) {
                        throw new CommandsException(tr('Failed to delete file ":file" to ":mode" because it is a directory and $recursive was not specified', [':file' => $file]));
                    }
                }
            });
        }
    }



    /**
     * Returns a commands object for the specified server so that we can execute commands there
     *
     * @param string|null $name
     * @return Commands
     */
    public static function server(?string $name = null): Commands
    {
        $server = null;

        if ($name) {
            $server = new Server($name);
        }

        return new Commands($server);
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