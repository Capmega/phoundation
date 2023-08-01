<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryEmail
 *
 * This trait contains methods for DataEntry objects that require a email
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        return $this->getDataValue('string', 'email');
    }


    /**
     * Sets the email for this object
     *
     * @param string|null $number
     * @return static
     */
    public function setEmail(?string $number): static
    {
        return $this->setSourceValue('email', $number);
    }
}