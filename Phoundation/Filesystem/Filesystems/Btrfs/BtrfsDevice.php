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
     * @param PhoPathInterface|null $o_path
     */
    public function __construct(?PhoPathInterface $o_path = null)
    {
        if (empty($o_path)) {
            throw new FilesystemBtrfsException(tr('No directory specified for BtrfsDevice class'));
        }

        parent::__construct($o_path);
    }


    /**
     * Returns new static object
     *
     * @param PhoPathInterface|null $o_path
     *
     * @return static
     */
    public static function new(?PhoPathInterface $o_path = null): static
    {
        return new static ($o_path);
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
        $o_return = $this->o_process->addArguments(['device', 'scan', $all ? 'all' : null])
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
    public function isDevice(): bool
    {
        return $this->scan()->valueExists($this->o_path);
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
            ':path' => $this->o_path,
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
        $return   = $this->o_process->addArguments(['device', 'stats', $this->o_path->getSource()])
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
        $this->o_process->addArguments(['device', 'stats', $this->o_path->getSource(), '--reset'])
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
        $this->o_process->addArguments(['device', 'remove', $this->o_path->getSource()])
                        ->executeNoReturn();

        return $this;
    }
}
