<?php

/**
 * Trait TraitDataEntryHomePhone
 *
 * This trait contains methods for DataEntry objects that require home phone numbers
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryHomePhone
{
    /**
     * Returns the home phone for this object
     *
     * @return string|null
     */
    public function getHomePhone(): ?string
    {
        return $this->getTypesafe('string', 'home_phone');
    }


    /**
     * Sets the home phone for this object
     *
     * @param string|null $home_phone
     *
     * @return static
     */
    public function setHomePhone(?string $home_phone): static
    {
        return $this->set(get_null($home_phone), 'home_phone');
    }
}
