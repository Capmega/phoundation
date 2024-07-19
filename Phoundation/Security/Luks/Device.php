<?php

/**
 * Class Device
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package   Phoundation\Security
 */

namespace Phoundation\Security\Luks;

use Phoundation\Accounts\Users\Password;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\Exception\FileWriteAccessDeniedException;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Os\Processes\Commands\Lsof;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Process;
use Phoundation\Security\Luks\Exception\DeviceAlreadyExistsException;
use Phoundation\Security\Luks\Exception\DeviceAlreadyOpenException;
use Phoundation\Security\Luks\Exception\DeviceNoKeyAvailableWithPassphraseException;
use Phoundation\Security\Luks\Exception\DeviceNotActiveException;
use Phoundation\Security\Luks\Exception\DeviceStillInUseException;
use Phoundation\Security\Luks\Exception\LuksNoOpenDeviceException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;

class Device extends FsFile
{
    /**
     * @var FsPathInterface|null $device_name
     */
    protected ?FsPathInterface $device_name = null;

    /**
     * The code used to open this LUKS file
     *
     * @var string|null
     */
    protected ?string $code = null;


    /**
     * Returns the LUKS device name if opened, NULL otherwise
     *
     * @return string|null
     */
    public function luksGetDeviceName(): ?string
    {
        return $this->device_name;
    }


    /**
     * Adds the specified passphrase to this LUKS file
     *
     * @param string $code
     *
     * @return static
     */
    public function luksAddKey(string $code): static
    {
        $this->luksCheckPath();

        Process::new('cryptsetup', $this->restrictions)
               ->addArguments(['luksAddKey', $this->source])
               ->setSudo(true)
               ->setPipeFrom($code)
               ->executeNoReturn();

        return $this;
    }


    /**
     * Removes the specified passphrase from this LUKS file
     *
     * @param string $code
     *
     * @return static
     */
    public function luksRemoveKey(string $code): static
    {
        $this->luksCheckPath();

        Process::new('cryptsetup', $this->restrictions)
               ->addArguments(['luksRemoveKey', $this->source])
               ->setSudo(true)
               ->setPipeFrom($code)
               ->executeNoReturn();

        return $this;
    }


    /**
     * Returns the header information from this LUKS file
     *
     * @return IteratorInterface
     */
    public function luksGetDump(): IteratorInterface
    {
        $this->luksCheckPath();

        return Process::new('cryptsetup', $this->restrictions)
                      ->addArguments(['luksDump', $this->source])
                      ->setSudo(true)
                      ->executeReturnIterator();
    }


    /**
     * Returns the header information from this LUKS file
     *
     * @param string            $password
     * @param EnumExecuteMethod $method
     *
     * @return static
     */
    public function luksFormat(string $password, EnumExecuteMethod $method = EnumExecuteMethod::noReturn): static
    {
        Password::checkStrong($password);

        $this->luksCheckPath();

        Log::action(tr('Formatting LUKS device file ":file"', [
            ':file' => $this->source,
        ]));

        Process::new('cryptsetup', $this->restrictions)
               ->addArguments(['luksFormat', '--batch-mode', $this->source])
               ->setSudo(true)
               ->setPipeFrom($password)
               ->execute($method);

        return $this;
    }


    /**
     * Creates a LUKS file with the specified size and password
     *
     * @param string|int $size
     * @param string     $password
     * @param bool       $initialize
     *
     * @return static
     */
    public function luksCreate(string|int $size, string $password, bool $initialize = true): static
    {
        Password::checkStrong($password);

        $this->luksCheckPath();

        // Allocate, initialize, and format
        $this->allocate($size);

        if ($initialize) {
            $this->initialize('random');
        }

        return $this->luksFormat($password);
    }


    /**
     * Returns the status for the mapped device
     *
     * @param string|null $device_name
     *
     * @return IteratorInterface
     */
    public function luksGetStatus(?string $device_name = null): IteratorInterface
    {
        if (!$device_name) {
            $device_name = $this->device_name;
        }

        return Process::new('cryptsetup', $this->restrictions)
                      ->addArguments(['status', $device_name])
                      ->setSudo(true)
                      ->executeReturnIterator(':');
    }


