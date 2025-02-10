<?php

/**
 * Trait TraitDataEntryDevice
 *
 * This trait contains methods for DataEntry objects that require a device
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Plugins\Hardware
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Plugins\Phoundation\Hardware\Devices\Device;
use Plugins\Phoundation\Hardware\Devices\Interfaces\DeviceInterface;


trait TraitDataEntryDeviceObject
{
    /**
     * Setup virtual configuration for Devices
     *
     * @return static
     */
    protected function addVirtualConfigurationDevices(): static
    {
        return $this->addVirtualConfiguration('devices', Device::class, [
            'id',
            'code',
            'name'
        ]);
    }


    /**
     * Returns the devices_id column
     *
     * @return int|null
     */
    public function getDevicesId(): ?int
    {
        return $this->getVirtualData('devices', 'int', 'id');
    }


    /**
     * Sets the devices_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setDevicesId(?int $id): static
    {
        return $this->setVirtualData('devices', $id, 'id');
    }


    /**
     * Returns the devices_code column
     *
     * @return string|null
     */
    public function getDevicesCode(): ?string
    {
        return $this->getVirtualData('devices', 'string', 'code');
    }


    /**
     * Sets the devices_code column
     *
     * @param string|null $code
     * @return static
     */
    public function setDevicesCode(?string $code): static
    {
        return $this->setVirtualData('devices', $code, 'code');
    }


    /**
     * Returns the devices_name column
     *
     * @return string|null
     */
    public function getDevicesName(): ?string
    {
        return $this->getVirtualData('devices', 'string', 'name');
    }


    /**
     * Sets the devices_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setDevicesName(?string $name): static
    {
        return $this->setVirtualData('devices', $name, 'name');
    }


    /**
     * Returns the Device Object
     *
     * @return DeviceInterface|null
     */
    public function getDeviceObject(): ?DeviceInterface
    {
        return $this->getVirtualObject('devices');
    }


    /**
     * Returns the devices_id for this user
     *
     * @param DeviceInterface|null $o_object
     *
     * @return static
     */
    public function setDeviceObject(?DeviceInterface $o_object): static
    {
        return $this->setVirtualObject('devices', $o_object);
    }
}
