<?php

/**
 * Command fs info
 *
 * Will info all the files in the specified directory recursively and display the amount found
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

$restrictions = FsRestrictions::getReadonly('/', 'command fs info');

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

CliDocumentation::setUsage('./pho fs info PATH
./pho fs info PATH -h');

CliDocumentation::setHelp('This command will display information about the specified file or directory


ARGUMENTS


PATH                                    The path for which information should be displayed');

// Get arguments
$argv = ArgvValidator::new()
                     ->select('path')->isPath(FsDirectory::getFilesystemRoot())
                     ->select('-h,--human-readable')->isOptional(false)->isBoolean()
                     ->validate();

FsPath::new($argv['path'], $restrictions)->getInfoObject()->displayCliForm();
