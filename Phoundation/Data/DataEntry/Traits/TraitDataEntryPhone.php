<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait TraitDataEntryPhone
 *
 * This trait contains methods for DataEntry objects that require phone numbers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataEntryPhone
{
    /**
     * Returns the phone for this object
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->getValueTypesafe('string', 'phone');
    }


    /**
     * Sets the phone for this object
     *
     * @param string|null $phone
     * @return static
     */
    public function setPhone(?string $phone): static
    {
        return $this->setValue('phone', $phone);
    }
}
