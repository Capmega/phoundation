<?php

namespace Phoundation\Cli;

use Phoundation\Cli\Interfaces\RunFileInterface;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Commands\Ps;


/**
 * Class RunFile
 *
 * This class manages run files for Phoundation command processes
 *
 * Phoundation command processes are all commands that are available in the ROOT/system/commands/ directory
 *
 * Run files are stored as ROOT/system/run/PROCESS/PROCESSID
 *
 * If PROCESS is "accounts/users/create" with PID 6345 then the run file is ROOT/system/run/accounts/users/create/6345
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Cli
 */
class RunFile implements RunFileInterface
{
    /**
     * The file access restrictions
     *
     * @var Restrictions $restrictions
     */
    protected static Restrictions $restrictions;

    /**
     * The command for which we are creating a run file
     *
     * @var string $command
     */
    protected string $command;

    /**
     * The path where all runfiles are located
     *
     * @var string $path
     */
    protected static string $path = PATH_ROOT . 'data/run/';

    /**
     * The exact run file for this command
     *
     * @var string $file
     */
    protected string $file;

    /**
     * The pid for which we are creating a runfile
     *
     * @var string $pid
     */
    protected string $pid;


    /**
     * RunFile class constructor
     *
     * @param string $command
     */
    public function __construct(string $command)
    {
        static::$restrictions = Restrictions::new(static::$path, true, 'runfile');

        $this->setCommand(Strings::from($command, PATH_ROOT . 'scripts/'));
        $this->setPid(getmypid());
        $this->create();
    }


    /**
     * RunFile class constructor
     *
     * @param string $command
     * @return static
     */
    public static function new(string $command): static
    {
        return new static($command);
    }


    /**
     * Sets the command
     *
     * @param string $command
     * @return static
     */
    protected function setCommand(string $command): static
    {
        if (!$command) {
            throw new OutOfBoundsException(tr('No command specified'));
        }

        $this->command = $command;
        return $this;
    }


    /**
     * Returns the command for this runfile
     *
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }


    /**
     * Sets the pid
     *
     * @param int $pid
     * @return static
     */
    protected function setPid(int $pid): static
    {
        if ($pid < 0) {
            throw new OutOfBoundsException(tr('Invalid process id ":pid" specified', [
                ':pid' => $pid
            ]));
        }

        $this->pid = $pid;
        return $this;
    }


    /**
     * Returns the pid for this runfile
     *
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }


    /**
     * Returns the path where all run files are located
     *
     * @return string
     */
    public function getPath(): string
    {
        return static::$path;
    }


    /**
     * Returns the run file for this process
     *
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }


    /**
     * Creates the run file
     *
     * @return static
     */
    protected function create(): static
    {
        Path::new(static::$path . $this->command . '/', static::$restrictions)->ensure();

        $this->file = static::$path . $this->command . '/' . $this->pid;

        touch($this->file);
        return $this;
    }


    /**
     * Returns true if this run file object still has a run file available
     *
     * @return bool
     */
    public function exists(): bool
    {
        return file_exists($this->file);
    }


    /**
     * Returns true if the specified command has an active run file
     *
     * @param string $command
     * @return bool
     */
    public static function commandExists(string $command): bool
    {
        return (bool) static::getPidForCommand($command);
    }


    /**
     * Returns the first found PID for the specified command, if it currently runs. NULL otherwise
     *
     * @return int|null
     */
    public function getPidForCommand(): ?int
    {
        $path = static::findCommandPath($this->command);

        if (!$path) {
            // The command currently isn't running
            return null;
        }

        try {
            // Yay, a directory for this command exists! Return the first run file (PID file) we can find.
            return (int) Path::new($path)->getSingleFile('/\d+/');

        } catch (FilesystemException) {
            // No run file found
            return null;
        }
    }


    /**
     * Returns an array with all PIDs and mtimes for the specified command, if it currently runs.
     *
     * Array format: [pid > mtime, pid => mtime, ...]
     * @return array
     */
    public function getPidsForCommand(): array
    {
        $path = static::findCommandPath($this->command);

        if (!$path) {
            // The command currently isn't running
            return [];
        }

        // Yay, a directory for this command exists! Return all the run files (PID files) we can find.
        $pids   = Path::new($path)->scanRegex('/\d+/');
        $return = [];

        // Build PID > MTIME array
        foreach ($pids as $pid) {
            $return[$pid] = stat($pid)['mtime'];
        }

        return $return;
    }


    /**
     * Return the amount of this command being run
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->getPidsForCommand());
    }


    /**
     * Will validate all existing run files and delete all those run files that are stale
     *
     * @return void
     */
    public static function purge(): void
    {
        // Purge orphaned run files
        Path::new(static::$path)
            ->execute()
            ->setRecurse(true)
            ->onFiles(function(string $file) {
                if (Strings::fromReverse($file, '/') === 'pids') {
                    // This is the pids path, ignore it.
                    return;
                }

                // Extract command and PID from the file
                $pid     = Strings::fromReverse($file, '/');
                $command = Strings::until($file, '/' . $pid);
                $command = Strings::fromReverse($command, '/');

                if (!static::validateRunFile($pid, $file)) {
                    // This run file was messed up
                    return;
                }

                // Ensure that this PID exist, and that it's the correct process
                $process = Ps::new()->ps($pid);
                $cmd     = Strings::from($process['cmd'], '/pho ');

                show($process['cmd']);
                showdie($cmd);

                if ($cmd !== $command) {
                    // The PID exists, but its a different command. Remove the runfile and all PID files
                    File::new($runfile, static::$restrictions)->delete(PATH_DATA . 'run/');
                    File::new(static::$path . 'pids/' . $pid, static::$restrictions)->delete(PATH_DATA . 'run/pids/');
                }
        });

        // Purge orphaned PID files
        Path::new(static::$path)
            ->execute()
            ->setRecurse(true)
            ->onPathOnly(function(string $path) {
// TODO
            });
    }


    /**
     * Delete the run file and clean up the run path
     *
     * @return static
     */
    public function delete(): static
    {
        // Delete the runfile and delete all possible PID files associated with this PID
        // Don't use runfiles here because we're deleting the runfile paths...
        File::new(PATH_DATA . 'run/' . $this->command . '/' . $this->pid, static::$restrictions)->delete(PATH_DATA . 'run/', use_run_file: false);
        Path::new(PATH_DATA . 'run/pids/' . $this->pid, static::$restrictions)->delete(PATH_DATA . 'run/', use_run_file: false);
        return $this;
    }


    /**
     * Validates the run file and returns true if all is well, false if not
     *
     * @param $pid
     * @param $file
     * @return bool
     */
    protected static function validateRunFile($pid, $file): bool
    {
        if (is_really_natural($pid)) {
            return true;
        }

        // Wut? Get rid of this, next!
        Log::warning(tr('Encountered invalid PID file ":pid", removing the file', [
            ':pid' => $pid
        ]));

        File::new($file, static::$restrictions)->delete(PATH_DATA . 'run/');
        return false;
    }


    /**
     * Returns the run path for the command, if it exists, NULL otherwise
     *
     * @param string $command
     * @return string|null
     */
    protected function findCommandPath(string $command): ?string
    {
        $path     = static::$path;
        $sections = explode('/', $command);

        // Search the command as a hierarchical tree.
        foreach ($sections as $section) {
            $path .= $section;

            if (!file_exists($path)) {
                // Run file does not exist
                return null;
            }
        }

        return $path;
    }
}
