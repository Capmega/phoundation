<?php

/**
 * Command fs initalize
 *
 * Will initalize the specified file by overwriting it (multiple times) with data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Tools
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsPath;
use Phoundation\Filesystem\FsRestrictions;

$restrictions = FsRestrictions::getWritable('/', 'command fs initalize');

CliDocumentation::setAutoComplete([
    'arguments' => [
        '-r,--random' => false,
        '-d,--data'   => ['random', 'zeroes', 'ones'],
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

CliDocumentation::setUsage('./pho fs initalize FILE
./pho fs initalize -rd random FILE');

CliDocumentation::setHelp('This command will initialize the specified file by overwriting it (multiple times) with 
data


ARGUMENTS


PATH                                    The path of which the size needs to be calculated 

[-b,--block-size SIZE]                  Used in combination with --initialize. Will fill the file using block of the 
                                        specified size
                                        (1024 - 1073741824 [4096])

[-d,--data DATA]                        The data with which the file will be initialized. Either one of "random" (will 
                                        fill the file with random data), "zeroes" (will fill the file with chr(0) 
                                        bytes), or "ones" (Will will the file with chr(255) bytes) or any other data 
                                        string that will be repeated over and over until the file is full
                                        ["zero" (or "zeros"), "one" (or "ones"), "random", "......."]

[-r,--randomized]                       If specified will overwrite the file blocks randomly, instead of linearly');


// Get the arguments
$argv = ArgvValidator::new()
    ->select('path')->isFile(FsDirectory::getFilesystemRoot(true))
    ->select('-r,--randomized')->isOptional(false)->isBoolean()
    ->select('-b,--block-size', true)->isOptional(4096)->sanitizeBytes()->isBetween(100, 1_073_741_824)
    ->select('-d,--data', true)->isString()->hasMinCharacters(1)->hasMaxCharacters(1_073_741_824)
    ->validate();


// Initialize the specified file
FsPath::newExisting($argv['path'], FsRestrictions::getWritable('/'))
      ->initialize($argv['data'], $argv['block_size'], $argv['randomized']);
