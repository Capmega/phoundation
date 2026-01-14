<?php

/**
 * Command developer repositories fetch
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will fetch all known phoundation repositories
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


// Start documentation
CliDocumentation::setAutoComplete([
    'arguments' => [
        '-a'          => false,
        '-r,--remote' => true
    ]
]);

CliDocumentation::setUsage('./pho development repositories fetch
./pho dv rp ft
./pho development rp ft -A --remote origin');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will execute a fetch on all known phoundation repositories 


ARGUMENTS


- 


OPTIONAL ARGUMENTS


[-A, --all]                                    If specified, will fetch all repositories (including deleted)

[-r, --remote REMOTE_REPOSITORY]               If specified, will fetch from the specified remote repository (must exist)

[-a]                                           If specified, will execute fetch --all'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-a')->isOptional()->isBoolean()
                     ->select('-r,--remote')->isOptional()->isCode()
                     ->validate();


// Execute git pull on all known repositories
Repositories::new()->load()->fetch($argv['remote'] ?? false, $argv['a']);
