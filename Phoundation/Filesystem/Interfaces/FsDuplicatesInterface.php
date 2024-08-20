<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Interfaces;

interface FsDuplicatesInterface
{
    /**
     * Deletes the duplicate files
     *
     * @return static
     */
    public function deleteKeepFirst(): static;

    /**
     * Returns the number of bytes freed by the deleting of duplicate files
     *
     * @return int
     */
    public function getDeletedBytes(): int;

    /**
     * Returns the number of bytes freed by the deleting of duplicate files
     *
     * @return int
     */
    public function getDeletedCount(): int;

    /**
     * Returns the number of bytes freed by the deleting of duplicate files
     *
     * @return FsFilesInterface
     */
    public function getDeletedFiles(): FsFilesInterface;
}
