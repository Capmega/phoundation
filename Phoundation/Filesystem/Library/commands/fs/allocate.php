<?php

/**
 * Command fs create
 *
 * This command will create a file with the specified size
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;

$restrictions = FsRestrictions::getWritable('/', 'command fs allocate');

CliDocumentation::setAutoComplete([
    'arguments' => [
        '-b,--block-size' => true,
        '-i,--initialize' =>  ['random', 'zeroes', 'ones'],
        '-s,--size'       => true,
        '-r,--randomized' => false,
    ],
    'positions' => [
        '0' => [
            'word'   => function ($word) use ($restrictions) { return FsDirectory::new(FsDirectory::getFilesystemRootObject())->scan($word . '*'); },
            'noword' => function ()      use ($restrictions) { return FsDirectory::new(FsDirectory::getFilesystemRootObject())->scan('*'); },
        ],
    ]
]);

CliDocumentation::setUsage('./pho fs create PATH SIZE');

CliDocumentation::setHelp('This command will create a file with the specified size


ARGUMENTS


FILE                                    The file to be created 


-s,--size SIZE                          The size of the file to allocate, either in bytes or in human readable form
                                        e.g. 1024, 1KB, 1KiB, 1GB, etc.

[-i,--initialize TYPE]                  Will initialize the file after allocating it. The file will be initialized by 
                                        filling it with data. TYPE specified what data this will be. TYPE can either be 
                                        "zero" which will file the file with zero bytes, "random" which will fill the 
                                        file with random data, or any other string which then will be used repeatedly 
                                        until the file is full.  

[-b,--block-size SIZE]                  Used in combination with --initialize. Will fill the file using block of the 
                                        specified size
                                        (1024 - 1073741824 [4096])

[-r,--randomized]                       Used in combination with --initialize. If specified will initialize the file 
                                        with blocks at random locations within the file');


// Get the arguments
$argv = ArgvValidator::new()
                     ->select('file')->sanitizeFile(FsDirectory::getFilesystemRootObject(), (FORCE ? null : false))
                     ->select('-s,--size', true)->isOptional(false)->sanitizeBytes()
                     ->select('-i,--initialize', true)->isOptional(false)->isString()->hasMinCharacters(1)->hasMaxCharacters(1_073_741_824)
                     ->select('-b,--block-size', true)->isOptional(4096)->sanitizeBytes()->isBetween(100, 1_073_741_824)
                     ->select('-r,--randomized')->isOptional(false)->isBoolean()
                     ->validate();


// Allocate the specified file
$file = FsFile::new($argv['file'], $restrictions)->allocate($argv['size']);


// Initialize the file
if ($argv['initialize']) {
    // Determine the initialization data
    $file->initialize($argv['initialize'], $argv['block_size'], $argv['randomized']);
}
