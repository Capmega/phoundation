<?php

/**
 * Class Mkdir
 *
 * This class contains various "mkdir" commands
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Os\Processes\Commands\Exception\CommandsException;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Utils\Strings;


class Mkdir extends Command
{
    /**
     * Creates the specified directory
     *
     * @param string          $file The directory to create
     * @param string|int|null $mode
     *
     * @return void
     */
    public function mkdir(string $file, string|int|null $mode = null): void
    {
        try {
            $mode = config()->get('filesystem.mode.default.directory', $mode ?? 0750);
            $mode = Strings::fromOctal($mode);
            $this->setCommand('mkdir')
                 ->addArguments([$file, '-p', '-m', $mode])
                 ->setTimeout(1)
                 ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command mkdir failed, most of the time either $file does not exist, or we do not have access to change the mode
            static::handleException('mkdir', $e, function ($e, $first_line, $last_line) use ($file) {
                if ($e->getCode() == 1) {
                    if (str_contains($first_line, 'not a directory')) {
                        $directory = Strings::from($first_line, 'directory \'');
                        $directory = Strings::until($directory, '\':');

                        throw new CommandsException(tr('Failed to create directory file ":file" because the section ":directory" already exists and is not a directory', [
                            ':file'      => $file,
                            ':directory' => $directory,
                        ]));
                    }

                    if (str_contains($first_line, 'permission denied')) {
                        $directory = Strings::from($first_line, 'directory \'');
                        $directory = Strings::until($directory, '\':');

                        throw new CommandsException(tr('Failed to create directory file ":file", permission denied to create section ":directory" ', [
                            ':file'      => $file,
                            ':directory' => $directory,
                        ]));
                    }
                }
            });
        }
    }
}
