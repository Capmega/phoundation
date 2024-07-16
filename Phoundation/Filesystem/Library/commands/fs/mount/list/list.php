<?php

/**
 * Command file-system/mount/list
 *
 * This command lists all mounts
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\Mounts\FsMounts;



CliDocumentation::setUsage('./pho filesystem mounts list');

CliDocumentation::setHelp('This command will list all configured mount points');


// Get the arguments
$argv = ArgvValidator::new()->validate();


// Display the available mounts
FsMounts::new()->load()->displayCliTable('name,source_path,target_path,filesystem');
