<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;

/**
 * Class zip
 *
 * This class contains various "zip" commands
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
class Zip extends Command
{
    /**
     * Unzips the specified file
     *
     * @param FsFileInterface           $file The file to be unzipped.
     * @param FsDirectoryInterface|null $target_path
     *
     * @return void
     */
    public function unzip(FsFileInterface $file, ?FsDirectoryInterface $target_path = null): void
    {
        try {
            if (!$target_path) {
                $target_path = $file->getParentDirectory();
            }

            $this->setExecutionDirectory($target_path)
                 ->setCommand('unzip')
                 ->addArguments($file)
                 ->setTimeout(120)
                 ->executeNoReturn();

        } catch (ProcessFailedException $e) {
            // The command gunzip failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('unzip', $e, function () use ($file) {
                FsFile::new($file)
                    ->checkReadable();
            });
        }
    }
}
