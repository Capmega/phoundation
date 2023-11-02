<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\File;
use Phoundation\Os\Processes\Exception\ProcessFailedException;


/**
 * Class zip
 *
 * This class contains various "zip" commands
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class Zip extends Command
{
    /**
     * Unzips the specified file
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

            $this->setExecutionDirectory($target_path)
                 ->setInternalCommand('unzip')
                 ->addArguments($file)
                 ->setTimeout(120)
                 ->executeNoReturn();

        } catch (ProcessFailedException $e) {
            // The command gunzip failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('unzip', $e, function() use ($file) {
                File::new($file)->checkReadable();
            });
        }
    }
}
