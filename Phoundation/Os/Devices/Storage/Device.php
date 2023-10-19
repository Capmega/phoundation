<?php

namespace Phoundation\Os\Devices\Storage;

use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Interfaces\FileInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Devices\Storage\Exception\StorageException;
use Phoundation\Os\Devices\Storage\Interfaces\DeviceInterface;
use Phoundation\Os\Processes\Commands\Cryptsetup;
use Phoundation\Os\Processes\Commands\Lsblk;
use Phoundation\Os\Processes\Commands\Mount;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Process;


/**
 * Class Device
 *
 * This is a storage device
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class Device extends File implements DeviceInterface
{
    /**
     * Device class constructor
     *
     * @return void
     */
    public function __construct(mixed $file = null, RestrictionsInterface|array|string|null $restrictions = null)
    {
        if (!$restrictions) {
            $restrictions = Restrictions::new( Restrictions::new('/dev/'), false, 'default device');
        }

        parent::__construct($file, $restrictions);

        $this->checkDeviceFile();
    }


    /**
     * Checks the storage device file and throws exception in case of issues
     *
     * @return void
     * @throws StorageException
     */
    protected function checkDeviceFile(): void
    {
        $this->file = Strings::startsWith($this->file, '/dev/');

        if (!Lsblk::new()->isStorageDevice($this->file)) {
            throw new StorageException(tr('Specified device ":device" is not a storage device', [
                ':device' => $this->file
            ]));
        }
    }


    /**
     * Returns true if this device is mounted once or more
     *
     * @return bool
     */
    public function isMounted(): bool
    {
        return Mount::new()->deviceIsMounted($this->file);
    }


    /**
     * Throws an exception if the device is not mounted
     *
     * @return static
     */
    public function checkMounted(): static
    {
        if (!$this->isMounted($this->file)) {
            throw StorageException::new(tr('The device is not mounted'));
        }

        return $this;
    }


    /**
     * Throws an exception if the device is mounted
     *
     * @return static
     */
    public function checkUnmounted(): static
    {
        if ($this->isMounted($this->file)) {
            throw StorageException::new(tr('The device is mounted'));
        }

        return $this;
    }


    /**
     * Scrambles this storage device with random data
     *
     * @return $this
     */
    public function scramble(): static
    {
        $this->checkUnmounted();

        Process::new('dd', $this->restrictions)
            ->setSudo(true)
            ->setTimeout(0)
            ->addArguments(['if=/dev/urandom', 'of=' . $this->file, 'bs=4096', 'status=progress'])
            ->execute(EnumExecuteMethod::passthru);

        return $this;
    }


    /**
     * Formats this device for encryption using LUKS
     *
     * @param string $key
     * @return $this
     */
    public function encrypt(string $key): static
    {
        $this->checkUnmounted();

        Cryptsetup::new($this->restrictions)->luksFormat($this, $key);

        return $this;
    }
}