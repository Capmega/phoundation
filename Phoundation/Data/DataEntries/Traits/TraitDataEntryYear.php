<?php

/**
 * Trait TraitDataEntryYear
 *
 * This trait contains methods for DataEntry objects that require "year"
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryYear
{
    /**
     * Returns the year for this object
     *
     * @return int|null
     */
    public function getYear(): ?int
    {
        return $this->getTypesafe('int', 'year');
    }


    /**
     * Sets the year for this object
     *
     * @param int|null $year
     *
     * @return static
     */
    public function setYear(?int $year): static
    {
        return $this->set(get_null($year), 'year');
    }
}
