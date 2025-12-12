<?php

/**
 * Command developer git repositories show
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will show details about the requested repository
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
            return Repositories::new()->load()->autoCompleteFind($word);
        },
    ]
]);

CliDocumentation::setUsage('./pho development git repositories show NAME');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will show details about the requested repository 


ARGUMENTS


NAME                                    The repository name to display detailed information from'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('name')->isCode()
                     ->validate();


$o_repository = Repository::new($argv['name']);


// Display repository details
$o_repository->displayCliForm();

Log::cli(' ');
Log::cli(ts('Remotes:'), 'information');
$o_repository->getRemotesObject()->displayCliTable();

Log::cli(' ');
Log::cli(ts('Branches:'), 'information');
$o_repository->getBranchesObject()->displayCliTable();

Log::cli(' ');
Log::cli(ts('Tags:'), 'information');
$o_repository->getTagsObject()->displayCliTable();
