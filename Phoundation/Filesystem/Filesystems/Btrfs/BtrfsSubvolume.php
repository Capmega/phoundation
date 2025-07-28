<?php

/**
 * Class BtrfsSubvolume
 *
 * This class manages BTRFS subvolumes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Filesystems\Btrfs;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\Filesystems\Btrfs\Exception\FilesystemBtrfsException;
use Phoundation\Filesystem\Filesystems\Btrfs\Interfaces\BtrfsFilesystemInterface;
use Phoundation\Filesystem\Filesystems\Btrfs\Interfaces\BtrfsSubVolumeInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;


class BtrfsSubvolume extends Btrfs implements BtrfsSubVolumeInterface
{
    /**
     * Tracks the filesystem for this subvolume
     *
     * @var BtrfsFilesystemInterface $o_filesystem
     */
    protected BtrfsFilesystemInterface $o_filesystem;


    /**
     * BtrfsSubvolume class constructor
     *
     * @param BtrfsFilesystemInterface $o_filesystem
     * @param PhoPathInterface|null    $o_path
     */
    public function __construct(BtrfsFilesystemInterface $o_filesystem, ?PhoPathInterface $o_path = null)
    {
        parent::__construct();
    }


    /**
     * Returns new static object
     *
     * @param BtrfsFilesystemInterface $o_filesystem
     * @param PhoPathInterface|null    $o_path
     *
     * @return static
     */
    public static function new(BtrfsFilesystemInterface $o_filesystem, ?PhoPathInterface $o_path = null): static
    {
        return new static ($o_filesystem, $o_path);
    }


    /**
     * Returns the BtrfsFilesystem object for this BtrsSubvolume object
     *
     * @return BtrfsFilesystemInterface
     */
    public function getFilesystemObject(): BtrfsFilesystemInterface
    {
        return $this->o_filesystem;
    }


    /**
     * Sets the BtrfsFilesystem object for this BtrsSubvolume object
     *
     * @param BtrfsFilesystemInterface $o_filesystem
     *
     * @return BtrfsSubvolume
     */
    public function setFilesystemObject(BtrfsFilesystemInterface $o_filesystem): static
    {
        $this->o_filesystem = $o_filesystem;
        return $this;
    }


    /**
     * Generates a new subvolume
     *
     * @param IteratorInterface|array|int|null $group_id
     *
     * @return $this
     */
    public function create(IteratorInterface|array|int|null $group_id = null): static
    {
        $this->o_path->getDirectoryObject()->ensure();
        $this->o_process->addArguments(['subvolume', 'create']);

        if ($group_id) {
            foreach (Arrays::force($group_id) as $group) {
                $this->o_process->addArguments(['-i', $group]);
            }
        }

        $this->o_process->executeReturnIterator();
        return $this;
    }


    /**
     * Deletes the current subvolume
     *
     * @param IteratorInterface|array|int|null $group_id
     *
     * @return $this
     */
    public function delete(IteratorInterface|array|int|null $group_id = null): static
    {
        $this->o_path->getDirectoryObject()->ensure();
        $this->o_process->addArguments(['subvolume', 'create']);

        if ($group_id) {
            foreach (Arrays::force($group_id) as $group) {
                $this->o_process->addArguments(['-i', $group]);
            }
        }

        $this->o_process->executeReturnIterator();
        return $this;
    }


    /**
     * List subvolumes and snapshots in the filesystem.
     *
     * Executes "btrfs subvolume list"
     *
     * @return IteratorInterface
     */
    public function list(): IteratorInterface
    {
        $o_return = $this->o_process->addArguments(['subvolume', 'list'])
                                    ->executeReturnIterator()
                                    ->removeKeys(0);

        foreach ($o_return as $key => $device) {
            $device = Strings::from($device, ':');
            $device = trim($device);

            $o_return->set($device, $key);
        }

        return $o_return;
    }


    /**
     * Returns true if the current source directory for this object is a BTRFS device, false otherwise
     *
     * @return bool
     */
    public function isSubvolume(): bool
    {
        return $this->list()->valueExists($this->o_path);
    }


    /**
     * Throws an FilesystemBtrfsException if the current source directory for this object is not a BTRFS device
     *
     * @return static
     * @throws FilesystemBtrfsException
     */
    public function checkSubvolume(): static
    {
        if ($this->isSubvolume()) {
            return $this;
        }

        throw new FilesystemBtrfsException(tr('Specified path ":path" is not a BTRFS subvolume.', [
            ':path' => $this->o_path,
        ]));
    }


}
