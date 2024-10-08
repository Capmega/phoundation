<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Project\Project;


/**
 * Script project/check
 *
 * This script will check for - and report - (and optionally fix) the project and its systems
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho project check
./pho system project check --repair
');

CliDocumentation::setHelp('This command will check - and report - (and optionally fix) the project and its systems



ARGUMENTS



[-r / --repair]                         If specified, the system will automatically fix all found issues');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('-r,--repair')->isOptional()->isBoolean()
                     ->validate();


Log::information('Checking your system. Please wait, this may take a few seconds...');
Project::check($argv['repair']);
