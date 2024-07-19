<?php

/**
 * Trait TraitDataEntryDirectory
 *
 * This trait contains methods for DataEntry objects that require a directory
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsPath;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;

trait TraitDataEntryDirectory
{
    /**
     * Returns the path for this object
     *
     * @return FsDirectoryInterface|null
     */
    public function getDirectory(): ?FsDirectoryInterface
    {
        return $this->getTypesafe(FsDirectoryInterface::class, 'directory');
    }


    /**
     * Sets the path for this object
     *
     * @param FsDirectoryInterface|string|null $directory
     *
     * @return static
     */
    public function setDirectory(FsDirectoryInterface|string|null $directory, ?FsRestrictionsInterface $restrictions = null): static
    {
        return $this->set(is_string($directory) ? new FsDirectory($directory, $restrictions) : $directory, 'directory');
    }
}
