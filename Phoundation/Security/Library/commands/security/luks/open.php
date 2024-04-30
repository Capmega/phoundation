<?php

/**
 * Script security/luks/open
 *
 * Allows the user to open a LUKS file and map it to the specified device
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliCommand;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Security\Luks\Device;

CliDocumentation::setUsage('./pho security luks open -f FILE -d DEVICE
echo PASSWORD | ./pho security luks open -f FILE -d DEVICE');

CliDocumentation::setHelp('This LUKS command will open the specified LUKS FILE and map it to the specified DEVICE

If the password was specified through STDIN (Through a pipe) that will be used. If not, the command will work in
interactive mode and ask for the password with a password prompt 


ARGUMENTS


-d, --device DEVICE                     The LUKS device to which this file should be mapped

-f, --file FILE                         The LUKS file to test the password sections against

-F, --force                             If specified, the command will 
');

CliDocumentation::setAutoComplete([
                                      'arguments' => [
                                          '-f,--file' => true,
                                          '-d,--device' => true,
                                          '-k,--key-file' => true,
                                      ],
                                  ]);


// Get arguments
$argv = ArgvValidator::new()
            ->select('-f,--file', true)->isFile('/', Restrictions::readonly('/'))
            ->select('-d,--device', true)->isVariable()
            ->select('-k,--key-file', true)->isOptional()->or('password')->isFile()
            ->validate();


// Get the LUKS file password
if (CliCommand::getStdInStream()) {
    $argv['password'] = CliCommand::getStdInStream();

} else {
    $argv['password'] = Cli::readPassword(tr('Enter the LUKS device password:'));
}


// Open the LUKS file
$device = Device::new($argv['file'], Restrictions::writable($argv['file']));

if (FORCE) {
    $device->luksClose(true);
}

$device->luksOpen($argv['password'], $argv['device']);
