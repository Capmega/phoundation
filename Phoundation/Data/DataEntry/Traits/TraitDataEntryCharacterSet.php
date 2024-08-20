<?php

/**
 * Trait TraitDataEntryCharacterSet
 *
 * This trait contains methods for DataEntry objects that require a character_set
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntryCharacterSet
{
    /**
     * Returns the character_set for this object
     *
     * @return string|null
     */
    public function getCharacterSet(): ?string
    {
        return $this->getTypesafe('string', 'character_set');
    }


    /**
     * Sets the character_set for this object
     *
     * @param string|null $character_set
     *
     * @return static
     */
    public function setCharacterSet(?string $character_set): static
    {
        return $this->set($character_set, 'character_set');
    }
}
