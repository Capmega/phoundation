<?php

/**
 * Trait TraitDataEntryEmail
 *
 * This trait contains methods for DataEntry objects that require a email
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

trait TraitDataEntryEmail
{
    /**
     * Returns the email for this object
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->getTypesafe('string', 'email');
    }


    /**
     * Sets the email for this object
     *
     * @param string|null $email
     *
     * @return static
     */
    public function setEmail(?string $email): static
    {
        return $this->set($email, 'email');
    }
}
