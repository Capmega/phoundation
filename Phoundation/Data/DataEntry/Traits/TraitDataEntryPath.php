<?php

/**
 * Trait TraitDataEntryPath
 *
 * This trait contains methods for DataEntry objects that require a path
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Filesystem\FsPath;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;


trait TraitDataEntryPath
{
    /**
     * Returns the path for this object
     *
     * @return FsPathInterface|null
     */
    public function getPath(): ?FsPathInterface
    {
        return $this->getValueTypesafe(FsPathInterface::class, 'path');
    }


    /**
     * Sets the path for this object
     *
     * @param FsPathInterface|string|null  $path
     * @param FsRestrictionsInterface|null $restrictions
     *
     * @return static
     */
    public function setPath(FsPathInterface|string|null $path, ?FsRestrictionsInterface $restrictions = null): static
    {
        return $this->set(is_string($path) ? new FsPath($path, $restrictions) : $path, 'path');
    }
}
