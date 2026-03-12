<?php

namespace Phoundation\Filesystem\Filesystems\Btrfs\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\Filesystems\Btrfs\Exception\BtrfsException;

interface BtrfsDeviceInterface
{
    /**
     * Scan or forget (unregister) devices of btrfs filesystems
     *
     * Executes "btrfs device scan"
     *
     * @param bool $all
     *
     * @return IteratorInterface
     */
    public function scan(bool $all = false): IteratorInterface;


    /**
     * Returns true if the current source directory for this object is a BTRFS device, false otherwise
     *
     * @return bool
     */
    public function isDevice(): bool;


    /**
     * Throws an FilesystemBtrfsException if the current source directory for this object is not a BTRFS device
     *
     * @return static
     * @throws BtrfsException
     */
    public function checkDevice(): static;


    /**
     * Returns device IO error statistics
     *
     * @return IteratorInterface
     */
    public function getStatistics(): IteratorInterface;


    /**
     * Resets device IO error statistics
     *
     * @return static
     */
    public function resetStatistics(): static;


    /**
     * Remove a device from a filesystem
     *
     * Executes 'btrfs device remove DEVICE_PATH'
     *
     * @return static
     */
    public function remove(): static;
}
