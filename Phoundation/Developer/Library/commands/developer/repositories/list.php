<?php

/**
 * Command developer git repositories list
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will list all known phoundation repositories
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Development
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Developer\Versioning\Repositories\Repositories;
use Phoundation\Filesystem\PhoDirectory;


// Start documentation
CliDocumentation::setAutoComplete();

CliDocumentation::setUsage('./pho development repositories list');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will list all known phoundation repositories 


ARGUMENTS


- 


OPTIONAL ARGUMENTS


[-A]                                    If specified, will list all repositories (including deleted)'));


// Get command line arguments
$argv = ArgvValidator::new()->validate();


// List available repositories
Repositories::new()->load()->ksort()->displayCliTable([
    'name'     => ts('Repository name'),
    'platform' => ts('Platform'),
    'type'     => ts('Type'),
    'required' => ts('Required'),
    'path'     => ts('Path'),
]);
