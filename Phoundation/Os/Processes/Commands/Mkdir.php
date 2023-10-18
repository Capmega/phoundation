<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Config;
use Phoundation\Core\Strings;
use Phoundation\Os\Processes\Commands\Exception\CommandsException;
use Phoundation\Os\Processes\Exception\ProcessFailedException;


/**
 * Class Mkdir
 *
 * This class contains various "mkdir" commands
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Mkdir extends Command
{
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

            $this
                ->setInternalCommand('mkdir')
                ->addArguments([$file, '-p', '-m', $mode])
                ->setTimeout(1)
                ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command mkdir failed, most of the time either $file doesn't exist, or we don't have access to change the mode
            static::handleException('mkdir', $e, function($first_line, $last_line, $e) use ($file) {
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
}
