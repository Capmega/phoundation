<?php

/**
 * Command fs size
 *
 * Will count the sizes of all the files in the specified path recursively and display the amount found
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Cli\CliCommand;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoPath;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Utils\Numbers;


$restrictions = PhoRestrictions::newReadonly('/');

CliDocumentation::setAutoComplete([
    'positions' => [
        '0' => [
            'word'   => function ($word) use ($restrictions) {
                return PhoDirectory::new(PhoDirectory::newFilesystemRootObject())->scan($word . '*');
            },
            'noword' => function () use ($restrictions) {
                return PhoDirectory::new(PhoDirectory::newFilesystemRootObject())->scan('*');
            },
        ],
    ]
]);

CliDocumentation::setUsage('./pho fs size PATH');

CliDocumentation::setHelp('This command will count the sizes of all the files in the specified path recursively and
display the amount found


ARGUMENTS


PATH                                    The path of which the size needs to be calculated 


[-h,--human-readable]                   If specified, will display not the number of bytes as an integer number, but a 
                                        human-readable size instead. Instead of 1073741824 bytes, it will display 1GiB');


// Get arguments
$argv = ArgvValidator::new()
    ->select('path')->sanitizeDirectory(PhoDirectory::newFilesystemRootObject())
    ->select('-h,--human-readable')->isOptional(false)->isBoolean()
    ->validate();


// Get size for the specified path
if ($argv['human_readable']) {
    CliCommand::echo(
        Numbers::getHumanReadableBytes(PhoPath::newExisting($argv['path'], PhoRestrictions::new('/'))->getSize())
    );
} else {
    CliCommand::echo(PhoPath::newExisting($argv['path'], PhoRestrictions::new('/'))->getSize());
}
