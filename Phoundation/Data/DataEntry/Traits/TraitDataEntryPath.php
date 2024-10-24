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

use Phoundation\Filesystem\PhoPath;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;


trait TraitDataEntryPath
{
    /**
     * Returns the path for this object
     *
     * @return PhoPathInterface|null
     */
    public function getPath(): ?PhoPathInterface
    {
        return $this->getValueTypesafe(PhoPathInterface::class, 'path');
    }


    /**
     * Sets the path for this object
     *
     * @param PhoPathInterface|string|null  $path
     * @param PhoRestrictionsInterface|null $restrictions
     *
     * @return static
     */
    public function setPath(PhoPathInterface|string|null $path, ?PhoRestrictionsInterface $restrictions = null): static
    {
        return $this->set(is_string($path) ? new PhoPath($path, $restrictions) : $path, 'path');
    }
}
