<?php

/**
 * Command developer repositories add
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will list the add for all known phoundation repositories
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
CliDocumentation::setAutoComplete();

CliDocumentation::setUsage('./pho development repositories add
./pho development repositories add -h');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will add all known phoundation repositories 


ARGUMENTS


-


OPTIONAL ARGUMENTS


[-h, --human-readable]                  If specified, will display the information with human readable addes'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-h,--human-readable')->isOptional()->isBoolean()
                     ->validate();


// List add for all available repositories
Repositories::new()->load()->getStatusObject($argv['human_readable'])->displayCliTable();
