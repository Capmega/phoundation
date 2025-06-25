<?php

/**
 * Class Device
 *
 * This is a storage device
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Devices\Storage;

use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoFileCore;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Os\Devices\Storage\Exception\StorageException;
use Phoundation\Os\Devices\Storage\Interfaces\DeviceInterface;
use Phoundation\Os\Processes\Commands\Cryptsetup;
use Phoundation\Os\Processes\Commands\Lsblk;
use Phoundation\Os\Processes\Commands\Mount;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Strings;


class Device extends PhoFile implements DeviceInterface
{
    /**
     * Device class constructor
     *
     * @return void
     */
    public function __construct(mixed $file = null, PhoRestrictionsInterface|array|string|null $restrictions = null)
    {
        if (!$restrictions) {
            $restrictions = PhoRestrictions::new('/dev/', false, 'default device');
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
        $this->source = Strings::ensureBeginsWith($this->source, '/dev/');
        if (
            !Lsblk::new()
                  ->isStorageDevice($this->source)
        ) {
            throw new StorageException(tr('Specified device ":device" is not a storage device', [
                ':device' => $this->source,
            ]));
        }
    }


    /**
     * Throws an exception if the device is not mounted
     *
     * @return static
     */
    public function checkMounted(): static
    {
        if (!$this->isMounted($this->source)) {
            throw StorageException::new(tr('The device is not mounted'));
        }

        return $this;
    }


    /**
     * Returns true if this device is mounted once or more
     *
     * @return bool
     */
    public function isMounted(): bool
    {
        return Mount::new()
                    ->deviceIsMounted($this->source);
    }


    /**
     * Scrambles this storage device with random data
     *
     * @return static
     */
    public function scramble(): static
    {
        $this->checkUnmounted();
        Process::new('dd', $this->o_restrictions)
               ->setSudo(true)
               ->setAcceptedExitCodes([
                   0,
                   1,
               ]) // Accept 1 if the DD process stopped due to disk full, which is expected
               ->setTimeout(0)
               ->addArguments([
                   'if=/dev/urandom',
                   'of=' . $this->source,
                   'bs=4096',
                   'status=progress',
               ])
               ->execute(EnumExecuteMethod::passthru);

        return $this;
    }


    /**
     * Throws an exception if the device is mounted
     *
     * @return static
     */
    public function checkUnmounted(): static
    {
        if ($this->isMounted($this->source)) {
            throw StorageException::new(tr('The device is mounted'));
        }

        return $this;
    }


    /**
     * Formats this device for encryption using LUKS
     *
     * @param string|null           $key
     * @param PhoFileInterface|null $key_file
     *
     * @return static
     */
    public function encrypt(?string $key, ?PhoFileInterface $key_file = null): static
    {
        $this->checkUnmounted();

        Cryptsetup::new()->luksFormat($this, $key, $key_file);

        return $this;
    }
}
