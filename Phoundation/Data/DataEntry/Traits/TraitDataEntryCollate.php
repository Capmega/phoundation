<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

/**
 * Trait TraitDataEntryCollate
 *
 * This trait contains methods for DataEntry objects that require a collate
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryCollate
{
    /**
     * Returns the collate for this object
     *
     * @return string|null
     */
    public function getCollate(): ?string
    {
        return $this->getValueTypesafe('string', 'collate');
    }


    /**
     * Sets the collate for this object
     *
     * @param string|null $collate
     *
     * @return static
     */
    public function setCollate(?string $collate): static
    {
        return $this->set($collate, 'collate');
    }
}
