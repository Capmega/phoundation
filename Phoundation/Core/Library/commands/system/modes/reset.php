<?php

/**
 * Command system modes reset
 *
 * This command will disable readonly mode
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


CliDocumentation::setUsage('./pho system modes reset');

CliDocumentation::setHelp('This command will disable both readonly and maintenance mode

When maintenance mode is enabled, all web requests will immediately be blocked until readonly mode has been disabled.
Most CLI commands will too be blocked. The only commands available will be commands under ./pho system

When readonly mode is enabled, all POST requests will ignore all POST data until readonly mode has been disabled.
Database requests will refuse to write, as will Filesystem commands.

Readonly mode is enabled by generating the file ROOT/data/system/readonly/USEREMAIL. If the path
ROOT/data/system/readonly exists, readonly mode has been enabled

Maintenance mode is enabled by generating the file ROOT/data/system/maintenance/USEREMAIL. If the path
ROOT/data/system/maintenance exists, maintenance mode has been enabled

This command will disable both maintenance and readonly modes

Enable maintenance mode manually with ./pho system modes maintenance enable
Enable readonly mode manually with ./pho system modes readonly enable


ARGUMENTS


-');


// Validate arguments
ArgvValidator::new()->validate();
Core::resetModes();
