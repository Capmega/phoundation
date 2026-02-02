<?php

/**
 * Command project version
 *
 * This command will fix project issues.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Project\Project;


CliDocumentation::setUsage('./pho project version');

CliDocumentation::setHelp('This command will print the current project version.


ARGUMENTS


-');


// Validate arguments
$argv = ArgvValidator::new()
                     ->validate();


Log::cli(Project::getVersion());