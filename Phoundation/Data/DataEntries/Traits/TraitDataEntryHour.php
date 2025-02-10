<?php

/**
 * Trait TraitDataEntryHour
 *
 * This trait contains methods for DataEntry objects that require a hour
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryHour
{
    /**
     * Returns the hour for this object
     *
     * @return int|null
     */
    public function getHour(): ?int
    {
        return $this->getTypesafe('int', 'hour');
    }


    /**
     * Sets the hour for this object
     *
     * @param int|null $hour
     *
     * @return static
     */
    public function setHour(?int $hour): static
    {
        return $this->set(get_null($hour), 'hour');
    }
}
