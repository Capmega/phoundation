<?php

/**
 * Command tools os filesystem btrfs device statistics
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\Filesystems\Btrfs\BtrfsDevice;
use Phoundation\Filesystem\PhoDirectory;


CliDocumentation::setAutoComplete([
    'positions' => [
        0 => function($word) { return PhoDirectory::newFilesystemRoot(false)->scan($word, '/.*/')->addEmpty(); },
    ]
]);

CliDocumentation::setUsage('./pho filesystem btrfs device statistics PATH
./pho filesystem btrfs device statistics -r');

CliDocumentation::setHelp('This command displays device IO error statistics


ARGUMENTS

PATH                                    Path to device that contains the BTRFS device



OPTIONAL ARGUMENTS

[-A, --all]                             If specified will return all devices


[-r, --reset]                           If specified will reset the statistics of the device');


// Validate data
$argv = ArgvValidator::new()
                     ->select('path')->sanitizePath()
                     ->select('-r,--reset')->isOptional(false)->isBoolean()
                     ->validate();


// Get and display statistics
BtrfsDevice::new($argv['path'])->getStatistics(ALL)->displayCliKeyValueTable();


// Reset statistics?
if ($argv['reset']) {
    BtrfsDevice::new($argv['path'])->resetStatistics();
    Log::warning(ts('Reset IO error statistics for device ":device"', [
        ':device' => $argv['path']
    ]));
}
