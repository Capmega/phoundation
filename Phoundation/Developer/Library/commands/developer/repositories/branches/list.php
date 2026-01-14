<?php

/**
 * Command developer repositories branches
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
use Phoundation\Developer\Versioning\Repositories\Repositories;
use Phoundation\Developer\Versioning\Repositories\Repository;


// Start documentation
CliDocumentation::setAutoComplete([
    'positions' => [
        0 => function ($word) {
            return Repositories::new()->load()->keepMatchingAutocompleteValues($word, 'name');
        },
    ]
]);

CliDocumentation::setUsage('./pho development repositories branches list REPOSITORY_NAME');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will list all known phoundation repositories 


ARGUMENTS


REPOSITORY_NAME                         The repository for which to display the available branches


OPTIONAL ARGUMENTS


[-A, --all]                             If specified, will display all branches'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('repository')->isCode()
                     ->validate();


// Switch the branch!
Repository::new($argv['repository'])->getBranchesObject()->displayCliTable([
    'branch' => ts('Branch'),
]);

