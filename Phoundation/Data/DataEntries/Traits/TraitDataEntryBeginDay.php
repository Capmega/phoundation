<?php

/**
 * Trait TraitDataEntryBeginDay
 *
 * This trait contains methods for DataEntry objects that require a begin_day
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryBeginDay
{
    /**
     * Returns the begin day for this object
     *
     * @return int|null
     */
    public function getBeginDay(): ?int
    {
        return $this->getTypesafe('int', 'begin_day');
    }


    /**
     * Sets the begin day for this object
     *
     * @param int|null $day
     *
     * @return static
     */
    public function setBeginDay(?int $day): static
    {
        return $this->set(get_null($day), 'begin_day');
    }
}
