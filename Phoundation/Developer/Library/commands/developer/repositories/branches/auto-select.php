<?php

/**
 * Command developer git repositories branches select
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will synchronize the branches for all known phoundation repositories
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
use Phoundation\Developer\Versioning\Repositories\Repositories;
use Phoundation\Filesystem\PhoDirectory;


// Start documentation
CliDocumentation::setAutoComplete([
    'positions' => [
        0 => true
    ]
]);

CliDocumentation::setUsage('./pho development repositories branches select
./pho development rp br sl');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will synchronize the branches for all known phoundation repositories, ensuring all repositories are on the right branch

The selected branch for this project should match the specified version, with optionally a suffix 

The selected branch for all other repositories should match the Phoundation version, with optionally a suffix 


ARGUMENTS


-'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('suffix')->isOptional()->matchesRegex('/^[a-z0-9-]+$/i')
                     ->validate();


// Synchronize all known repositories
$o_repositories = Repositories::new()->load();

Log::cli(ts('Automatically selecting branches for ":count" repositories, this might take a few seconds...', [
    ':count' => $o_repositories->getCount()
]), 'action');

$o_repositories->selectAutoBranch($argv['suffix']);
