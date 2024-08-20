<?php

/**
 * Trait TraitDataEntryDeviceObject
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Plugins\Hardware
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Plugins\Phoundation\Hardware\Devices\Device;
use Plugins\Phoundation\Hardware\Devices\Interfaces\DeviceInterface;

trait TraitDataEntryDeviceObject
{
    /**
     * Returns the devices_id for this object
     *
     * @return int|null
     */
    public function getDevicesId(): ?int
    {
        return $this->getTypesafe('int', 'devices_id');
    }


    /**
     * Sets the devices_id for this object
     *
     * @param int|null $devices_id
     *
     * @return static
     */
    public function setDevicesId(?int $devices_id): static
    {
        return $this->set($devices_id, 'devices_id');
    }


    /**
     * Returns the devices_id for this device
     *
     * @return DeviceInterface|null
     */
    public function getDevice(): ?DeviceInterface
    {
        $devices_id = $this->getTypesafe('int', 'devices_id');
        if ($devices_id) {
            return Device::get($devices_id, 'id');
        }

        return null;
    }


    /**
     * Returns the devices_name for this device
     *
     * @return string|null
     */
    public function getDevicesName(): ?string
    {
        return $this->getTypesafe('string', 'devices_name');
    }


    /**
     * Sets the devices_name for this device
     *
     * @param string|null $devices_name
     *
     * @return static
     */
    public function setDevicesName(?string $devices_name): static
    {
        return $this->set($devices_name, 'devices_name');
    }
}
