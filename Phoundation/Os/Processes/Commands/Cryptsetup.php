<?php

/**
 * Class Cryptsetup
 *
 * This class contains various "cryptsetup" commands
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Os\Devices\Storage\Device;
use Phoundation\Os\Devices\Storage\Interfaces\DeviceInterface;
use Phoundation\Os\Processes\Process;


class Cryptsetup extends Command
{
    /**
     * Formats the specified LUKS device
     *
     * @param DeviceInterface       $device
     * @param string|null           $key
     * @param PhoFileInterface|null $key_file
     *
     * @return void
     */
    public function luksFormat(DeviceInterface $device, ?string $key = null, ?PhoFileInterface $key_file = null): void
    {
        // Get restrictions from the specified device
        $this->setRestrictionsObject($device->getRestrictionsObject());

        $device = Device::new($device)->getSource();

        if ($key) {
            if ($key_file) {
                throw new OutOfBoundsException(tr('Cannot luks format device ":device", both key and file key specified', [
                    ':device' => $device,
                ]));
            }

            Log::action(ts('Formatting device ":device" with LUKS encryption, this may take a few seconds', [
                ':device' => $device,
            ]));

            Process::new('echo')
                   ->appendArgument($key)
                   ->setPipe($this->setCommand('cryptsetup')
                                  ->setSudo(true)
                                  ->appendArguments([
                                      '-q',
                                      '-v',
                                      'luksFormat',
                                      $device,
                                  ])
                                  ->setTimeout(30))
                   ->executePassthru();

        } else {
            if (!$key_file) {
                throw new OutOfBoundsException(tr('Cannot luks format device ":device", no key or file key specified', [
                    ':device' => $device,
                ]));
            }
            $this->setCommand('cryptsetup')
                 ->setSudo(true)
                 ->appendArguments([
                     '-q',
                     '-v',
                     'luksFormat',
                     $device,
                     '--key-file',
                     $key_file,
                 ])
                 ->setTimeout(10)
                 ->executePassthru();
        }
    }
}
