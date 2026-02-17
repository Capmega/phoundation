<?php

/**
 * Command developer repositories tags select
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will synchronize the tags for all known phoundation repositories
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
use Phoundation\Developer\Versioning\Repositories\Repositories;


// Start documentation
CliDocumentation::setAutoComplete([
    'positions' => [
        0 => true
    ]
]);

CliDocumentation::setUsage('./pho development repositories tags select
./pho development rp br sl');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will synchronize the tags for all known phoundation repositories, ensuring all repositories are on the right tag

The selected tag for this project should match the specified version, with optionally a suffix 

The selected tag for all other repositories should match the Phoundation version, with optionally a suffix 


ARGUMENTS


-'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('suffix')->isOptional()->matchesRegex('/^[a-z0-9-]+$/i')
                     ->validate();


// Synchronize all available repositories
$_repositories = Repositories::new()->load();

Log::cli(ts('Automatically selecting tags for ":count" repositories, this might take a few seconds...', [
    ':count' => $_repositories->getCount()
]), 'action');

$_repositories->selectVersionTag($argv['suffix']);
