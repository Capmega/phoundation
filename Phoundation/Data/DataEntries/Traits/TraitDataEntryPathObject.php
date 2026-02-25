<?php

/**
 * Trait TraitDataEntryPath
 *
 * This trait contains methods for DataEntry objects that require a path
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\PhoPath;
use Phoundation\Filesystem\Traits\TraitDataRestrictions;


trait TraitDataEntryPathObject
{
    use TraitDataEntryPath;
    use TraitDataRestrictions;


    /**
     * Returns the path for this object
     *
     * @return PhoPathInterface|null
     */
    public function getPathObject(): ?PhoPathInterface
    {
        $path = $this->getPath();

        if ($path) {
            return PhoPath::new($this->getPath(), $this->getRestrictionsObject());
        }

        return null;
    }



    /**
     * Sets the path for this object
     *
     * @param PhoPathInterface|null $_path
     *
     * @return static
     */
    public function setPathObject(?PhoPathInterface $_path): static
    {
        return $this->setPath($_path->getSource());
    }
}
