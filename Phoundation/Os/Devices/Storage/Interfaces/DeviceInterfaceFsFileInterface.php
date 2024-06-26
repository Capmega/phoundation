<?php

declare(strict_types=1);

namespace Phoundation\Os\Devices\Storage\Interfaces;

use Phoundation\Filesystem\Interfaces\FsFileInterface;

/**
 * interface DeviceInterfaceFsFileInterface
 *
 * This is a storage device
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
interface DeviceInterfaceFsFileInterface extends FsFileInterface
{
    /**
     * Returns true if this device is mounted
     *
     * @return bool
     */
    public function isMounted(): bool;
}
