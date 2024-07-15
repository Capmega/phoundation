<?php

/**
 * Command fs duplicates find
 *
 * Will search the specified path for duplicate files and display a list of results
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */

declare(strict_types=1);

use Phoundation\Cli\CliCommand;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;

$restrictions = FsRestrictions::getReadonly('/', 'command fs duplicates find');

CliDocumentation::setAutoComplete([
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

CliDocumentation::setUsage('./pho fs duplicates find PATH');

CliDocumentation::setHelp('This command will search the specified path for duplicate files and display the results 

All files are first compared by size. If the size matches, then a sha1_file comparison is done. If the hash matches, the
files are considered equal


ARGUMENTS


PATH                                    The path that should be scanned

[-r,--recursive LEVELS]                 If specified, will search recursively LEVELS levels deep
                                        (1 ... 1_000_000 [1_000_000])

[-m,--max-size SIZE]                    Maximum size in bytes for a file to be checked. Files larger than this will be 
                                        ignored. Allows numeric, or byte notation like 1GB, 1GiB, etc. Use 0 to process 
                                        all sizes
                                        (1KiB ... 1PiB [1GiB])');


// Get arguments
$argv = ArgvValidator::new()
    ->select('path')->isDirectory(FsDirectory::getFilesystemRoot())
    ->select('-r,--recursive', true)->isOptional(0)->isInteger()->isPositive()
    ->select('-m,--max-size', true)->isOptional(1_073_741_824)->sanitizeBytes()
    ->validate();


// Scan for duplicates and display them
$duplicates = FsDirectory::new($argv['path'], $restrictions)->getDuplicateFiles($argv['recursive'], $argv['max_size']);

if ($duplicates->getCount()) {
    Log::success(tr('Found ":count" duplicate files', [
        ':count' => $duplicates->getCount()
    ]));

    foreach ($duplicates as $hash => $files) {
        Log::notice($hash);
        Log::debug($files, echo_header: false);
        Log::cli(' ');
    }

} else {
    Log::success('No duplicate files found');
}
