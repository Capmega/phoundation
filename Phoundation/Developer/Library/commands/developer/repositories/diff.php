<?php

/**
 * Command developer repositories diff
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will list the diff for all known phoundation repositories
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
CliDocumentation::setAutoComplete();

CliDocumentation::setUsage('./pho development repositories diff
./pho development repositories diff -h');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will diff all known phoundation repositories 


ARGUMENTS


-


OPTIONAL ARGUMENTS


[-h, --human-readable]                  If specified, will display the information with human readable diffs'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-h,--human-readable')->isOptional()->isBoolean()
                     ->validate();


// List diff for all available repositories
Repositories::new()->load()->getDiffObject($argv['human_readable'])->displayCliTable();
