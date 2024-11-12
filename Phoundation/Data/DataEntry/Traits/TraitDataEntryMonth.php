<?php

/**
 * Trait TraitDataEntryMonth
 *
 * This trait contains methods for DataEntry objects that require a month
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntryMonth
{
    /**
     * Returns the month for this object
     *
     * @return int|null
     */
    public function getMonth(): ?int
    {
        return $this->getTypesafe('int', 'month');
    }


    /**
     * Sets the month for this object
     *
     * @param int|null $month
     *
     * @return static
     */
    public function setMonth(?int $month): static
    {
        return $this->set(get_null($month), 'month');
    }
}
