<?php

/**
 * Trait TraitDataEntryDirectory
 *
 * This trait contains methods for DataEntry objects that require a directory
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoPath;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;


trait TraitDataEntryDirectory
{
    /**
     * Returns the path for this object
     *
     * @return PhoDirectoryInterface|null
     */
    public function getDirectory(): ?PhoDirectoryInterface
    {
        return $this->getTypesafe(PhoDirectoryInterface::class, 'directory');
    }


    /**
     * Sets the path for this object
     *
     * @param PhoDirectoryInterface|string|null $directory
     * @param PhoRestrictionsInterface|null $restrictions
     * @return static
     */
    public function setDirectory(PhoDirectoryInterface|string|null $directory, ?PhoRestrictionsInterface $restrictions = null): static
    {
        return $this->set(is_string($directory) ? new PhoDirectory($directory, $restrictions) : $directory, 'directory');
    }
}
