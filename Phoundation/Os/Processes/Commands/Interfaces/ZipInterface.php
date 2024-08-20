<?php

namespace Phoundation\Os\Processes\Commands\Interfaces;

use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\Interfaces\FsPathInterface;


interface ZipInterface
{
    /**
     * Unzips the specified file
     *
     * @param FsDirectoryInterface $target The directory to which to unzip
     *
     * @return FsDirectoryInterface
     */
    public function unzip(FsDirectoryInterface $target): FsDirectoryInterface;

    /**
     * Zips the specified path
     *
     * @param FsFileInterface|null $target
     *
     * @return FsFileInterface
     */
    public function zip(?FsFileInterface $target = null): FsFileInterface;

    /**
     * Returns the source
     *
     * @return int
     */
    public function getCompressionLevel(): int;

    /**
     * Sets the source
     *
     * @param int $CompressionLevel
     *
     * @return static
     */
    public function setCompressionLevel(int $CompressionLevel): static;

    /**
     * Returns the target object
     *
     * @return FsPathInterface
     */
    public function getSourcePath(): FsPathInterface;

    /**
     * Sets the target object
     *
     * @param FsPathInterface|null $source_path
     *
     * @return static
     */
    public function setSourcePath(?FsPathInterface $source_path): static;
}
