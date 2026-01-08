<?php

/**
 * Command developer git repositories branches delete
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


// Start documentation
CliDocumentation::setAutoComplete([
    'positions' => [
        0 => true
    ]
]);

CliDocumentation::setUsage('./pho development repositories branches delete
./pho development rp br sl');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will delete the branches with the specified suffix for all known phoundation repositories, ensuring all repositories are on the right branch


ARGUMENTS


-r, --not-remote                        If specified will NOT remove the branches from the default remote repository'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('suffix')->matchesRegex('/^[a-z0-9-]+$/i')
                     ->select('-r,--not-remote')->isOptional()->isBoolean()
                     ->validate();


// Load all known repositories and delete the requested branch
$o_repositories = Repositories::new()->load();

Log::cli(ts('Deleting branches for ":count" repositories, this might take a few seconds...', [
    ':count' => $o_repositories->getCount()
]), 'action');

$o_repositories->deleteBranch($argv['suffix'], !$argv['not_remote']);
