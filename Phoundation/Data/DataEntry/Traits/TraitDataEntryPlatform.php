<?php

/**
 * Trait TraitDataEntryPlatform
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntryPlatform
{
    /**
     * Returns the platform for this object
     *
     * @return string|null
     */
    public function getPlatform(): ?string
    {
        return $this->getTypesafe('string', 'platform');
    }


    /**
     * Sets the platform for this object
     *
     * @param string|null $platform
     *
     * @return static
     */
    public function setPlatform(?string $platform): static
    {
        return $this->set($platform, 'platform');
    }
}
