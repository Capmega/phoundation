<?php

/**
 * Trait TraitDataEntryWorkPhone
 *
 * This trait contains methods for DataEntry objects that require work phone numbers
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryWorkPhone
{
    /**
     * Returns the work phone for this object
     *
     * @return string|null
     */
    public function getWorkPhone(): ?string
    {
        return $this->getTypesafe('string', 'work_phone');
    }


    /**
     * Sets the work phone for this object
     *
     * @param string|null $work_phone
     *
     * @return static
     */
    public function setWorkPhone(?string $work_phone): static
    {
        return $this->set(get_null($work_phone), 'work_phone');
    }
}
