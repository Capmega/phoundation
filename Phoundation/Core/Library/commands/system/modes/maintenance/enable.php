<?php

/**
 * Command system modes maintenance enable
 *
 * This command will enable maintenance mode
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Core;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho system modes maintenance enable');

CliDocumentation::setHelp('This command will enable maintenance mode

When maintenance mode is enabled, all web requests will immediately be blocked until maintenance mode has been disabled.
Most CLI commands will too be blocked. The only commands available will be commands under ./pho system

Maintenance mode is enabled by generating the file ROOT/data/system/maintenance/USEREMAIL. If the path
ROOT/data/system/maintenance exists, maintenance mode has been enabled

Maintenance mode is enabled (and disabled when finished) automatically by a number of scripts and library calls, like
for example:

./pho databases import
./pho databases export
./pho system deploy
./pho system sync

Disable maintenance mode manually with ./pho system modes maintenance disable


ARGUMENTS


-');


// Validate arguments
ArgvValidator::new()->validate();


// Disable maintenance mode
Core::setMaintenanceMode(true);
