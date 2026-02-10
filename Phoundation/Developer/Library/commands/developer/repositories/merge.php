<?php

/**
 * Command developer repositories merge
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will merge the specified branches into the current branch of all known phoundation repositories
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesChangesException;
use Phoundation\Developer\Versioning\Repositories\Repositories;


// Start documentation
CliDocumentation::setAutoComplete([
    'position' => [
        -1 => function ($word) {

        },
        0 => function ($word) {

        }
    ]
]);

CliDocumentation::setUsage('./pho development repositories branch merge BRANCH [BRANCH, BRANCH, ...]
./pho dv rp br mg BRANCH 
./pho development rp branch mg [BRANCH, BRANCH, ...]');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will merge the specified branches into the current branch of all known phoundation repositories 


ARGUMENTS


BRANCH                                  The branch that will be merged into the currently selected branch 


OPTIONAL ARGUMENTS


[BRANCH, BRANCH, ...]                   Optionally, specify more branches to merge into the currently selected branch'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('branches')->isOptional()->sanitizeForceArray()->forEachField()->isCode()
                     ->validate();


try {
// Execute git merge on all known repositories
    Repositories::new()->load()->merge($argv['branches']);

} catch (RepositoriesChangesException $e) {
    // Could not merge, some repositories have changes. List the affected repositories here
    Log::cli($e->getMessage(), 'warning');

    foreach ($e->getDataKey('repositories') as $repository) {
        Log::cli($repository, 'debug');
    }
}

