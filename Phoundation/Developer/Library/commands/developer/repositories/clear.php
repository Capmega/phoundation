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
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Repositories\Repositories;


// Start documentation
CliDocumentation::setAutoComplete([
    'positions' => [
        0 => function ($word) {
            return Repositories::new()->load()->autoCompleteFind($word);
        },
    ]
]);

CliDocumentation::setUsage('./pho development repositories show NAME');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will show details about the requested repository 


ARGUMENTS


-'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->validate();


// Clear all repositories from the database
Repositories::new()->truncate();
