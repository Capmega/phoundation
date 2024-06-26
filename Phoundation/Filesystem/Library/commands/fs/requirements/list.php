<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Filesystem\Mounts\FsMounts;


/**
 * Command file-system/mount/all
 *
 * This command lists all mounts
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */

CliDocumentation::setUsage('./pho filesystem requirements list');

CliDocumentation::setHelp('This command lists all available path requirements');


// Display the available mounts
FsMounts::new()->load()->displayCliTable('name,source_path,target_path,filesystem');
