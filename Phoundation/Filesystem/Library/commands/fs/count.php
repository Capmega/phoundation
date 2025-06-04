<?php

/**
 * Command fs count
 *
 * Will count all the files in the specified directory recursively and display the amount found
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Cli\CliCommand;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoRestrictions;


$restrictions = PhoRestrictions::newReadonlyObject('/');

CliDocumentation::setAutoComplete([
    'positions' => [
        '0' => [
            'word'   => function ($word) use ($restrictions) { return PhoDirectory::newFilesystemRootObject()->scan($word, '/.*?$/'); },
            'noword' => function ($word) use ($restrictions) { return PhoDirectory::newFilesystemRootObject()->scan($word, '/.*?$/'); },
        ],
    ]
]);

CliDocumentation::setUsage('./pho fs count PATH');

CliDocumentation::setHelp('This command will count all the files in the specified directory recursively and display  
the amount found


ARGUMENTS


PATH                                    The path that should have the files counted, must be a directory');

$argv = ArgvValidator::new()
    ->select('path')->sanitizeDirectory(PhoDirectory::newFilesystemRootObject())
    ->select('-h,--human-readable')->isOptional(false)->isBoolean()
    ->validate();

if ($argv['user_readable']) {
    CliCommand::echo(number_format(PhoDirectory::newExisting($argv['path'], PhoRestrictions::new('/'))->getCount()));

} else {
    CliCommand::echo(PhoDirectory::newExisting($argv['path'], PhoRestrictions::new('/'))->getCount());
}
