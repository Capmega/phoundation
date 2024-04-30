<?php

namespace Phoundation\Security\Luks;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Interfaces\PathInterface;
use Phoundation\Filesystem\MountedStorageDevices;

class Devices
{
    /**
     * Scans for LUKS devices in the specified path and returns all found devices
     *
     * @param PathInterface $path
     * @param int|bool      $recurse
     *
     * @return IteratorInterface
     */
    public static function scan(PathInterface $path, int|bool $recurse = false): IteratorInterface
    {
        Directory::new($path)->each(function ($file) {
            show($file);
        });
showdie();
    }


    /**
     * Returns all mounted LUKS devices
     *
     * @return IteratorInterface
     */
    public static function getMounted(): IteratorInterface
    {
        $return  = [];
        $devices = MountedStorageDevices::new()->scan();

        foreach ($devices as $device) {
            if (str_starts_with($device, 'dm-uuid-CRYPT-LUKS2-')) {
                $return[] = $device;
            }
        }

        return new Iterator($return);
    }
}