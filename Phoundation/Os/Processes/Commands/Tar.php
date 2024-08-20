<?php

/**
 * Class FilesystemCommands
 *
 * This class contains various "tar" commands
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;


class Tar extends Command
{
    /**
     * Untars the specified file
     *
     * @param FsFileInterface           $file The file to be untarred. Must be a tar file (doh)
     * @param FsDirectoryInterface|null $target
     * @oaram int                       $timeout
     *
     * @return FsDirectoryInterface
     */
    public function untar(FsFileInterface $file, ?FsDirectoryInterface $target = null, int $timeout = 600): FsDirectoryInterface
    {
        try {
            if (!$target) {
                $target = $file->getParentDirectory();
            }

            $this->setExecutionDirectory($target)
                 ->setCommand('tar')
                 ->addArguments(['-x', '-f'])
                 ->addArguments($file)
                 ->setTimeout($timeout)
                 ->executeNoReturn();

            return $target;

        } catch (ProcessFailedException $e) {
            // The command tar failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('tar', $e, function ($e) use ($file) {
                FsFile::new($file)->checkReadable($e);
            });
        }
    }


    /**
     * Tars the specified path
     *
     * @param FsPathInterface      $path
     * @param FsFileInterface|null $target
     * @param bool                 $compression
     * @param int                  $timeout
     *
     * @return FsFileInterface
     */
    public function tar(FsPathInterface $path, ?FsFileInterface $target = null, bool $compression = true, int $timeout = 600): FsFileInterface
    {
        try {
            if (!$target) {
                $target = new FsFile($path . '.tar.gz');
            }

            $this->setExecutionDirectory($path->getParentDirectory())
                 ->setCommand('tar')
                 ->addArguments(['-c', ($compression ? 'j' : null), '-f'])
                 ->addArguments($target)
                 ->addArguments($path)
                 ->setTimeout($timeout)
                 ->executeNoReturn();

            return $target;

        } catch (ProcessFailedException $e) {
            // The command tar failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('tar', $e, function ($e, $file, $line) use ($path) {
                FsFile::new($path)->checkReadable('tar', $e);
            });
        }
    }
}
