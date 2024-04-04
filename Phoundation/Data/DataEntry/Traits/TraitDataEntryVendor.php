<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Stringable;


/**
 * Trait TraitDataEntryVendor
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryVendor
{
    /**
     * Returns the vendor for this object
     *
     * @return string|null
     */
    public function getVendor(): ?string
    {
        return $this->getValueTypesafe('string', 'vendor');
    }


    /**
     * Sets the vendor for this object
     *
     * @param Stringable|string|null $vendor
     *
     * @return static
     */
    public function setVendor(Stringable|string|null $vendor): static
    {
        return $this->setValue('vendor', (string)$vendor);
    }
}
