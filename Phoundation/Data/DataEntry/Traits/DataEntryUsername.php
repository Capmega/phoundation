<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryUsername
 *
 * This trait contains methods for DataEntry objects that require a username
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryUsername
{
    /**
     * Returns the username for this object
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->getSourceColumnValue('string', 'username');
    }


    /**
     * Sets the username for this object
     *
     * @param string|null $username
     * @return static
     */
    public function setUsername(?string $username): static
    {
        return $this->setSourceValue('username', $username);
    }
}
