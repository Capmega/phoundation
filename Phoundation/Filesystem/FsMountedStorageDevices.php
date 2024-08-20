<?php

/**
 * Class FsMountedStorageDevices
 *
 * This class represents the directory "/dev/disk/by-id/" and contains all mounted storage devices
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */

namespace Phoundation\Filesystem;

use Phoundation\Data\Traits\TraitStaticMethodNew;


class FsMountedStorageDevices extends FsDirectoryCore
{
    use TraitStaticMethodNew;


    /**
     * FsMountedStorageDevices class constructor
     *
     * @param bool $writable
     */
    public function __construct(bool $writable = false)
    {
        $this->source         = '/dev/disk/by-id/';
        $this->restrictions = FsRestrictions::new($this->source, $writable, 'FsMountedStorageDevices');
    }
}
