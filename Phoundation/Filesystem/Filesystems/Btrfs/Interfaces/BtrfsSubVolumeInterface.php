<?php

namespace Phoundation\Filesystem\Filesystems\Btrfs\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\Filesystems\Btrfs\BtrfsSubvolume;
use Phoundation\Filesystem\Filesystems\Btrfs\Exception\FilesystemBtrfsException;

interface BtrfsSubVolumeInterface
{
    /**
     * Generates a new subvolume
     *
     * @param IteratorInterface|array|int|null $group_id
     *
     * @return $this
     */
    public function create(IteratorInterface|array|int|null $group_id = null): static;


    /**
     * Generates a new subvolume
     *
     * @param IteratorInterface|array|int|null $group_id
     *
     * @return $this
     */
    public function delete(IteratorInterface|array|int|null $group_id = null): static;


    /**
     * List subvolumes and snapshots in the filesystem.
     *
     * Executes "btrfs subvolume list"
     *
     * @return IteratorInterface
     */
    public function list(): IteratorInterface;


    /**
     * Returns true if the current source directory for this object is a BTRFS device, false otherwise
     *
     * @return bool
     */
    public function isSubvolume(): bool;


    /**
     * Throws an FilesystemBtrfsException if the current source directory for this object is not a BTRFS subvolume
     *
     * @return static
     * @throws FilesystemBtrfsException
     */
    public function checkSubvolume(): static;
}
