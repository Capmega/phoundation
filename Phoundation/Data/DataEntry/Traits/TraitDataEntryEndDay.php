<?php

/**
 * Trait TraitDataEntryEndDay
 *
 * This trait contains methods for DataEntry objects that require "end_day"
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntryEndDay
{
    /**
     * Returns the end day for this object
     *
     * @return int|null
     */
    public function getEndDay(): ?int
    {
        return $this->getTypesafe('int', 'end_day');
    }


    /**
     * Sets the end day for this object
     *
     * @param int|null $day
     *
     * @return static
     */
    public function setEndDay(?int $day): static
    {
        return $this->set(get_null($day), 'end_day');
    }
}
