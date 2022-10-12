<?php

namespace Phoundation\Processes;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Filesystem\Path;
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
     * Returns the realpath for the specified command
     *
     * @param string $file
     * @param int|string $mode
     * @param bool $recurse
     * @return void
     */
    public function chmod(string $file, int|string $mode, bool $recurse = false): void
    {
        try {
            $mode = Strings::fromOctal($mode);

            Processes::create('chmod', $this->server, true)
                ->addArguments([$mode, $file, ($recurse ? '-R' : '')])
                ->setTimeout(2)
                ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command chmod failed, most of the time either $file doesn't exist, or we don't have access to change the mode
            Commands::handleException('rm', $e, function($first_line, $last_line, $e) use ($file, $mode) {
                if ($e->getCode() == 1) {
                    if (str_contains($last_line, 'no such file or directory')) {
                        throw new CommandsException(tr('Failed to chmod file ":file" to ":mode", it does not exist', [':file' => $file, ':mode' => $mode]));
                    }

                    if (str_contains($last_line, 'operation not permitted')) {
                        throw new CommandsException(tr('Failed to chmod file ":file" to ":mode", permission denied', [':file' => $file, ':mode' => $mode]));
                    }
                }
            });
        }
    }



    /**
     * Deletes the specified file
     *
     * @param string $file       The file to delete
     * @param bool $recurse_down If set to true and the file is a directory, all files below will also be recursively
     *                           deleted
     * @param bool $recurse_up   If set to true, all directories above will be deleted as well IF they are empty after this
     *                           delete operation
     * @param int $timeout       The maximum amount of time that may be spent on this delete operation
     * @return void
     */
    public function delete(string $file, bool $recurse_down = true, bool $recurse_up = false, int $timeout = 10): void
    {
        try {
            Processes::create('rm', $this->server, true)
                ->addArguments([$file, '-f', ($recurse_down ? '-r' : '')])
                ->setTimeout($timeout)
                ->executeReturnArray();

            if ($recurse_up) {
                // Delete upwards as well as long as the parent directories are empty!
                $empty = true;

                while ($empty) {
                    $file = dirname($file);
                    $empty = Path::isEmpty($file);

                    if ($empty) {
                        Commands::delete($file, $recurse_down, false, 1);
                    }
                }
            }

        } catch (ProcessFailedException $e) {
            // The command rm failed, most of the time either $file doesn't exist, or we don't have access to change the mode
            Commands::handleException('rm', $e, function($first_line, $last_line, $e) use ($file) {
                if ($e->getCode() == 1) {
                    if (str_contains($last_line, 'no such file or directory')) {
                        // The specified file does not exist, that is okay, we wanted it gone anyway
                        return;
                    }

                    if (str_contains($last_line, 'is a directory')) {
                        throw new CommandsException(tr('Failed to delete file ":file" to ":mode", it is a directory and $recurse_down was not specified', [':file' => $file]));
                    }
                }
            });
        }
    }



    /**
     * Creates the specified directory
     *
     * @param string $file The directory to create
     * @param string|int|null $mode
     * @return void
     */
    public function mkdir(string $file, string|int|null $mode = null): void
    {
        try {
            $mode = Config::get('filesystem.mode.default.directory', 0750, $mode);
            $mode = Strings::fromOctal($mode);

            Processes::create('mkdir', $this->server, true)
                ->addArguments([$file, '-p', '-m', $mode])
                ->setTimeout(1)
                ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command mkdir failed, most of the time either $file doesn't exist, or we don't have access to change the mode
            Commands::handleException('mkdir', $e, function($first_line, $last_line, $e) use ($file) {
                if ($e->getCode() == 1) {
                    if (str_contains($first_line, 'not a directory')) {
                        $path = Strings::from($first_line, 'directory \'');
                        $path = Strings::until($path, '\':');
                        throw new CommandsException(tr('Failed to create directory file ":file" because the section ":path" already exists and is not a directory', [':file' => $file, ':path' => $path]));
                    }

                    if (str_contains($first_line, 'permission denied')) {
                        $path = Strings::from($first_line, 'directory \'');
                        $path = Strings::until($path, '\':');
                        throw new CommandsException(tr('Failed to create directory file ":file", permission denied to create section ":path" ', [':file' => $file, ':path' => $path]));
                    }

                }
            });
        }
    }



    /**
     * Returns a commands object for the specified server so that we can execute commands there
     *
     * @param Server|null $server
     * @return Commands
     */
    public static function server(?Server $server = null): Commands
    {
        return new Commands($server);
    }



    /**
     * Returns a commands object for this local machine
     *
     * @return Commands
     */
    public static function local(): Commands
    {
        return new Commands();
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