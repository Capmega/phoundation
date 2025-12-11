<?php

/**
 * Command developer git repositories scan
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will scan for phoundation repositories and register them in the database
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
CliDocumentation::setAutoComplete([
    'arguments' => [
        '-p,--path' => function ($word) {
            return PhoDirectory::newRootObject()->scan($word);
        },
        '-d,--delete-gone' => false
    ]
]);

CliDocumentation::setUsage('./pho development git repositories list');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will list all known phoundation repositories 


ARGUMENTS


- 


OPTIONAL ARGUMENTS


[-A]                                    If specified, will list all repositories (including deleted)'));


// Get command line arguments
$argv = ArgvValidator::new()->validate();


// List known repositories
Repositories::new()->load()->displayCliTable([
    'name'     => ts('Repository name'),
    'platform' => ts('Platform'),
    'type'     => ts('Type'),
    'required' => ts('Required'),
    'path'     => ts('Path'),
]);
