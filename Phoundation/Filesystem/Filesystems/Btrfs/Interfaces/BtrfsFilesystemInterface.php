<?php

namespace Phoundation\Filesystem\Filesystems\Btrfs\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;

interface BtrfsFilesystemInterface
{
    /**
     * Returns a key/value Iterator containing usage information for this filesystem
     *
     * @return IteratorInterface
     */
    public function getUsage(): IteratorInterface;


    /**
     * Returns a key/value Iterator containing usage information per device for this filesystem
     *
     * @return IteratorInterface
     */
    public function getDeviceUsage(): IteratorInterface;


    /**
     * Returns basic information on all BTRFS filesystems mounted, or all devices with a BTRFS filesystems under /dev
     *
     * @param bool $mounted
     *
     * @return IteratorInterface
     */
    public function getFilesystems(bool $mounted = true): IteratorInterface;


    /**
     * Scan or forget (unregister) devices of btrfs filesystems
     *
     * Executes "btrfs device add DIRECTORY"
     *
     * @param BtrfsDeviceInterface $device
     *
     * @return static
     */
    public function add(BtrfsDeviceInterface $device): static;
}
