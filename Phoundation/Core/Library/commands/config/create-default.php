<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Utils\Config;


/**
 * Script config/create-default
 *
 * This script will create a default configuration file ROOT/config/default.yaml containing ALL possible configuration
 * paths and their default values
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho config create-default');

CliDocumentation::setHelp('This command will create a default configuration file ROOT/config/default.yaml containing ALL possible configuration paths and their default values


ARGUMENTS


-');


// Validate no arguments
$argv = ArgvValidator::new()->validate();


// Generate default yaml configuration file
$count = Config::generateDefaultYaml();
Log::success(tr('Created config/default.yaml with ":count" configuration paths', [':count' => $count]));
