<?php

declare(strict_types=1);

namespace Phoundation\Os\Devices\Storage\Interfaces;

use Phoundation\Filesystem\Interfaces\PhoFileInterface;

interface DeviceInterface extends PhoFileInterface
{
    /**
     * Returns true if this device is mounted
     *
     * @return bool
     */
    public function isMounted(): bool;
}
