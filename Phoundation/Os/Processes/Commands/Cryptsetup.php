<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Config;
use Phoundation\Core\Strings;
use Phoundation\Os\Devices\Storage\Device;
use Phoundation\Os\Processes\Commands\Exception\CommandsException;
use Phoundation\Os\Processes\Exception\ProcessFailedException;


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
     * @return void
     */
    public function luksFormat(Device|string $device, string $key = null): void
    {
        $device = Device::new($device)->getFile();

        $this
            ->setInternalCommand('cryptsetup')
            ->addArguments(['-y', '-v', 'luksFormat', $device])
            ->setTimeout(10)
            ->executePassthru();
   }
}
