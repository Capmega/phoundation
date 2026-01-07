<?php

/**
 * Command developer git repositories branches switch
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will switch the branch for the specified repository
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
use Phoundation\Developer\Versioning\Repositories\Repository;
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

CliDocumentation::setUsage('./pho development git repositories branches switch REPOSITORY_NAME BRANCH_NAME');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will list all known phoundation repositories 


ARGUMENTS


REPOSITORY_NAME                         The repository for which to switch the branch

BRANCH_NAME                             The branch name to switch to'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('repository')->isCode()
                     ->select('branch')->isCode()
                     ->validate();


// Switch the branch!
Repository::new($argv['repository'])->setCurrentBranch($argv['branch']);

