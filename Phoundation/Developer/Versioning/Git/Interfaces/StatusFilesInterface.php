<?php

namespace Phoundation\Developer\Versioning\Git\Interfaces;


/**
 * Class StatusFiles
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
interface StatusFilesInterface
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
    public function CliDisplayTable(): void;

    /**
     * Applies the patch for this file on the specified target file
     *
     * @param string $target_path
     * @return static
     */
    public function patch(string $target_path): static;

    /**
     * Generates a diff patch file for this path and returns the file name for the patch file
     *
     * @param bool $cached
     * @return string|null
     */
    public function getPatchFile(bool $cached = false): ?string;

    /**
     * Returns a git object for this path
     *
     * @return GitInterface
     */
    public function getGit(): GitInterface;
}