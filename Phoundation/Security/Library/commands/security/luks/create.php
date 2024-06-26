<?php

/**
 * Command security luks create
 *
 * This command will create an encrypted LUKS file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Security
 */

declare(strict_types=1);

use Phoundation\Cli\CliCommand;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Security\Luks\Device;

$restrictions = FsRestrictions::getWritable('/', 'command security luks create');

CliDocumentation::setAutoComplete([
    'arguments' => [
        '-b,--block-size' => true,
        '-i,--initialize' => false,
        '-s,--size'       => true,
        '-r,--randomized' => false,
    ],
    'positions' => [
        '0' => [
            'word'   => function ($word) use ($restrictions) {
                return FsDirectory::new('/', $restrictions)->scan($word . '*');
            },
            'noword' => function () use ($restrictions) {
                return FsDirectory::new('/', $restrictions)->scan('*');
            },
        ],
    ]
]);

CliDocumentation::setUsage('./pho tools files create PATH SIZE');

CliDocumentation::setHelp('This command will create a file with the specified size

WARNING:                                When using the --initialize option, this command may take a while to complete as
                                        it will be filling the entire allocated file with random data


ARGUMENTS


FILE                                    The file to be created 


-s,--size SIZE                          The size of the file to allocate, either in bytes or in human readable form
                                        e.g. 1024, 1KB, 1KiB, 1GB, etc.

[-i,--initialize]                       Will initialize the file after allocating it with random data.  

[-b,--block-size SIZE]                  Used in combination with --initialize. Will fill the file using block of the 
                                        specified size
                                        (1024 - 1073741824 [4096])

[-r,--randomized]                       Used in combination with --initialize. If specified will initialize the file 
                                        with blocks at random locations within the file');


// Get the arguments
$argv = ArgvValidator::new()
    ->select('file')->isFile('/', $restrictions, FORCE ? null : false)
    ->select('-s,--size', true)->isOptional(false)->sanitizeBytes()
    ->select('-i,--initialize')->isOptional(false)->isBoolean()
    ->select('-b,--block-size', true)->isOptional(4096)->sanitizeBytes()->isBetween(1024, 1_073_741_824)
    ->select('-r,--randomized')->isOptional(false)->isBoolean()
    ->validate();


// Get the LUKS file password
$argv['password'] = CliCommand::getStdInStreamOrPassword(tr('Enter the LUKS device password:'));


// Allocate the specified file
$file = FsFile::new($argv['file'], $restrictions)->allocate($argv['size']);


// Initialize the file
if ($argv['initialize']) {
    // Determine the initialization data
    $file->initialize('random', $argv['block_size'], $argv['randomized']);
}


// Format the device as a LUKS device
$file = Device::new($file)->luksFormat($argv['password']);


// Done!
Log::success(tr('Successfully created luks device file ":file"', [
    ':file' => $argv['file']
]));
