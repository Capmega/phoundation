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
     * @var BtrfsFilesystemInterface $_filesystem
     */
    protected BtrfsFilesystemInterface $_filesystem;


    /**
     * BtrfsSubvolume class constructor
     *
     * @param BtrfsFilesystemInterface $_filesystem
     * @param PhoPathInterface|null    $_path
     */
    public function __construct(BtrfsFilesystemInterface $_filesystem, ?PhoPathInterface $_path = null)
    {
        parent::__construct();
    }


    /**
     * Returns new static object
     *
     * @param BtrfsFilesystemInterface $_filesystem
     * @param PhoPathInterface|null    $_path
     *
     * @return static
     */
    public static function new(BtrfsFilesystemInterface $_filesystem, ?PhoPathInterface $_path = null): static
    {
        return new static ($_filesystem, $_path);
    }


    /**
     * Returns the BtrfsFilesystem object for this BtrsSubvolume object
     *
     * @return BtrfsFilesystemInterface
     */
    public function getFilesystemObject(): BtrfsFilesystemInterface
    {
        return $this->_filesystem;
    }


    /**
     * Sets the BtrfsFilesystem object for this BtrsSubvolume object
     *
     * @param BtrfsFilesystemInterface $_filesystem
     *
     * @return BtrfsSubvolume
     */
    public function setFilesystemObject(BtrfsFilesystemInterface $_filesystem): static
    {
        $this->_filesystem = $_filesystem;
        return $this;
    }


    /**
     * Generates a new subvolume
     *
     * @param IteratorInterface|array|int|null $group_id
     *
     * @return static
     */
    public function create(IteratorInterface|array|int|null $group_id = null): static
    {
        $this->_path->getDirectoryObject()->ensure();
        $this->_process->addArguments(['subvolume', 'create']);

        if ($group_id) {
            foreach (Arrays::force($group_id) as $group) {
                $this->_process->addArguments(['-i', $group]);
            }
        }

        $this->_process->executeReturnIterator();
        return $this;
    }


    /**
     * Deletes the current subvolume
     *
     * @param IteratorInterface|array|int|null $group_id
     *
     * @return static
     */
    public function delete(IteratorInterface|array|int|null $group_id = null): static
    {
        $this->_path->getDirectoryObject()->ensure();
        $this->_process->addArguments(['subvolume', 'create']);

        if ($group_id) {
            foreach (Arrays::force($group_id) as $group) {
                $this->_process->addArguments(['-i', $group]);
            }
        }

        $this->_process->executeReturnIterator();
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
        $_return = $this->_process->addArguments(['subvolume', 'list'])
                                    ->executeReturnIterator()
                                    ->removeKeys(0);

        foreach ($_return as $key => $device) {
            $device = Strings::from($device, ':');
            $device = trim($device);

            $_return->set($device, $key);
        }

        return $_return;
    }


    /**
     * Returns true if the current source directory for this object is a BTRFS device, false otherwise
     *
     * @return bool
     */
    public function isSubvolume(): bool
    {
        return $this->list()->valueExists($this->_path);
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
            ':path' => $this->_path,
        ]));
    }
}
