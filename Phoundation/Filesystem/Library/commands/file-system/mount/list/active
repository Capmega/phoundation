<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Filesystem\Mounts\Mounts;


/**
 * Script file-system/mount/list/active
 *
 * This command lists all active mounts
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */


CliDocumentation::setUsage('./pho filesystem mounts list active');

CliDocumentation::setHelp('This command will list all configured mount points that are active');


// Display the current mounts
Mounts::listMountTargets()->displayCliTable('source,target,filesystem');
