<?php

/**
 * Command fs size
 *
 * Will count the sizes of all the files in the specified path recursively and display the amount found
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Cli\CliCommand;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsPath;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Utils\Numbers;

$restrictions = FsRestrictions::getReadonly('/', 'command fs size');

CliDocumentation::setAutoComplete([
    'positions' => [
        '0' => [
            'word'   => function ($word) use ($restrictions) {
                return FsDirectory::new(FsDirectory::getFilesystemRootObject())->scan($word . '*');
            },
            'noword' => function () use ($restrictions) {
                return FsDirectory::new(FsDirectory::getFilesystemRootObject())->scan('*');
            },
        ],
    ]
]);

CliDocumentation::setUsage('./pho fs size PATH');

CliDocumentation::setHelp('This command will count the sizes of all the files in the specified path recursively and
display the amount found


ARGUMENTS


PATH                                    The path of which the size needs to be calculated 


[-h,--human-readable]                   If specified will display not the amount of bytes as an integer number, but a 
                                        human readable size instead. Instead of 1073741824 bytes, it will display 1GiB');


// Get arguments
$argv = ArgvValidator::new()
    ->select('path')->sanitizeDirectory(FsDirectory::getFilesystemRootObject())
    ->select('-h,--human-readable')->isOptional(false)->isBoolean()
    ->validate();


// Get size for the specified path
if ($argv['human_readable']) {
    CliCommand::echo(
        Numbers::getHumanReadableBytes(FsPath::newExisting($argv['path'], FsRestrictions::new('/'))->getSize())
    );
} else {
    CliCommand::echo(FsPath::newExisting($argv['path'], FsRestrictions::new('/'))->getSize());
}
