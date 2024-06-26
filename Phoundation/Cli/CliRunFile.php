<?php

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
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cli
 */

declare(strict_types=1);

namespace Phoundation\Cli;

use Phoundation\Cli\Interfaces\CliRunFileInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsFileCore;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Os\Processes\Commands\Ps;
use Phoundation\Utils\Strings;

class CliRunFile extends FsFileCore implements CliRunFileInterface
{
    /**
     * The directory where all run files are located
     *
     * @var FsDirectoryInterface $directory
     */
    protected static FsDirectoryInterface $directory;

    /**
     * The command for which we are creating a run file
     *
     * @var string $command
     */
    protected string $command;

    /**
     * The exact run file for this command
     *
     * @var FsFileInterface $file
     */
    protected FsFileInterface $file;

    /**
     * The pid for which we are creating a runfile
     *
     * @var int $pid
     */
    protected int $pid;


    /**
     * RunFile class constructor
     *
     * @param string $command
     */
    public function __construct(string $command)
    {
        $this->restrictions = FsRestrictions::new(DIRECTORY_ROOT . 'data/run/', true, 'CliRunFile::__construct()');

        static::$directory = FsDirectory::new(
            DIRECTORY_ROOT . 'data/run/',
            $this->restrictions
        );

        $this->setCommand(Strings::from($command, DIRECTORY_COMMANDS))
             ->setPid(getmypid())
             ->create();
    }


    /**
     * RunFile class constructor
     *
     * @param string $command
     *
     * @return static
     */
    public static function new(string $command): static
    {
        return new static($command);
    }


    /**
     * Creates the run file
     *
     * @param bool $force
     *
     * @return static
     */
    public function create(bool $force = false): static
    {
        $this->path = static::$directory->addFile($this->command . '/' . $this->pid)->getPath();
        return parent::create();
    }


    /**
     * Returns true if the specified command has an active run file
     *
     * @param string $command
     *
     * @return bool
     */
    public function commandExists(string $command): bool
    {
        return (bool) $this->getPidForCommand($command);
    }


    /**
     * Returns the first found PID for the specified command, if it currently runs. NULL otherwise
     *
     * @return int|null
     */
    public function getPidForCommand(): ?int
    {
        $directory = static::findCommandDirectory($this->command);

        if (!$directory) {
            // The command currently isn't running
            return null;
        }

        try {
            // Yay, a directory for this command exists! Return the first run file (PID file) we can find.
            return (int) FsDirectory::new($directory)->getSingleFile('/\d+/');

        } catch (FilesystemException) {
            // No run file found
            return null;
        }
    }


    /**
     * Returns the run directory for the command, if it exists, NULL otherwise
     *
     * @param string $command
     *
     * @return string|null
     */
    protected function findCommandDirectory(string $command): ?string
    {
        $directory = static::$directory;
        $sections  = explode('/', $command);

        // Search the command as a hierarchical tree.
        foreach ($sections as $section) {
            $directory .= $section;

            if (!file_exists($directory)) {
                // Run file does not exist
                return null;
            }
        }

        return $directory;
    }


    /**
     * Will validate all existing run files and delete all those run files that are stale
     *
     * @return void
     */
    public static function purge(): void
    {
        // Purge orphaned run files
        FsDirectory::new(static::$directory)
                   ->execute()
                   ->setRecurse(true)
                   ->onFiles(function (string $file) {
                        if (Strings::fromReverse($file, '/') === 'pids') {
                           // This is the pids directory, ignore it.
                           return;
                        }

                        // Extract command and PID from the file
                        $pid     = Strings::fromReverse($file, '/');
                        $command = Strings::until($file, '/' . $pid);
                        $command = Strings::fromReverse($command, '/');
                        $pid     = (int) $pid;

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
                            FsFile::new($runfile, static::$directory->getRestrictions())
                                  ->delete(DIRECTORY_DATA . 'run/');
                            FsFile::new(static::$directory . 'pids/' . $pid, static::$directory->getRestrictions())
                                  ->delete(DIRECTORY_DATA . 'run/pids/');
                        }
                   });

        // Purge orphaned PID files
        FsDirectory::new(static::$directory)
                 ->execute()
                 ->setRecurse(true)
                 ->onDirectoryOnly(function (string $directory) {
// TODO
                 });
    }


    /**
     * Validates the run file and returns true if all is well, false if not
     *
     * @param int $pid
     * @param string $file
     *
     * @return bool
     */
    protected static function validateRunFile(int $pid, string $file): bool
    {
        if (is_really_natural($pid)) {
            return true;
        }

        // Wut? Get rid of this, next!
        Log::warning(tr('Encountered invalid PID file ":pid", removing the file', [
            ':pid' => $pid,
        ]));

        FsFile::new($file, static::$directory->getRestrictions())->delete(DIRECTORY_DATA . 'run/');

        return false;
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
     * Sets the command
     *
     * @param string $command
     *
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
     * Returns the pid for this runfile
     *
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }


    /**
     * Sets the pid
     *
     * @param int $pid
     *
     * @return static
     */
    protected function setPid(int $pid): static
    {
        if ($pid < 0) {
            throw new OutOfBoundsException(tr('Invalid process id ":pid" specified', [
                ':pid' => $pid,
            ]));
        }

        $this->pid = $pid;

        return $this;
    }


    /**
     * Returns the directory where all run files are located
     *
     * @return FsDirectoryInterface
     */
    public function getDirectory(): FsDirectoryInterface
    {
        return static::$directory;
    }


    /**
     * Returns the run file for this process
     *
     * @return FsFileInterface
     */
    public function getFile(): FsFileInterface
    {
        return $this->file;
    }


    /**
     * Return the number of this command being run
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->getPidsForCommand());
    }


    /**
     * Returns an array with all PIDs and mtimes for the specified command, if it currently runs.
     *
     * Array format: [pid > mtime, pid => mtime, ...]
     *
     * @return array
     */
    public function getPidsForCommand(): array
    {
        $directory = static::findCommandDirectory($this->command);

        if (!$directory) {
            // The command currently isn't running
            return [];
        }

        // Yay, a directory for this command exists! Return all the run files (PID files) we can find.
        $pids   = FsDirectory::new($directory)->scanRegex('/\d+/');
        $return = [];

        // Build PID > MTIME array
        foreach ($pids as $pid) {
            $return[$pid] = stat((String) $pid)['mtime'];
        }

        return $return;
    }
}
