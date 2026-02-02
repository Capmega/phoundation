<?php

/**
 * Command developer repositories pull
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will pull all known phoundation repositories
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Repositories\Repositories;


// Start documentation
CliDocumentation::setAutoComplete([
    'arguments' => [
        '-a' => false
    ]
]);

CliDocumentation::setUsage('./pho development repositories pull
./pho dv rp pl
./pho development rp pl -A
./pho development rp pull -A --remote origin');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will execute a pull on all known phoundation repositories 


ARGUMENTS


- 


OPTIONAL ARGUMENTS


[-b, --branch BRANCH_NAME]                     If specified, will pull from the specified remote branch (must exist)

[-r, --remote REMOTE_REPOSITORY]               If specified, will pull from the specified remote repository (must exist)'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-b,--branch')->isOptional()->isCode()
                     ->select('-r,--remote')->isOptional()->isCode()
                     ->validate();


// Execute git pull on all known repositories
Repositories::new()->load()->pull($argv['remote'], $argv['branch']);
