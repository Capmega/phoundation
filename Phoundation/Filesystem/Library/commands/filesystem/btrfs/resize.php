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
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Filesystems\Btrfs\BtrfsFilesystem;


CliDocumentation::setUsage('./pho filesystem btrfs device statistics PATH
./pho filesystem btrfs device statistics -r');

CliDocumentation::setHelp('This command scans devices of btrfs filesystems


ARGUMENTS


-A, --all                               If specified will return all devices');


throw new UnderConstructionException();
// Validate data
$argv = ArgvValidator::new()
                     ->select('path')->sanitizePath()
                     ->validate();


// Get and display statistics
foreach (BtrfsFilesystem::new($argv['path'])->getUsage() as $key => $value) {
    Log::cli($key . ' = ' . $value);
}
