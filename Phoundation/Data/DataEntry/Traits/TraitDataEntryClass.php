<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait TraitDataEntryClass
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataEntryClass
{
    /**
     * Returns the class for this object
     *
     * @return string|null
     */
    public function getClass(): ?string
    {
        return $this->getSourceValueTypesafe('string', 'class');
    }


    /**
     * Sets the class for this object
     *
     * @param string|null $class
     * @return static
     */
    public function setClass(?string $class): static
    {
        return $this->setSourceValue('class', $class);
    }
}
