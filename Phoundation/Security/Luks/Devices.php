<?php

declare(strict_types=1);

namespace Phoundation\Security\Luks;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Filesystem\FsMountedStorageDevices;

class Devices
{
    /**
     * Scans for LUKS devices in the specified path and returns all found devices
     *
     * @param FsPathInterface $path
     * @param int|bool        $recurse
     *
     * @return IteratorInterface
     */
    public static function scan(FsPathInterface $path, int|bool $recurse = false): IteratorInterface
    {
        FsDirectory::new($path)->each(function ($file) {
            show($file);
        });
    }


    /**
     * Returns all mounted LUKS devices
     *
     * @return IteratorInterface
     */
    public static function getMounted(): IteratorInterface
    {
        $return  = [];
        $devices = FsMountedStorageDevices::new()->scan();

        foreach ($devices as $device) {
            if (str_starts_with($device, 'dm-uuid-CRYPT-LUKS2-')) {
                $return[] = $device;
            }
        }

        return new Iterator($return);
    }
}