    /**
     * Returns true of this device is opened by LUKS
     *
     * @return bool
     */
    public function luksIsOpen(): bool
    {
        $mounts = Devices::getMounted();
        $path   = $this->getAbsolutePath(must_exist: false);

        foreach ($mounts as $mount) {
            $device = Strings::fromReverse($mount, '-');
            $status = $this->luksGetStatus($device);

            if ($status->get('loop', false) === $path) {
                return true;
            }
        }

        return false;
    }


    /**
     * Returns true if this file is a valid LUKS file
     *
     * @return bool
     */
    public function isLuks(): bool
    {
        $this->luksCheckPath();

        $result = Process::new('cryptsetup', $this->restrictions)
               ->addArguments(['isLuks', $this->source])
               ->setSudo(true)
               ->executeReturnString();

        return Strings::toBoolean($result);
    }


    /**
     * Returns the UUID for this LUKS file
     *
     * @return string
     */
    public function luksGetUuid(): string
    {
        $this->luksCheckPath();

        return Process::new('cryptsetup', $this->restrictions)
               ->addArguments(['luksUUID', $this->source])
               ->setSudo(true)
               ->executeReturnString();
    }


    /**
     * Opens the luks device file with the specified code and generates the specified device name in /dev/mapper/
     *
     * @param string                     $passphrase
     * @param FsPathInterface|string     $device_name
     * @param EnumExecuteMethod $method
     *
     * @return static
     */
    public function luksOpen(string $passphrase, FsPathInterface|string $device_name, EnumExecuteMethod $method = EnumExecuteMethod::noReturn): static
    {
        try {
            $this->luksCheckPath();

            if (empty($device_name)) {
                throw new ValidationFailedException(tr('No device name specified'));
            }

            if ($this->luksIsOpen()) {
                throw new DeviceAlreadyOpenException(tr('Cannot open file ":file" to map to device ":device" because the file is already opened and mapped by LUKS', [
                    ':file'   => $this->source,
                    ':device' => $device_name
                ]));
            }

            Process::new('cryptsetup', $this->restrictions)
                  ->addArguments(['luksOpen', $this->source, $device_name])
                  ->setSudo(true)
                  ->setPipeFrom($passphrase)
                  ->execute($method);

        } catch (ProcessFailedException $e) {
            if ($e->dataMatchesRegex('/Device .+? already exists/i')) {
                throw new DeviceAlreadyExistsException(tr('Cannot open LUKS file ":file" as device ":device", that device name already exists', [
                    ':file'   => $this->source,
                    ':device' => $device_name
                ]));
            }

            if ($e->dataMatchesRegex('/Device .+? does not exist or access denied./i')) {
                if ($this->exists()) {
                    throw new FileNotExistException(tr('Cannot open LUKS file ":file" as device ":device", the file does not exist', [
                        ':file'   => $this->source,
                        ':device' => $device_name
                    ]));
                }

                throw new FileWriteAccessDeniedException(tr('Cannot open LUKS file ":file" as device ":device", the file does not exist', [
                    ':file'   => $this->source,
                    ':device' => $device_name
                ]));
            }

            if ($e->dataContains('No key available with this passphrase')) {
                throw new DeviceNoKeyAvailableWithPassphraseException(tr('Cannot open LUKS file ":file" as device ":device", no crypt keys found for specified passphrase', [
                    ':file'   => $this->source,
                    ':device' => $device_name
                ]));
            }

            // Keep throwing
            throw $e;
        }

        $this->code        = $passphrase;
        $this->device_name = new FsFile('/dev/mapper/' . $device_name);

        Log::success(tr('Opened LUKS file ":file" and mapped it to device ":device"', [
            ':file'   => $this->source,
            ':device' => $device_name
        ]), 2);

        return $this;
    }


