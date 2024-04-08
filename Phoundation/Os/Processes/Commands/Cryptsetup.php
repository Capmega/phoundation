<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Os\Devices\Storage\Device;
use Phoundation\Os\Processes\Process;

/**
 * Class Cryptsetup
 *
 * This class contains various "cryptsetup" commands
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
class Cryptsetup extends Command
{
    /**
     * Creates the specified directory
     *
     * @param Device|string $device
     * @param string|null   $key
     * @param string|null   $key_file
     *
     * @return void
     */
    public function luksFormat(Device|string $device, string $key = null, string $key_file = null): void
    {
        $device = Device::new($device)
                        ->getPath();
        if ($key) {
            if ($key_file) {
                throw new OutOfBoundsException(tr('Cannot luks format device ":device", both key and file key specified', [
                    ':device' => $device,
                ]));
            }
            Log::action(tr('Formatting device ":device" with LUKS encryption, this may take a few seconds', [
                ':device' => $device,
            ]));
            Process::new('echo')
                   ->addArgument($key)
                   ->setPipe($this->setCommand('cryptsetup')
                                  ->setSudo(true)
                                  ->addArguments([
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
                 ->addArguments([
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
