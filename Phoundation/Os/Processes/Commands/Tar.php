<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\File;
use Phoundation\Os\Processes\Exception\ProcessFailedException;


/**
 * Class FilesystemCommands
 *
 * This class contains various "tar" commands
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Tar extends Command
{
    /**
     * Untars the specified file
     *
     * @param string $file The file to be untarred. Must be a tar file (doh)
     * @return string
     */
    public function untar(string $file, ?string $target_path = null): string
    {
        try {
            if (!$target_path) {
                $target_path = dirname($file);
            }

            $this
                ->setExecutionPath($target_path)
                ->setInternalCommand('tar')
                ->addArguments(['-x', '-f'])
                ->addArguments($file)
                ->setTimeout(120)
                ->executeNoReturn();

            return $target_path;

        } catch (ProcessFailedException $e) {
            // The command tar failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('tar', $e, function() use ($file) {
                File::new($file)->checkReadable();
            });
        }
    }


    /**
     * Tars the specified path
     *
     * @param string $path
     * @param string|null $target_file
     * @param bool $compression
     * @return string
     */
    public function tar(string $path, ?string $target_file = null, bool $compression = true): string
    {
        try {
            if (!$target_file) {
                $target_file = $path . '.tar.gz';
            }

            $this
                ->setExecutionPath(dirname($path))
                ->setInternalCommand('tar')
                ->addArguments(['-c', ($compression ? 'j' : null), '-f'])
                ->addArguments($target_file)
                ->addArguments($path)
                ->setTimeout(120)
                ->executeNoReturn();

            return $target_file;

        } catch (ProcessFailedException $e) {
            // The command tar failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('tar', $e, function() use ($path) {
                File::new($path)->checkReadable();
            });
        }
    }
}