    /**
     * Closes the current luks device
     *
     * @param bool $force
     *
     * @return static
     */
    public function luksClose(bool $force = false): static
    {
        if (empty($this->device_name)) {
            if ($force) {
                // It's closed, what more do you want?
                Log::warning(tr('Will not close LUKS file ":file", the file is not open', [
                    ':file'   => $this->source,
                ]), 2);

                return $this;
            }

            throw new LuksNoOpenDeviceException(tr('Cannot close LUKS device for file ":file", no device has been opened yet for it', [
                ':file' => $this->source
            ]));
        }

        try {
            Process::new('cryptsetup', $this->restrictions)
                ->addArguments(['luksClose', $this->device_name])
                ->setSudo(true)
                ->executeNoReturn();

            Log::success(tr('Closed LUKS device ":device" that was mapped from file ":file"', [
                ':file'   => $this->source,
                ':device' => $this->device_name
            ]), 2);

            return $this;

        } catch (ProcessFailedException $e) {
            if ($e->dataMatchesRegex('/Device .+? is not active./i')) {
                // The device wasn't even open!
                if ($force) {
                    // That's fine, we were just making sure
                    Log::warning(tr('Will not close LUKS device ":device" that was mapped from file ":file", the device was not open', [
                        ':file'   => $this->source,
                        ':device' => $this->device_name
                    ]), 2);

                    return $this;
                }

                throw new DeviceNotActiveException(tr('Cannot close LUKS device ":device" for file ":file", the device is not currently active', [
                    ':file'   => $this->source,
                    ':device' => $this->device_name
                ]));
            }

            if ($e->dataMatchesRegex('/Device .+? is still in use./i')) {
                $processes = Lsof::new()->getForFile($this->device_name->getSource());

                throw DeviceStillInUseException::new(tr('Cannot close LUKS device ":device" for file ":file", the device is still in use', [
                    ':file'   => $this->source,
                    ':device' => $this->device_name
                ]))->addData(['processes' => $processes]);
            }

            throw $e;
        }
    }


    /**
     * Will try combinations of all specified keys and return the keys that could open this LUKS file
     *
     * @param IteratorInterface|array $keys
     *
     * @return IteratorInterface
     */
    public function luksTryPasswordSections(IteratorInterface|array $keys): IteratorInterface
    {
        // Try opening a UUID named device to ensure we won't try opening a device name that already exists
        $return       = [];
        $keys         = Arrays::force($keys);
        $keys         = Arrays::filterEmpty($keys);
        $count        = count($keys);
        $device       = Strings::getUuid();
        $combinations = static::getKeyCombinations($keys);

        if (empty($keys)) {
            throw OutOfBoundsException::new(tr('No keys specified'))->makeWarning();
        }

        Log::action(tr('Trying ":total" combinations for ":count" keys', [
            ':total' => count($combinations),
            ':count' => $count
        ]));

        foreach ($combinations as $id => $combination) {
            try {
                Log::action(tr('Trying key ":id"', [':id' => $id]), 3, echo_newline: false);
                $this->luksOpen($combination, $device)
                     ->luksClose();

                // Yay, this key worked!
                $return[] = $combination;
                Log::success(' ' . tr('[ Ok ]'), 3, echo_prefix: false);

            } catch (DeviceNoKeyAvailableWithPassphraseException) {
                // This key doesn't work, next!
                Log::error(' ' . tr('[ Failed ]'), 3, echo_prefix: false);
            }
        }

        return new Iterator($return);
    }


    /**
     * Returns an array with all key combinations
     *
     * @param array       $keys
     * @param array       $return
     * @param array       $used_keys
     * @param string|null $base
     *
     * @return array
     */
    public static function getKeyCombinations(array $keys, array &$return = [], array $used_keys = [], ?string $base = null): array
    {
        // Add all keys with the current base
        // Now add the next line, next line should not use the already used keys
        foreach ($keys as $key) {
            if (in_array($key, $used_keys)) {
                continue;
            }

            $return[] = $base . $key;
            $used_keys[$key] = $key;

            static::getKeyCombinations($keys, $return, $used_keys, $base . $key);
            unset($used_keys[$key]);
        }

        return $return;
    }


    /**
     * Checks if a path has been specified
     *
     * @return $this
     */
    protected function luksCheckPath(): static
    {
        if (empty($this->source)) {
            throw new OutOfBoundsException(tr('No LUKS file specified'));
        }

        return $this;
    }


    /**
     * Checks if a device has been specified
     *
     * @return $this
     */
    protected function luksCheckDevice(): static
    {
        if (empty($this->device)) {
            throw new OutOfBoundsException(tr('No LUKS device specified'));
        }

        return $this;
    }
}
