<?php

/**
 * Command file-system/mount/all
 *
 * This command mounts all configured filesystem mounts
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\Mounts\FsMounts;


CliDocumentation::setUsage('./pho filesystem mount all');

CliDocumentation::setHelp('This command will try to mount all configured filesystem mounts');


// Get no arguments
$argv = ArgvValidator::new()->validate();


// FsMount all filesystems
foreach (FsMounts::new()->load() as $mount) {
    $mount->mount();
}
