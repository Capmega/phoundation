<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Stringable;


/**
 * Trait DataEntryManufacturer
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryManufacturer
{
    /**
     * Returns the manufacturer for this object
     *
     * @return string|null
     */
    public function getManufacturer(): ?string
    {
        return $this->getSourceValueTypesafe('string', 'manufacturer');
    }


    /**
     * Sets the manufacturer for this object
     *
     * @param Stringable|string|null $manufacturer
     * @return static
     */
    public function setManufacturer(Stringable|string|null $manufacturer): static
    {
        return $this->setSourceValue('manufacturer', (string) $manufacturer);
    }
}
