<?php

/**
 * Class PhoMountedStorageDevices
 *
 * This class represents the directory "/dev/disk/by-id/" and contains all mounted storage devices
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Data\Traits\TraitStaticMethodNew;


class PhoMountedStorageDevices extends PhoDirectoryCore
{
    use TraitStaticMethodNew;


    /**
     * PhoMountedStorageDevices class constructor
     *
     * @param bool $writable
     */
    public function __construct(bool $writable = false)
    {
        $this->source         = '/dev/disk/by-id/';
        $this->restrictions = PhoRestrictions::new($this->source, $writable, 'FsMountedStorageDevices');
    }
}
