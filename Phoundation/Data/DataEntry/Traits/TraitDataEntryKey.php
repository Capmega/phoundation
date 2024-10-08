<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

/**
 * Trait TraitDataEntryKey
 *
 * This trait contains methods for DataEntry objects that require a key
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryKey
{
    /**
     * Returns the key for this object
     *
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->getValueTypesafe('string', 'key');
    }


    /**
     * Sets the key for this object
     *
     * @param string|null $key
     *
     * @return static
     */
    public function setKey(?string $key): static
    {
        return $this->set($key, 'key');
    }
}
