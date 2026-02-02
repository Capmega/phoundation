<?php

/**
 * Trait TraitDataEntryType
 *
 * This trait contains methods for DataEntry objects that require a type
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryType
{
    /**
     * Returns true if the DataEntry object has the specified type
     *
     * @param string $type          The type to compare against
     * @param bool   $strict [true] If true, will do a strict comparison (===), weak comparison (==) if false
     * @return bool
     */
    public function hasType(string $type, bool $strict = true): bool
    {
        if ($strict) {
            return $this->getType() === $type;
        }

        return $this->getType() == $type;
    }


    /**
     * Returns the type for this object
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->getTypesafe('string', 'type');
    }


    /**
     * Sets the type for this object
     *
     * @param string|null $type
     *
     * @return static
     */
    public function setType(?string $type): static
    {
        return $this->set(get_null($type), 'type');
    }
}
