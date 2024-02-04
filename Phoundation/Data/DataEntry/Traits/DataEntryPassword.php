<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryPassword
 *
 * This trait contains methods for DataEntry objects that require a password
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryPassword
{
    /**
     * Returns the password for this object
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->getSourceValueTypesafe('string', 'password');
    }


    /**
     * Sets the password for this object
     *
     * @param string|null $password
     * @return static
     */
    public function setPassword(?string $password): static
    {
        return $this->setSourceValue('password', $password);
    }
}
