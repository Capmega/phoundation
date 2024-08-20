<?php

/**
 * Command security luks close
 *
 * Allows the user to close a LUKS device
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Security
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Security\Luks\Device;


CliDocumentation::setUsage('./pho security luks close -d DEVICE
echo PASSWORD | ./pho security luks close -d DEVICE');

CliDocumentation::setHelp('This LUKS command will close the specified LUKS DEVICE


ARGUMENTS


-d, --device DEVICE                     The LUKS device that will be closed');

CliDocumentation::setAutoComplete([
    'arguments' => [
        '-d,--device' => true,
    ],
]);


// Close the LUKS file
$device = Device::new($argv['file'], FsRestrictions::getWritable($argv['file']))->luksClose(FORCE);
