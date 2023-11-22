<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
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
     * @param string $file The file to be gzipped.
     * @return string
     */
    public function gzip(string $file): string
    {
        try {
            if (!str_ends_with($this->file, '.gz')) {
                if (!str_ends_with($this->file, '.tgz')) {
                    throw new OutOfBoundsException(tr('Cannot gunzip file ":file", the filename must end with ".gz"', [
                        ':file' => $this->file
                    ]));
                }
            }

            $this->setInternalCommand('gzip')
                 ->addArguments($file)
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
     * @param string $file The file to be gunzipped.
     * @return string
     */
    public function gunzip(string $file): string
    {
        try {
            $this->setInternalCommand('gunzip')
                 ->addArguments($file)
                 ->setTimeout(120)
                 ->executeNoReturn();

            return Strings::until(Strings::until($file, '.tgz'), '.gz');

        } catch (ProcessFailedException $e) {
            // The command gunzip failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('gunzip', $e, function() use ($file) {
                File::new($file)->checkReadable();
            });
        }
    }
}
