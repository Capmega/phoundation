<?php

/**
 * Trait TraitDataEntryClass
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

trait TraitDataEntryClass
{
    /**
     * Returns the class for this object
     *
     * @return string|null
     */
    public function getClass(): ?string
    {
        return $this->getTypesafe('string', 'class');
    }


    /**
     * Sets the class for this object
     *
     * @param string|null $class
     *
     * @return static
     */
    public function setClass(?string $class): static
    {
        return $this->set($class, 'class');
    }
}
