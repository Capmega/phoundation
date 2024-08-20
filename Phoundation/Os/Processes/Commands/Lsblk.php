<?php

/**
 * Class Lsblk
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;


class Lsblk extends Command
{
    /**
     * Returns all available storage block devices
     *
     * @return IteratorInterface
     */
    public function getStorageDevicesTree(): IteratorInterface
    {
        // Build the process parameters, then execute
        $devices = $this->clearArguments()
                        ->setCommand('lsblk')
                        ->addArgument('-J')
                        ->executeReturnString();
        $return  = Iterator::new();
        $devices = Json::decode($devices);

        return $this->convertDevicesTree($return, $devices['blockdevices']);
    }


    /**
     * Loads the specified devices into the specified iterator,
     *
     * @param IteratorInterface $iterator
     * @param array             $devices
     *
     * @return IteratorInterface
     */
    protected function convertDevicesTree(IteratorInterface $iterator, array $devices): IteratorInterface
    {
        foreach ($devices as $data) {
            $data['mountpoints'] = Iterator::new()
                                           ->setSource($data['mountpoints']);
            if (!empty($data['children'])) {
                $data['children'] = $this->convertDevicesTree($iterator, $data['children']);
            }
            $iterator->add($data, $data['name']);
        }

        return $iterator;
    }


    /**
     * Returns true if the specified device is a storage device
     *
     * @param FsFileInterface|string $device
     *
     * @return bool
     */
    public function isStorageDevice(FsFileInterface|string $device): bool
    {
        return $this->getStorageDevices()
                    ->keyExists(Strings::ensureStartsNotWith($device, '/dev/'));
    }


    /**
     * Returns all available storage block devices
     *
     * @return IteratorInterface
     */
    public function getStorageDevices(): IteratorInterface
    {
        // Build the process parameters, then execute
        $devices = $this->clearArguments()
                        ->setCommand('lsblk')
                        ->addArgument('-J')
                        ->executeReturnString();
        $return  = Iterator::new();
        $devices = Json::decode($devices);

        return $this->flattenDevicesTree($return, $devices['blockdevices']);
    }


    /**
     * Loads the specified devices into the specified iterator, flattening the device tree into a list
     *
     * @param IteratorInterface $iterator
     * @param array             $devices
     * @param string|null       $parent
     *
     * @return IteratorInterface
     */
    protected function flattenDevicesTree(IteratorInterface $iterator, array $devices, ?string $parent = null): IteratorInterface
    {
        foreach ($devices as $data) {
            $data['parent']      = $parent;
            $data['mountpoints'] = Iterator::new()
                                           ->setSource($data['mountpoints']);
            $children = isset_get($data['children']);
            unset($data['children']);
            $iterator->add($data, $data['name']);
            if ($children) {
                $this->flattenDevicesTree($iterator, $children, $data['name']);
            }
        }

        return $iterator;
    }
}
