<?php

/**
 * Class StatusFile
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Interfaces;

use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\Interfaces\FsPathInterface;

interface StatusFileInterface extends FsFileInterface
{
/**
     * Returns the file name
     *
     * @return FsFileInterface
     */
    public function getFile(): FsFileInterface;

    /**
     * Returns the target file
     *
     * @return FsFileInterface|null
     */
    public function getGitTarget(): ?FsFileInterface;

    /**
     * Returns the status for this file
     *
     * @return StatusInterface
     */
    public function getStatusObject(): StatusInterface;

    /**
     * Returns true if this file has a git conflict
     *
     * @return bool
     */
    public function hasConflict(): bool;

    /**
     * Applies the patch for this file on the specified target file
     *
     * @param FsPathInterface $target_path
     *
     * @return static
     */
    public function patch(FsPathInterface $target_path): static;

    /**
     * Generates a diff patch file for this file and returns the file name for the patch file
     *
     * @return FsFileInterface
     */
    public function getPatchFile(): FsFileInterface;
}
