<?php

/**
 * Trait TraitDataEntryDay
 *
 * This trait contains methods for DataEntry objects that require a day
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntryDay
{
    /**
     * Returns the day for this object
     *
     * @return int|null
     */
    public function getDay(): ?int
    {
        return $this->getTypesafe('int', 'day');
    }


    /**
     * Sets the day for this object
     *
     * @param int|null $day
     *
     * @return static
     */
    public function setDay(?int $day): static
    {
        return $this->set(get_null($day), 'day');
    }
}
