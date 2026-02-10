<?php

/**
 * Command developer repositories branches
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will list all known phoundation repositories and their currently selected branch
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Repositories\Repositories;


// Start documentation
CliDocumentation::setAutoComplete();

CliDocumentation::setUsage('./pho development repositories branches');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will list all known phoundation repositories and at what branch they are 


ARGUMENTS


- 


OPTIONAL ARGUMENTS


[-A]                                    If specified, will list all repositories (including deleted)'));


// Get command line arguments
$argv = ArgvValidator::new()->validate();


// List available repositories
Repositories::new()->load()->displayCliTable([
    'name'     => ts('Repository name'),
    'platform' => ts('Platform'),
    'type'     => ts('Type'),
    'branch'   => ts('Branch (or tag)'),
]);
