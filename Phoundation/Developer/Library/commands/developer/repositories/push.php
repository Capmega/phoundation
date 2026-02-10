<?php

/**
 * Command developer repositories push
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will push all known phoundation repositories
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
    'arguments' => [
        '-a' => false
    ]
]);

CliDocumentation::setUsage('./pho development repositories branch push
./pho dv rp br ps
./pho development rp br ps -A
./pho development rp branch push -A --remote origin');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will execute a push on all known phoundation repositories 


ARGUMENTS


- 


OPTIONAL ARGUMENTS


[-b, --branch BRANCH_NAME]                     If specified, will push from the specified remote branch (must exist)

[-r, --remote REMOTE_REPOSITORY]               If specified, will push from the specified remote repository (must exist)'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-b,--branch')->isOptional()->isCode()
                     ->select('-r,--remote')->isOptional()->isCode()
                     ->validate();


try {
// Execute git push on all known repositories
    Repositories::new()->load()->push($argv['remote'], $argv['branch']);

} catch (RepositoriesChangesException $e) {
    // Could not push, some repositories have changes. List the affected repositories here
    Log::cli($e->getMessage(), 'warning');

    foreach ($e->getDataKey('repositories') as $repository) {
        Log::cli($repository, 'debug');
    }
}

