<?php

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

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsPath;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Stringable;

class Zip extends Command
{
    /**
     * Zip class constructor
     *
     * @param FsDirectoryInterface|FsRestrictionsInterface|null $execution_directory
     * @param Stringable|string|null                            $operating_system
     * @param string|null                                       $packages
     */
    public function __construct(FsDirectoryInterface|FsRestrictionsInterface|null $execution_directory = null, Stringable|string|null $operating_system = null, ?string $packages = null)
    {
        parent::__construct($execution_directory, $operating_system, $packages);
        $this->timeout = 120;
    }


    /**
     * Unzips the specified file
     *
     * @param FsFileInterface           $file The file to be unzipped.
     * @param FsDirectoryInterface|null $target
     *
     * @return FsDirectoryInterface
     */
    public function unzip(FsFileInterface $file): FsDirectoryInterface
    {
        try {
            $this->setCommand('unzip')
                 ->addArguments($file)
                 ->executeNoReturn();

            return $this->getExecutionDirectory();

        } catch (ProcessFailedException $e) {
            // The command unzip failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('unzip', $e, function () use ($file) {
                FsFile::new($file)->checkReadable();
            });
        }
    }


    /**
     * Zips the specified path
     *
     * @param FsPathInterface      $path The file to be unzipped.
     * @param FsFileInterface|null $target
     *
     * @return FsFileInterface
     */
    public function zip(FsPathInterface $path, ?FsFileInterface $target = null): FsFileInterface
    {
        try {
            if (!$target) {
                $parent = $path->getParentDirectory();
                $target = new FsFile($parent . $path->getBasename() . '.zip', $parent->getRestrictions());
            }

            $this->setCommand('zip')
                 ->addArguments('-rp')
                 ->addArguments($target)
                 ->addArguments($path->makeRelative($this->getExecutionDirectory()))
                 ->executeNoReturn();

            return $target;

        } catch (ProcessFailedException $e) {
            // The command zip failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('zip', $e, function () use ($path) {
                FsPath::new($path)->checkReadable();
            });
        }
    }
}
