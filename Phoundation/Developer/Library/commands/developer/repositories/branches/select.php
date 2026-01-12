<?php

/**
 * Command developer git repositories branches select
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
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Git\Exception\GitBranchNotExistException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesException;
use Phoundation\Developer\Versioning\Repositories\Repositories;


// Start documentation
CliDocumentation::setAutoComplete([
    'positions' => [
        0 => true,
    ],
    'arguments' => [
        '-a,--auto-create' => false
    ]
]);

CliDocumentation::setUsage('./pho development repositories branches switch REPOSITORY_NAME BRANCH_NAME');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will select the specified branch for all available repositories 


ARGUMENTS


BRANCH_NAME                             The branch name to switch to


OPTIONAL ARGUMENTS


[-c, --auto-create]                     If specified, will automatically create the branch on each repository if it does 
                                        not yet exist

[-u, --auto-upstream]                   If specified, will automatically set upstreams to the default repository'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('branch')->isCode()
                     ->select('-c,--auto-create')->isOptional()->isBoolean()
                     ->select('-u,--auto-upstream')->isOptional()->isBoolean()
                     ->validate();


try {
    // Switch the branch!
    Repositories::new()->load()->selectBranch($argv['branch'], $argv['auto_create'] or $argv['auto_upstream'], $argv['auto_upstream']);

} catch (RepositoriesException|GitBranchNotExistException $e) {
    throw $e->makeWarning();
}

