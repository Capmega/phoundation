<?php

/**
 * Trait TraitDataEntryDevice
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


trait TraitDataEntryDevice
{
    /**
     * Returns the device for this object
     *
     * @return string|null
     */
    public function getDevice(): ?string
    {
        return $this->getTypesafe('string', 'device');
    }


    /**
     * Sets the device for this object
     *
     * @param string|null $device
     *
     * @return static
     */
    public function setDevice(?string $device): static
    {
        return $this->set($device, 'device');
    }
}
