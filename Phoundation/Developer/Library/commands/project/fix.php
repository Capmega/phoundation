<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Project\Project;


/**
 * Script project/fix
 *
 * This script will fix project issues.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho project fix');

CliDocumentation::setHelp('This command will fix project issues. For the moment it can only fix filesystem mode and
ownership issues.

NOTE: This script requires root access to the "chown" command



ARGUMENTS



-');


// Validate arguments
$argv = ArgvValidator::new()
                     ->validate();


Log::information('Fixing your system. Please wait, this may take a few seconds...');
Project::fixFileModes();
