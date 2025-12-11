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
    public function getPath(): ?string
    {
        return $this->getTypesafe('string', 'path');
    }


    /**
     * Sets the path for this object
     *
     * @param string|null  $path
     *
     * @return static
     */
    public function setPath(string|null $path): static
    {
        return $this->set(get_null($path), 'path');
    }
}
