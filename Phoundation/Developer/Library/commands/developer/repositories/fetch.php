<?php

/**
 * Command developer git repositories fetch
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
        '-a' => false
    ]
]);

CliDocumentation::setUsage('./pho development repositories fetch
./pho dv rp ls
./pho development rp ls -A');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will execute a fetch on all known phoundation repositories 


ARGUMENTS


- 


OPTIONAL ARGUMENTS


[-A, --all]                                    If specified, will fetch all repositories (including deleted)

[-a]                                           If specified, will execute fetch --all'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-a')->isOptional()->isBoolean()
                     ->validate();


// Execute git pull on all known repositories
Repositories::new()->load()->fetch($argv['a']);
