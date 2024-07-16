<?php

/**
 * Trait TraitDataEntryUnits
 *
 * This trait contains methods for DataEntry objects that requires units
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Stringable;

trait TraitDataEntryUnits
{
    /**
     * Returns the units for this object
     *
     * @return string|null
     */
    public function getUnits(): ?string
    {
        return $this->getValueTypesafe('string', 'units');
    }


    /**
     * Sets the units for this object
     *
     * @param Stringable|string|null $units
     *
     * @return static
     */
    public function setUnits(Stringable|string|null $units): static
    {
        return $this->set((string) $units, 'units');
    }
}
