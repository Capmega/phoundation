<?php

/**
 * Command version
 *
 * This command will display detailed information about the current framework, project, database ,etc.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Developer\Project\Project;

CliDocumentation::setUsage('./pho version');

CliDocumentation::setHelp('The version script will print all version information.


ARGUMENTS


-');


Log::cli(Project::getVersions(true));
