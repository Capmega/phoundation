<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\Directory;
use Phoundation\Os\Processes\Commands\Exception\CommandsException;
use Phoundation\Os\Processes\Exception\ProcessFailedException;


/**
 * Class Rm
 *
 * This class contains various easy-to-use and ready-to-go command line commands in static methods to manage Linux
 * filesystems
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class Rm extends Command
{
    /**
     * Deletes the specified file
     *
     * @param string $file       The file to delete
     * @param bool $recurse_down If set to true and the file is a directory, all files below will also be recursively
     *                           deleted
     * @param bool $recurse_up   If set to true, all directories above will be deleted as well IF they are empty after this
     *                           delete operation
     * @param int $timeout       The maximum number of time that may be spent on this delete operation
     * @return void
     */
    public function delete(string $file, bool $recurse_down = true, bool $recurse_up = false, int $timeout = 10): void
    {
        try {
            $this
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
                    $empty = Directory::new($file, $this->restrictions)->isEmpty();

                    if ($empty) {
                        static::delete($file, $recurse_down, false, 1);
                    }
                }
            }

        } catch (ProcessFailedException $e) {
            // The command rm failed, most of the time either $file doesn't exist, or we don't have access to change the mode
            static::handleException('rm', $e, function($first_line, $last_line, $e) use ($file) {
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
}
