<?php

/**
 * Class Zip
 *
 * This class contains various "zip" methods
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Traits\TraitDataCompressionLevel;
use Phoundation\Data\Traits\TraitDataSourcePath;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsPath;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Os\Processes\Commands\Interfaces\ZipInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Utils\Config;
use Stringable;


class Zip extends Command implements ZipInterface
{
    use TraitDataCompressionLevel;
    use TraitDataSourcePath;


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

        $this->timeout = 300;
        $this->compression_level = Config::getInteger('filesystem.compression.zip.level.default', 6);
    }


    /**
     * Unzips the specified file
     *
     * @param FsDirectoryInterface $target
     *
     * @return FsDirectoryInterface
     */
    public function unzip(FsDirectoryInterface $target): FsDirectoryInterface
    {
        try {
            $this->setSourcePath($target)
                 ->setCommand('unzip')
                 ->addArguments($this->source_path)
                 ->executeNoReturn();

            return $this->getExecutionDirectory();

        } catch (ProcessFailedException $e) {
            // The command unzip failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('unzip', $e, function () use ($target) {
                FsFile::new($target)->checkReadable();
            });
        }
    }


    /**
     * Zips the specified path
     *
     * @param FsFileInterface|null $target
     *
     * @return FsFileInterface
     */
    public function zip(?FsFileInterface $target = null): FsFileInterface
    {
        try {
            if (!$target) {
                $parent = $this->source_path->getParentDirectory();
                $target = new FsFile($parent . $this->source_path->getBasename() . '.zip', $parent->getRestrictions());
            }

            $this->setCommand('zip')
                 ->addArguments('-rp' . $this->compression_level)
                 ->addArguments($target)
                 ->addArguments($this->source_path->makeRelative($this->source_path->getParentDirectory()))
                 ->executeNoReturn();

            return $target;

        } catch (ProcessFailedException $e) {
            // The command zip failed, most of the time either $file doesn't exist, or we don't have access
            static::handleException('zip', $e, function ($e) {
                FsPath::new($this->source_path)->checkReadable('zip', $e);
            });
        }
    }
}
