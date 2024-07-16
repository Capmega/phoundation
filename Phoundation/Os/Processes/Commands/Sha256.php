<?php

/**
 * Class FilesystemCommands
 *
 * This class contains various "sha256" commands
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\FsFile;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Utils\Strings;

class Sha256 extends Command
{
    /**
     * Returns a SHA256 hash for the specified file
     *
     * @param string $file The file to get the sha256 hash from
     *
     * @return string
     */
    public function sha256(string $file): string
    {
        try {
            $output = $this->setCommand('sha256sum')
                           ->addArguments($file)
                           ->setTimeout(120)
                           ->executeReturnString();

            return Strings::until($output, ' ');

        } catch (ProcessFailedException $e) {
            // The command sha256sum failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('sha256sum', $e, function () use ($file) {
                FsFile::new($file, $this->restrictions)->checkReadable();
            });
        }
    }
}
