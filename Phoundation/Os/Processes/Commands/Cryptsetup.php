<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Config;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Os\Devices\Storage\Device;
use Phoundation\Os\Processes\Commands\Exception\CommandsException;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Process;


/**
 * Class Cryptsetup
 *
 * This class contains various "cryptsetup" commands
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class Cryptsetup extends Command
{
    /**
     * Creates the specified directory
     *
     * @param Device|string $device
     * @param string|null $key
     * @param string|null $file_key
     * @return void
     */
    public function luksFormat(Device|string $device, string $key = null, string $file_key = null): void
    {
        $device = Device::new($device)->getFile();

        if ($key) {
            if ($file_key) {
                throw new OutOfBoundsException(tr('Cannot luks format device ":device", both key and file key specified', [
                    ':device' => $device
                ]));
            }

            Process::new('echo')
                ->addArgument($key)
                ->setPipe($this
                    ->setInternalCommand('cryptsetup')
                    ->setSudo(true)
                    ->addArguments(['-q', '-v', 'luksFormat', $device])
                    ->setTimeout(10))
                ->executePassthru();
        } else {
            if (!$file_key) {
                throw new OutOfBoundsException(tr('Cannot luks format device ":device", no key or file key specified', [
                    ':device' => $device
                ]));
            }

            $this
                ->setInternalCommand('cryptsetup')
                ->setSudo(true)
                ->addArguments(['-q', '-v', 'luksFormat', $device, '--key-file', $file_key])
                ->setTimeout(10)
                ->executePassthru();
        }
   }
}
