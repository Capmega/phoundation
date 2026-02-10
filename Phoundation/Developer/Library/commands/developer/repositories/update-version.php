<?php

/**
 * Command developer repositories branches select
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will switch the branch for the specified repository
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
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

CliDocumentation::setUsage('./pho development repositories update');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will update suffix version branches from their base version branches  


ARGUMENTS


-'));


// Get command line arguments
$argv = ArgvValidator::new()->validate();


// Update the branches on all repositories
Repositories::new()->load()->updateVersionBranches(ALL);
