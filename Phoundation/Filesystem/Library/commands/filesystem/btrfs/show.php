<?php

/**
 * Command filesystem btrfs show
 *
 * This command shows basic information about all mounted btrfs filesystems or BTRFS filesystems under /dev
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


CliDocumentation::setUsage('./pho filesystem btrfs show 
./pho filesystem btrfs show -A');

CliDocumentation::setHelp('This command scans devices of btrfs filesystems

By default, only mounted BTRFS filesystems are displayed. With the -A or --all option, all BTRFS filesystems available under /dev are displayed.


ARGUMENTS


-A, --all                               If specified will return all devices');


// Validate no arguments
$argv = ArgvValidator::new()->validate();


// Get and display statistics
BtrfsFilesystem::new()->getFilesystems(!ALL)->displayCliKeyValueTable();
