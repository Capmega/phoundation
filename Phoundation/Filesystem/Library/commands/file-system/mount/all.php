<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Filesystem\Mounts\Mounts;


/**
 * Script file-system/mount/all
 *
 * This command mounts all configured filesystem mounts
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */


CliDocumentation::setUsage('./pho filesystem mount all');

CliDocumentation::setHelp('This command will attempt to mount all configured mount points');


// Mount all configured mount points
foreach (Mounts::new()->load() as $mount) {
    $mount->mount();
}
