<?php

/**
 * Command fs shred
 *
 * Will shred (securely delete) the specified file by overwriting it multiple times with random data, then deleting it
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */

declare(strict_types=1);

use Phoundation\Cli\CliCommand;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsPath;
use Phoundation\Filesystem\FsRestrictions;

$restrictions = FsRestrictions::getWritable('/', 'command fs shred');

CliDocumentation::setAutoComplete([
    'arguments' => [
        '-p,--passes' => true,
        '-r,--random' => false,
    ],
    'positions' => [
        '0' => [
            'word'   => function ($word) use ($restrictions) {
                return FsDirectory::new(FsDirectory::getFilesystemRoot())->scan($word . '*');
            },
            'noword' => function () use ($restrictions) {
                return FsDirectory::new(FsDirectory::getFilesystemRoot())->scan('*');
            },
        ],
    ]
]);

CliDocumentation::setUsage('./pho fs shred PATH
./pho fs shred --random PATH');

CliDocumentation::setHelp('This command will count the sizes of all the files in the specified path recursively and
display the amount found



ARGUMENTS


PATH                                    The path of which the size needs to be calculated 


[-p,--passes PASSES]                    The amount of times the file should be overwritten before deleting it 
                                        (1-100 [3])


[-r,--random]                           If specified will overwrite the file blocks randomly, instead of linearly');


// Get the arguments
$argv = ArgvValidator::new()
    ->select('path')->isDirectory(FsDirectory::getFilesystemRoot())
    ->select('-r,--random')->isOptional(false)->isBoolean()
    ->select('-p,--passes', true)->isOptional(false)->isInteger()->isBetween(1, 100)
    ->validate();


// Shred the specified file
FsPath::newExisting($argv['path'], FsRestrictions::getWritable('/'))->shred($argv['passes'], $argv['random']);
