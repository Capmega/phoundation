<?php

/**
 * Command tools os filesystem btrfs device statistics
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\Filesystems\Btrfs\BtrfsFilesystem;
use Phoundation\Filesystem\PhoDirectory;


CliDocumentation::setAutoComplete([
    'positions' => [
        0 => function($word) { return PhoDirectory::newFilesystemRootObject(false)->scan($word, '/.*/')->addEmpty(); },
    ],
    'arguments' => [
        '-d,--device' => false,
    ]
]);

CliDocumentation::setUsage('./pho filesystem btrfs filesystem usage PATH
./pho filesystem btrfs filesystem usage PATH -d');

CliDocumentation::setHelp('This command scans devices of btrfs filesystems


ARGUMENTS

PATH                                    Path to device that contains the BTRFS filesystem


OPTIONAL ARGUMENTS

[-d, --device]                          If specified will display the usage information per device for this filesystem');


// Validate data
$argv = ArgvValidator::new()
                     ->select('path')->sanitizePath()
                     ->select('-d,--device')->isOptional()->isBoolean()
                     ->validate();


if ($argv['device']) {
    // Get and display filesystem device statistics
    BtrfsFilesystem::new($argv['path'])->getDeviceUsage()->displayCliKeyValueTable();

} else {
    // Get and display filesystem statistics
    BtrfsFilesystem::new($argv['path'])->getUsage()->displayCliKeyValueTable();
}
