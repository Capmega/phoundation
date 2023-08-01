<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryAddress
 *
 * This trait contains methods for DataEntry objects that require a address
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryAddress
{
    /**
     * Returns the address for this object
     *
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->getDataValue('string', 'address');
    }


    /**
     * Sets the address for this object
     *
     * @param string|null $address
     * @return static
     */
    public function setAddress(?string $address): static
    {
        return $this->setSourceValue('address', $address);
    }
    
    
    
    /**
     * Returns the zipcode for this object
     *
     * @return string|null
     */
    public function getZipcode(): ?string
    {
        return $this->getDataValue('string', 'zipcode');
    }


    /**
     * Sets the zipcode for this object
     *
     * @param string|null $zipcode
     * @return static
     */
    public function setZipcode(?string $zipcode): static
    {
        return $this->setSourceValue('zipcode', $zipcode);
    }
}