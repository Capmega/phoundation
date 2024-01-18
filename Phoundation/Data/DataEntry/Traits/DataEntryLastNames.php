<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryLastNames
 *
 * This trait contains methods for DataEntry objects that require a code
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryLastNames
{
    /**
     * Returns the last_names for this user
     *
     * @return string|null
     */
    public function getLastNames(): ?string
    {
        return $this->getSourceColumnValue('string', 'last_names');
    }


    /**
     * Sets the last_names for this user
     *
     * @param string|null $last_names
     * @return static
     */
    public function setLastNames(?string $last_names): static
    {
        return $this->setSourceValue('last_names', $last_names);
    }
}
