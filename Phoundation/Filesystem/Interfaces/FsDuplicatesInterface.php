<?php

namespace Phoundation\Filesystem\Interfaces;

interface FsDuplicatesInterface
{
    /**
     * Deletes the duplicate files
     *
     * @return $this
     */
    public function deleteKeepFirst(): static;

    /**
     * Returns the amount of bytes freed by the deleting of duplicate files
     *
     * @return int
     */
    public function getDeletedBytes(): int;

    /**
     * Returns the amount of bytes freed by the deleting of duplicate files
     *
     * @return int
     */
    public function getDeletedCount(): int;

    /**
     * Returns the amount of bytes freed by the deleting of duplicate files
     *
     * @return FsFilesInterface
     */
    public function getDeletedFiles(): FsFilesInterface;
}
