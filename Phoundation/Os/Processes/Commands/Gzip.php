<?php

/**
 * Class Gzip
 *
 * This class contains various "gzip" commands
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\Exception\AlredyCompressedException;
use Phoundation\Filesystem\Exception\FileExistsException;
use Phoundation\Filesystem\Exception\InvalidFileType;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Utils\Strings;


class Gzip extends Command
{
    /**
     * Gzips the specified file
     *
     * @param FsFileInterface $file The file to be gzipped.
     *
     * @return FsFileInterface
     */
    public function gzip(FsFileInterface $file): FsFileInterface
    {
        try {
            if ($file->isCompressed()) {
                throw new AlredyCompressedException(tr('Cannot gzip file ":file", it is already compressed', [
                    ':file' => $file->getSource(),
                ]));
            }

            if (file_exists($file . '.gz')) {
                throw new FileExistsException(tr('Cannot gzip file ":file", the gzipped version ":gzip" already exists', [
                    ':file' => $file->getSource(),
                    ':gzip' => $file->getSource() . '.gz',
                ]));
            }

            $this->setCommand('gzip')
                 ->addArgument($file)
                 ->setTimeout(120)
                 ->executeNoReturn();

            return new FsFile($file . '.gz');

        } catch (ProcessFailedException $e) {
            // The gzip tar failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('gzip', $e, function () use ($file) {
                FsFile::new($file, $this->restrictions)->checkReadable();
            });
        }
    }


    /**
     * Gunzips the specified file
     *
     * @param FsFileInterface $file The file to be gunzipped.
     *
     * @return FsFileInterface
     */
    public function gunzip(FsFileInterface $file): FsFileInterface
    {
        try {
            $target = Strings::until(Strings::until((string) $file, '.tgz'), '.gz');
            $target = FsFile::new($target);

            if ($file->getMimetype() !== 'application/gzip') {
                throw new InvalidFileType(tr('Cannot gunzip file ":file", it is not a gzip file', [
                    ':file' => $file->getSource(),
                ]));
            }

            if ($target->exists()) {
                throw new FileExistsException(tr('Cannot gunzip file ":file", the target version ":gzip" already exists', [
                    ':file' => $file->getSource(),
                    ':gzip' => $target,
                ]));
            }

            $this->setCommand('gunzip')
                 ->addArguments($file)
                 ->setTimeout(120)
                 ->executeNoReturn();

            return $target;

        } catch (ProcessFailedException $e) {
            // The command gunzip failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('gunzip', $e, function () use ($file) {
                FsFile::new($file)
                    ->checkReadable();
            });
        }
    }
}
