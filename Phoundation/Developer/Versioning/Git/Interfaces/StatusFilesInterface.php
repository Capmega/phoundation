<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Interfaces;

use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\Interfaces\FsFilesInterface;

interface StatusFilesInterface extends FsFilesInterface
{
    /**
     * Scans for changes
     *
     * @return static
     */
    public function scanChanges(): static;


    /**
     * Display the files status on the CLI
     *
     * @return void
     */
    public function cliDisplayTable(): void;


    /**
     * Applies the patch for this file on the specified target file
     *
     * @param FsDirectoryInterface $target_path
     *
     * @return static
     */
    public function patch(FsDirectoryInterface $target_path): static;


    /**
     * Generates a diff patch file for this path and returns the file name for the patch file
     *
     * @param bool $cached
     *
     * @return FsFileInterface
     */
    public function getPatchFile(bool $cached = false): FsFileInterface;


    /**
     * Returns a git object for this path
     *
     * @return GitInterface
     */
    public function getGit(): GitInterface;
}