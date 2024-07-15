<?php

/**
 * Command security luks try
 *
 * Allows the user to try various LUKS password sections on the specified LUKS file to see what password(s) work
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Security
 */

declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliCommand;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Security\Luks\Device;

$restrictions = FsRestrictions::getReadonly('/', tr('security luks try'));

CliDocumentation::setUsage('./pho security luks try -f FILE
echo "SECTION SECTION SECTION" | ./pho security luks try -f FILE');

CliDocumentation::setHelp('This LUKS command accepts various password sections and will try to find complete 
passwords comprised of any possible combination of all sections

If password sections were specified through STDIN (Through a pipe) those will be used. If not, the command will work in
interactive mode and ask for the sections with a password prompt 

Sections must be specified with a space separator

Empty sections will be quietly ignored


ARGUMENTS


-f, --file FILE                         The LUKS file to test the password sections against');

CliDocumentation::setAutoComplete([
      '-f,--file' => [
          'arguments' => [
              'word'   => function ($word) use ($restrictions) {
                 return FsDirectory::new(FsDirectory::getFilesystemRoot())->scan($word . '*');
              },
              'noword' => function () use ($restrictions) {
                  return FsDirectory::new(FsDirectory::getFilesystemRoot())->scan('*');
              },
          ],
      ],
]);


// Get arguments
$argv = ArgvValidator::new()
            ->select('-f,--file', true)->isFile(FsDirectory::getFilesystemRoot())
            ->validate();


// Get the LUKS file password sections
$argv['sections'] = CliCommand::getStdInStreamOrPassword(tr('Enter the known LUKS device password sections (space separated):'));
$argv['sections'] = explode(' ', $argv['sections']);


// Open the LUKS file
$device = Device::new($argv['file'], FsRestrictions::getWritable($argv['file']));

if (FORCE) {
    $device->luksClose(true);
}


// Display found passwords
$passwords = $device->luksTryPasswordSections($argv['sections']);

if ($passwords->getCount()) {
    Log::information(tr('Found following passwords for the specified sections'));

    foreach ($passwords as $password) {
        Log::cli($password);
    }

} else {
    Log::warning(tr('No passwords found for the specified sections'));
}
