<?php

/**
 * Trait TraitDataEntryMiddleNames
 *
 * This trait contains methods for DataEntry objects that require a code
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryMiddleNames
{
    /**
     * Returns the middle_names for this user
     *
     * @return string|null
     */
    public function getMiddleNames(): ?string
    {
        return $this->getTypesafe('string', 'middle_names');
    }


    /**
     * Sets the middle_names for this user
     *
     * @param string|null $middle_names
     *
     * @return static
     */
    public function setMiddleNames(?string $middle_names): static
    {
        return $this->set(get_null($middle_names), 'middle_names');
    }
}
