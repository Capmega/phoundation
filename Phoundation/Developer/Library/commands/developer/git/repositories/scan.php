<?php

/**
 * Command developer git repositories scan
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will scan for phoundation repositories and register them in the database
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Development
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Git\Repositories\Repositories;


CliDocumentation::setUsage('./pho development git repositories scan');

CliDocumentation::setHelp('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will scan for phoundation repositories and register them in the database');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-p,--path', true)->isOptional()->isPath()
                     ->select('-d,--delete-gone')->isOptional()->isBoolean()
                     ->validate();


Repositories::new()->scan($argv['path'], $argv['delete_gone']);
