<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Interfaces;

use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoFilesInterface;

interface StatusFilesInterface extends PhoFilesInterface
{
    /**
     * Scans for changes
     *
     * @return static
     */
    public function scanChanges(): static;


    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|string|null $columns
     * @param array             $filters
     * @param string|null       $id_column
     *
     * @return static
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'file'): static;


    /**
     * Applies the patch for this file on the specified target file
     *
     * @param PhoDirectoryInterface $_target_path
     *
     * @return static
     */
    public function patch(PhoDirectoryInterface $_target_path): static;


    /**
     * Generates a diff patch file for this path and returns the file name for the patch file
     *
     * @param bool $cached
     *
     * @return PhoFileInterface
     */
    public function getPatchFile(bool $cached = false): PhoFileInterface;


    /**
     * Returns a git object for this path
     *
     * @return GitInterface
     */
    public function getGit(): GitInterface;
}
