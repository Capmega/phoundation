<?php

/**
 * Trait TraitDataEntryWeek
 *
 * This trait contains methods for DataEntry objects that require a week
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntryWeek
{
    /**
     * Returns the week for this object
     *
     * @return int|null
     */
    public function getWeek(): ?int
    {
        return $this->getTypesafe('int', 'week');
    }


    /**
     * Sets the week for this object
     *
     * @param int|null $week
     *
     * @return static
     */
    public function setWeek(?int $week): static
    {
        return $this->set(get_null($week), 'week');
    }
}
