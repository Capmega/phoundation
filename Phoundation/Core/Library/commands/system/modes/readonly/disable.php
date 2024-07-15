<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Core;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Command system/modes/readonly/disable
 *
 * This command will disable readonly mode
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho system modes readonly disable');

CliDocumentation::setHelp('This command will disable readonly mode

When readonly mode is enabled, all POST requests will ignore all POST data until readonly mode has been disabled.
Database requests will refuse to write, as will Filesystem commands.

Readonly mode is enabled by generating the file ROOT/data/system/readonly/USEREMAIL. If the path
ROOT/data/system/readonly exists, readonly mode has been enabled

Readonly mode is enabled (and disabled when finished) automatically by a number of scripts and library calls, like
for example:

./pho databases import
./pho databases export
./pho system deploy
./pho system sync

Enable readonly mode manually with ./pho system modes readonly enable


ARGUMENTS


-');


// Validate arguments
ArgvValidator::new()->validate();
Core::setReadonlyMode(false);
