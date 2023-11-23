<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\AlredyCompressedException;
use Phoundation\Filesystem\Exception\FileExistsException;
use Phoundation\Filesystem\Exception\InvalidFileType;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Interfaces\FileInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Utils\Strings;


/**
 * Class Gzip
 *
 * This class contains various "gzip" commands
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class Gzip extends Command
{
    /**
     * Gzips the specified file
     *
     * @param FileInterface|string $file The file to be gzipped.
     * @return string
     * @throws \Throwable
     */
    public function gzip(FileInterface|string $file): string
    {
        try {
            $file = File::new($file);

            if ($file->isCompressed()) {
                throw new AlredyCompressedException(tr('Cannot gzip file ":file", it is already compressed', [
                    ':file' => $file->getPath()
                ]));
            }

            if (file_exists($file . '.gz')) {
                throw new FileExistsException(tr('Cannot gzip file ":file", the gzipped version ":gzip" already exists', [
                    ':file' => $file->getPath(),
                    ':gzip' => $file->getPath() . '.gz'
                ]));
            }

            $this->setInternalCommand('gzip')
                 ->addArgument($file)
                 ->setTimeout(120)
                 ->executeNoReturn();

            return $file . '.gz';

        } catch (ProcessFailedException $e) {
            // The gzip tar failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('gzip', $e, function() use ($file) {
                File::new($file)->checkReadable();
            });
        }
    }


    /**
     * Gunzips the specified file
     *
     * @param FileInterface|string $file The file to be gunzipped.
     * @return FileInterface
     */
    public function gunzip(FileInterface|string $file): FileInterface
    {
        try {
            $file   = File::new($file);
            $target = Strings::until(Strings::until((string) $file, '.tgz'), '.gz');
            $target = File::new($target);

            if ($file->getMimetype() !== 'application/gzip') {
                throw new InvalidFileType(tr('Cannot gunzip file ":file", it is not a gzip file', [
                    ':file' => $file->getPath()
                ]));
            }

            if ($target->exists()) {
                throw new FileExistsException(tr('Cannot gunzip file ":file", the target version ":gzip" already exists', [
                    ':file' => $file->getPath(),
                    ':gzip' => $target
                ]));
            }

            $this->setInternalCommand('gunzip')
                 ->addArguments($file)
                 ->setTimeout(120)
                 ->executeNoReturn();

            return $target;

        } catch (ProcessFailedException $e) {
            // The command gunzip failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('gunzip', $e, function() use ($file) {
                File::new($file)->checkReadable();
            });
        }
    }
}
