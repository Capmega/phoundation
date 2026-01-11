<?php

/**
 * Command developer git repositories branches select
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will select the branch for all repositories
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
    'positions' => [
        0 => function ($word) {
            return Repositories::new()->load()->keepMatchingAutocompleteValues($word, 'name');
        },
        1 => function ($word) {
            $argv = ArgvValidator::new()
                                 ->select('repository')->isCode()
                                 ->validate();

            return array_keys(Repository::new($argv['repository'])->getBranchesObject()->keepMatchingAutocompleteValues($word, 'name')->getSource());
        },
    ]
]);

CliDocumentation::setUsage('./pho development repositories branches select REPOSITORY_NAME BRANCH_NAME');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will list all known phoundation repositories 


ARGUMENTS


REPOSITORY_NAME                         The repository for which to switch the branch

BRANCH_NAME                             The branch name to switch to


OPTIONAL ARGUMENTS


[-a,--auto-create]                      If specified, will automatically create the branch if it does not yet exist

[-u,--upstream]                         (Requires --auto-create) If specified, will automatically set the newly created branch upstream to '));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('repository')->isCode()
                     ->select('branch')->isCode()
                     ->select('-a,--auto-create')->isOptional()->isBoolean()
                     ->select('-u,--upstream')->isOptional()->isBoolean()
                     ->validate();


// Switch the branch!
Repository::new($argv['repository'])->setCurrentBranch($argv['branch'], $argv['auto_create'], $argv['upstream']);

