<?php

namespace Phoundation\Data\DataEntry;



/**
 * Trait DataEntryCode
 *
 * This trait contains methods for DataEntry objects that require a code
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryCode
{
    /**
     * Returns the code for this object
     *
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->getDataValue('code');
    }



    /**
     * Sets the code for this object
     *
     * @param string|null $code
     * @return static
     */
    public function setCode(?string $code): static
    {
        return $this->setDataValue('code', $code);
    }
}