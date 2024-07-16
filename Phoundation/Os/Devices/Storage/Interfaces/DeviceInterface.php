<?php

declare(strict_types=1);

namespace Phoundation\Os\Devices\Storage\Interfaces;

use Phoundation\Filesystem\Interfaces\FileInterface;

interface DeviceInterface extends FileInterface
{
    /**
     * Returns true if this device is mounted
     *
     * @return bool
     */
    public function isMounted(): bool;
}
