<?php

/**
 * Command info
 *
 * This command will display detailed information about the current framework, project, database ,etc.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliColor;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Core;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Exception\SqlAccessDeniedException;
use Phoundation\Databases\Sql\Exception\SqlDatabaseDoesNotExistException;
use Phoundation\Developer\Debug;
use Phoundation\Utils\Config;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;


CliDocumentation::setUsage('./pho version');

CliDocumentation::setHelp('The info script will show basic version information


ARGUMENTS


-');



Log::cli(tr('Phoundation version ":version"', [
    ':version' => Core::FRAMEWORK_CODE_VERSION
]));
