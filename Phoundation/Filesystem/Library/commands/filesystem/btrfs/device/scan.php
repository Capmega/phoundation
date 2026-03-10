<?php

/**
 * Command tools os filesystem btrfs device scan
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


$directory = PhoDirectory::newFilesystemRoot();

CliDocumentation::setUsage('./pho filesystem btrfs device scan
./pho filesystem btrfs device scan -a');

CliDocumentation::setHelp('This command scans devices of btrfs filesystems


ARGUMENTS


-A, --all                               If specified will return all devices');


// Validate no arguments specified
ArgvValidator::new()->validate();


// Scan for - and display devices
foreach (BtrfsDevice::new()->scan(ALL) as $device) {
    Log::cli($device);
}


