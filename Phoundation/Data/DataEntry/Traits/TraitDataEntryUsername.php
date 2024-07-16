<?php

/**
 * Trait TraitDataEntryUsername
 *
 * This trait contains methods for DataEntry objects that require a username
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

trait TraitDataEntryUsername
{
    /**
     * Returns the username for this object
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->getValueTypesafe('string', 'username');
    }


    /**
     * Sets the username for this object
     *
     * @param string|null $username
     *
     * @return static
     */
    public function setUsername(?string $username): static
    {
        return $this->set($username, 'username');
    }
}
