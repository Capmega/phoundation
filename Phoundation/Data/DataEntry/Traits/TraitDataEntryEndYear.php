<?php

/**
 * Trait TraitDataEntryEndYear
 *
 * This trait contains methods for DataEntry objects that require "end_year"
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntryEndYear
{
    /**
     * Returns the year for this object
     *
     * @return int|null
     */
    public function getEndYear(): ?int
    {
        return $this->getTypesafe('int', 'end_year');
    }


    /**
     * Sets the year for this object
     *
     * @param int|null $year
     *
     * @return static
     */
    public function setEndYear(?int $year): static
    {
        return $this->set(get_null($year), 'end_year');
    }
}
