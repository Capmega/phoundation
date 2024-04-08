<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

/**
 * Trait TraitDataEntryFirstNames
 *
 * This trait contains methods for DataEntry objects that require a code
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryFirstNames
{
    /**
     * Returns the first_names for this user
     *
     * @return string|null
     */
    public function getFirstNames(): ?string
    {
        return $this->getValueTypesafe('string', 'first_names');
    }


    /**
     * Sets the first_names for this user
     *
     * @param string|null $first_names
     *
     * @return static
     */
    public function setFirstNames(?string $first_names): static
    {
        return $this->setValue('first_names', $first_names);
    }
}
