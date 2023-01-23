<?php

namespace Phoundation\Data\DataEntry;



/**
 * Trait DataEntryEmail
 *
 * This trait contains methods for DataEntry objects that require a email
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryEmail
{
    /**
     * Returns the email for this object
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->getDataValue('email');
    }



    /**
     * Sets the email for this object
     *
     * @param string|null $email
     * @return static
     */
    public function setEmail(?string $email): static
    {
        return $this->setDataValue('email', $email);
    }
}