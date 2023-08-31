<?php

declare(strict_types=1);

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Strings;
use Phoundation\Filesystem\File;
use Phoundation\Processes\Exception\ProcessFailedException;


/**
 * Class FilesystemCommands
 *
 * This class contains various "sha256" commands
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Sha256 extends Command
{
    /**
     * Returns a SHA256 hash for the specified file
     *
     * @param string $file The file to get the sha256 hash from
     * @return string
     */
    public function sha256(string $file): string
    {
        try {
            $output = $this
                ->setInternalCommand('sha256sum')
                ->addArguments($file)
                ->setTimeout(120)
                ->executeReturnString();

            return Strings::until($output, ' ');

        } catch (ProcessFailedException $e) {
            // The command sha256sum failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('sha256sum', $e, function() use ($file) {
                File::new($file)->checkReadable();
            });
        }
    }
}
