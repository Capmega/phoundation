<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

/**
 * Trait TraitDataEntryMethod
 *
 * This trait contains methods for DataEntry objects that require a method
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryMethod
{
    /**
     * Returns the method for this object
     *
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->getValueTypesafe('string', 'method');
    }


    /**
     * Sets the method for this object
     *
     * @param string|null $method
     *
     * @return static
     */
    public function setMethod(?string $method): static
    {
        return $this->set($method, 'method');
    }
}
