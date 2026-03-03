<?php

/**
 * Class BtrfsDevice
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Filesystems\Btrfs;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Filesystem\Filesystems\Btrfs\Exception\FilesystemBtrfsException;
use Phoundation\Filesystem\Filesystems\Btrfs\Interfaces\BtrfsDeviceInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Utils\Strings;


class BtrfsDevice extends Btrfs implements BtrfsDeviceInterface
{
    /**
     * BtrFs class constructor
     *
     * @param PhoPathInterface|null $_path
     */
    public function __construct(?PhoPathInterface $_path = null)
    {
        if (empty($_path)) {
            throw new FilesystemBtrfsException(tr('No directory specified for BtrfsDevice class'));
        }

        parent::__construct($_path);
    }


    /**
     * Returns new static object
     *
     * @param PhoPathInterface|null $_path
     *
     * @return static
     */
    public static function new(?PhoPathInterface $_path = null): static
    {
        return new static ($_path);
    }


    /**
     * Scan or forget (unregister) devices of btrfs filesystems
     *
     * Executes "btrfs device scan"
     *
     * @param bool $all
     *
     * @return IteratorInterface
     */
    public function scan(bool $all = false): IteratorInterface
    {
        $_return = $this->_process->appendArguments(['device', 'scan', $all ? 'all' : null])
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
    public function isDevice(): bool
    {
        return $this->scan()->valueExists($this->_path);
    }


    /**
     * Throws an FilesystemBtrfsException if the current source directory for this object is not a BTRFS device
     *
     * @return static
     * @throws FilesystemBtrfsException
     */
    public function checkDevice(): static
    {
        if ($this->isDevice()) {
            return $this;
        }

        throw new FilesystemBtrfsException(tr('Specified path ":path" is not a BTRFS device.', [
            ':path' => $this->_path,
        ]));
    }


    /**
     * Returns device IO error statistics
     *
     * @return IteratorInterface
     */
    public function getStatistics(): IteratorInterface
    {
        $this->checkDevice();

        $iterator = [];
        $return   = $this->_process->appendArguments(['device', 'stats', $this->_path->getSource()])
                                    ->executeReturnIterator();

        foreach ($return as $stat) {
            $stat  = Strings::from($stat, '].');
            $key   = Strings::untilReverse($stat, ' ');
            $value = Strings::fromReverse($stat, ' ');

            $iterator[trim($key)] = trim($value);
        }

        return new Iterator($iterator);
    }


    /**
     * Resets device IO error statistics
     *
     * @return static
     */
    public function resetStatistics(): static
    {
        $this->_process->appendArguments(['device', 'stats', $this->_path->getSource(), '--reset'])
                        ->executeNoReturn();

        return $this;
    }


    /**
     * Remove a device from a filesystem
     *
     * Executes 'btrfs device remove DEVICE_PATH'
     *
     * @return static
     */
    public function remove(): static
    {
        $this->_process->appendArguments(['device', 'remove', $this->_path->getSource()])
                        ->executeNoReturn();

        return $this;
    }
}
