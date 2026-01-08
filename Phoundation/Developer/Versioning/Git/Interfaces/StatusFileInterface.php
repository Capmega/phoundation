<?php

/**
 * Class StatusFile
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Interfaces;

use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;

interface StatusFileInterface extends PhoFileInterface
{
    /**
     * Returns the target file
     *
     * @return PhoFileInterface|null
     */
    public function getGitTarget(): ?PhoFileInterface;

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
     * @param PhoPathInterface $target_path
     *
     * @return static
     */
    public function patch(PhoPathInterface $target_path): static;

    /**
     * Generates a diff patch file for this file and returns the file name for the patch file
     *
     * @return PhoFileInterface
     */
    public function getPatchFile(): PhoFileInterface;

    /**
     * Returns the status for this file
     *
     * @return string
     */
    public function getReadableStatus(): string;
}
