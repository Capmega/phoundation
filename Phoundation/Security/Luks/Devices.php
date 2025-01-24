<?php

/**
 * Class Devices
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */


declare(strict_types=1);

namespace Phoundation\Security\Luks;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\PhoMountedStorageDevices;


class Devices
{
    /**
     * Scans for LUKS devices in the specified path and returns all found devices
     *
     * @param PhoPathInterface $path
     * @param int|bool         $recurse
     *
     * @return IteratorInterface
     */
    public static function scan(PhoPathInterface $path, int|bool $recurse = false): IteratorInterface
    {
        PhoDirectory::new($path)->each(function ($file) {
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
        $devices = PhoMountedStorageDevices::new()->scan();

        foreach ($devices as $device) {
            if (str_starts_with($device->getSource(), 'dm-uuid-CRYPT-LUKS2-')) {
                $return[] = $device;
            }
        }

        return new Iterator($return);
    }
}
