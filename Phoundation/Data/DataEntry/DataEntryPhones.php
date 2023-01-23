<?php

namespace Phoundation\Data\DataEntry;



/**
 * Trait DataEntryPhones
 *
 * This trait contains methods for DataEntry objects that require phone numbers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryPhones
{
    /**
     * Returns the phones for this object
     *
     * @return string|null
     */
    public function getPhones(): ?string
    {
        return $this->getDataValue('phones');
    }



    /**
     * Sets the phones for this object
     *
     * @param string|null $phones
     * @return static
     */
    public function setPhones(?string $phones): static
    {
        return $this->setDataValue('phones', $phones);
    }
}