<?php

/**
 * Command version
 *
 * This command will display detailed information about the current framework, project, database ,etc.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;


CliDocumentation::setUsage('./pho version');

CliDocumentation::setHelp('The version script will print all version information.


ARGUMENTS


-');



Log::cli(ts('Phoundation version ":version"', [
    ':version' => Core::FRAMEWORK_CODE_VERSION
]));
Log::cli(ts('Project version ":version"', [
    ':version' => Core::getProjectVersion()
]));
