<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

/**
 * Trait TraitDataEntrySpent
 *
 * This trait contains methods for DataEntry objects that require a spent value
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntrySpent
{
    /**
     * Returns the spent value for this object
     *
     * @return float|null
     */
    public function getSpent(): ?float
    {
        return $this->getValueTypesafe('float', 'spent');
    }


    /**
     * Sets the spent value for this object
     *
     * @param float|null $spent
     *
     * @return static
     */
    public function setSpent(float|null $spent): static
    {
        return $this->setValue('spent', $spent);
    }
}
