<?php

declare(strict_types=1);

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Config;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Path;
use Phoundation\Processes\Commands\Exception\CommandsException;
use Phoundation\Processes\Exception\ProcessFailedException;
use Phoundation\Processes\Process;


/**
 * Class FilesystemCommands
 *
 * This class contains various easy-to-use and ready-to-go command line commands in static methods to manage Linux
 * filesystems
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class FilesystemCommands extends Command
{
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

            $this->process
                ->setCommand('chmod')
                ->addArguments([$mode, $file, ($recurse ? '-R' : '')])
                ->setTimeout(2)
                ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command chmod failed, most of the time either $file doesn't exist, or we don't have access to change the mode
            Command::handleException('rm', $e, function($first_line, $last_line, $e) use ($file, $mode) {
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
            $this->process
                ->setCommand('rm')
                ->addArguments([$file, '-f', ($recurse_down ? '-r' : '')])
                ->setTimeout($timeout)
                ->setRegisterRunfile(false)
                ->executeReturnArray();

            if ($recurse_up) {
                // Delete upwards as well as long as the parent directories are empty!
                $empty = true;

                while ($empty) {
                    $file = dirname($file);
                    $empty = Path::new($file, $this->restrictions)->isEmpty();

                    if ($empty) {
                        static::delete($file, $recurse_down, false, 1);
                    }
                }
            }

        } catch (ProcessFailedException $e) {
            // The command rm failed, most of the time either $file doesn't exist, or we don't have access to change the mode
            Command::handleException('rm', $e, function($first_line, $last_line, $e) use ($file) {
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

            $this->process
                ->setCommand('mkdir')
                ->addArguments([$file, '-p', '-m', $mode])
                ->setTimeout(1)
                ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command mkdir failed, most of the time either $file doesn't exist, or we don't have access to change the mode
            Command::handleException('mkdir', $e, function($first_line, $last_line, $e) use ($file) {
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
     * Returns a SHA256 hash for the specified file
     *
     * @param string $file The file to get the sha256 hash from
     * @return string
     */
    public function sha256(string $file): string
    {
        try {
            $output = $this->process
                ->setCommand('sha256sum')
                ->addArguments($file)
                ->setTimeout(120)
                ->executeReturnString();

            return Strings::until($output, ' ');

        } catch (ProcessFailedException $e) {
            // The command sha256sum failed, most of the time either $file doesn't exist, or we don't have access
            Command::handleException('sha256sum', $e, function() use ($file) {
                File::new($file)->checkReadable();
            });
        }
    }


    /**
     * Untars the specified file
     *
     * @param string $file The file to be untarred. Must be a tar file (doh)
     * @return string
     */
    public function untar(string $file, ?string $target_path = null): string
    {
        try {
            if (!$target_path) {
                $target_path = dirname($file);
            }

            $this->process
                ->setExecutionPath($target_path)
                ->setCommand('tar')
                ->addArguments(['-x', '-f'])
                ->addArguments($file)
                ->setTimeout(120)
                ->executeNoReturn();

            return $target_path;

        } catch (ProcessFailedException $e) {
            // The command tar failed, most of the time either $file doesn't exist, or we don't have access
            Command::handleException('tar', $e, function() use ($file) {
                File::new($file)->checkReadable();
            });
        }
    }


    /**
     * Tars the specified path
     *
     * @param string $path
     * @param string|null $target_file
     * @param bool $compression
     * @return string
     */
    public function tar(string $path, ?string $target_file = null, bool $compression = true): string
    {
        try {
            if (!$target_file) {
                $target_file = $path . '.tar.gz';
            }

            $this->process
                ->setExecutionPath(dirname($path))
                ->setCommand('tar')
                ->addArguments(['-c', ($compression ? 'j' : null), '-f'])
                ->addArguments($target_file)
                ->addArguments($path)
                ->setTimeout(120)
                ->executeNoReturn();

            return $target_file;

        } catch (ProcessFailedException $e) {
            // The command tar failed, most of the time either $file doesn't exist, or we don't have access
            Command::handleException('tar', $e, function() use ($path) {
                File::new($path)->checkReadable();
            });
        }
    }


    /**
     * Gzips the specified file
     *
     * @param string $file The file to be gzipped.
     * @return string
     */
    public function gzip(string $file): string
    {
        try {
            if (!str_ends_with($this->file, '.gz')) {
                if (!str_ends_with($this->file, '.tgz')) {
                    throw new OutOfBoundsException(tr('Cannot gunzip file ":file", the filename must end with ".gz"', [
                        ':file' => $this->file
                    ]));
                }
            }

            $this->process
                ->setCommand('gzip')
                ->addArguments($file)
                ->setTimeout(120)
                ->executeNoReturn();

            return $file . '.gz';

        } catch (ProcessFailedException $e) {
            // The gzip tar failed, most of the time either $file doesn't exist, or we don't have access
            Command::handleException('gzip', $e, function() use ($file) {
                File::new($file)->checkReadable();
            });
        }
    }


    /**
     * Gunzips the specified file
     *
     * @param string $file The file to be gunzipped.
     * @return string
     */
    public function gunzip(string $file): string
    {
        try {
            $this->process
                ->setCommand('gunzip')
                ->addArguments($file)
                ->setTimeout(120)
                ->executeNoReturn();

            return Strings::until(Strings::until($file, '.tgz'), '.gz');

        } catch (ProcessFailedException $e) {
            // The command gunzip failed, most of the time either $file doesn't exist, or we don't have access
            Command::handleException('gunzip', $e, function() use ($file) {
                File::new($file)->checkReadable();
            });
        }
    }


    /**
     * Gunzips the specified file
     *
     * @param string $file The file to be unzipped.
     * @param string|null $target_path
     * @return void
     */
    public function unzip(string $file, ?string $target_path = null): void
    {
        try {
            if (!$target_path) {
                $target_path = dirname($file);
            }

            $this->process
                ->setExecutionPath($target_path)
                ->setCommand('unzip')
                ->addArguments($file)
                ->setTimeout(120)
                ->executeNoReturn();

        } catch (ProcessFailedException $e) {
            // The command gunzip failed, most of the time either $file doesn't exist, or we don't have access
            Command::handleException('unzip', $e, function() use ($file) {
                File::new($file)->checkReadable();
            });
        }
    }
}