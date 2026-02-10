<?php

/**
 * Command developer repositories merge-version
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will merge the specified suffix branches into the currently selected version branch of all known phoundation repositories
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
throw new \Phoundation\Exception\UnderConstructionException();
        },
        0 => function ($word) {
throw new \Phoundation\Exception\UnderConstructionException();
        }
    ]
]);

CliDocumentation::setUsage('./pho development repositories branch merge-version BRANCH [BRANCH, BRANCH, ...]
./pho dv rp br mg BRANCH 
./pho development rp branch mg [BRANCH, BRANCH, ...]');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will merge the specified suffix branches into the currently selected version branch of all known phoundation repositories 


ARGUMENTS


BRANCH-SUFFIX                           The branch suffix that identifies the branches that will be merged into the currently selected branch 


OPTIONAL ARGUMENTS


[BRANCH-SUFFIX, BRANCH-SUFFIX, ...]     Optionally, specify more branch suffixes to merge into the currently selected branch'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('suffixes')->isOptional()->sanitizeForceArray()->forEachField()->isCode()
                     ->validate();


try {
// Execute git merge-version on all known repositories
    Repositories::new()->load()->mergeVersionSuffixes($argv['suffixes']);

} catch (RepositoriesChangesException $e) {
    // Could not merge-version, some repositories have changes. List the affected repositories here
    Log::cli($e->getMessage(), 'warning');

    foreach ($e->getDataKey('repositories') as $repository) {
        Log::cli($repository, 'debug');
    }
}

