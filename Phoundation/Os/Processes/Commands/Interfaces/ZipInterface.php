<?php

namespace Phoundation\Os\Processes\Commands\Interfaces;

use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;


interface ZipInterface
{
    /**
     * Unzips the specified file
     *
     * @param PhoDirectoryInterface $target The directory to which to unzip
     *
     * @return PhoDirectoryInterface
     */
    public function unzip(PhoDirectoryInterface $target): PhoDirectoryInterface;

    /**
     * Zips the specified path
     *
     * @param PhoFileInterface|null $target
     *
     * @return PhoFileInterface
     */
    public function zip(?PhoFileInterface $target = null): PhoFileInterface;

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
     * @return PhoPathInterface
     */
    public function getSourcePath(): PhoPathInterface;

    /**
     * Sets the target object
     *
     * @param PhoPathInterface|null $source_path
     *
     * @return static
     */
    public function setSourcePath(?PhoPathInterface $source_path): static;
}
