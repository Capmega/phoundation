<?php

/**
 * Trait TraitDataEntryType
 *
 * This trait contains methods for DataEntry objects that require a type
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

trait TraitDataEntryType
{
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
        return $this->set($type, 'type');
    }
}
