<?php

/**
 * Class FilesystemCommands
 *
 * This class contains various "tar" commands
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;


class Tar extends Command
{
    /**
     * Untars the specified file
     *
     * @param PhoFileInterface           $file The file to be untarred. Must be a tar file (doh)
     * @param PhoDirectoryInterface|null $target
     *
     * @oaram int                       $timeout
     *
     * @return PhoDirectoryInterface
     */
    public function untar(PhoFileInterface $file, ?PhoDirectoryInterface $target = null, int $timeout = 600): PhoDirectoryInterface
    {
        try {
            if (!$target) {
                $target = $file->getParentDirectoryObject();
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
                PhoFile::new($file)->checkReadable($e);
            });
        }
    }


    /**
     * Tars the specified path
     *
     * @param PhoPathInterface      $path
     * @param PhoFileInterface|null $target
     * @param bool                  $compression
     * @param int                   $timeout
     *
     * @return PhoFileInterface
     */
    public function tar(PhoPathInterface $path, ?PhoFileInterface $target = null, bool $compression = true, int $timeout = 600): PhoFileInterface
    {
        try {
            if (!$target) {
                $target = new PhoFile($path . '.tar.gz');
            }

            $this->setExecutionDirectory($path->getParentDirectoryObject())
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
                PhoFile::new($path)->checkReadable('tar', $e);
            });
        }
    }
}
