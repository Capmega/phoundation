<?php

/**
 * Command developer repositories tags create
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will create the specified tag for all repositories
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
    'positions' => [
        0 => true,
    ]
]);

CliDocumentation::setUsage('./pho development repositories tags delete NAME
./pho dv rp tg dl NAME');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will create a tag with the specified name for all repositories 


ARGUMENTS


NAME                                    The name of the tag to delete'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('name', true)->isCode()
                     ->validate();


// Delete the tag!
Repositories::new()->load()->deleteTag($argv['name']);

