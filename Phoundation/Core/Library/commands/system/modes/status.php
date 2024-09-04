<?php

/**
 * Command system modes maintenance status
 *
 * This command will display the current maintenance mode status
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho system modes maintenance status');

CliDocumentation::setHelp('This command will display the current maintenance mode status

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

Enable readonly mode manually with ./pho system modes maintenance enable or reset all modes with 
./pho system modes reset


ARGUMENTS


-');


// Validate arguments
ArgvValidator::new()->validate();


// Get maintenance mode data
$mode = Core::getMaintenanceMode();

if ($mode) {
    Log::warning(tr('The system is in ":mode" mode, set by ":user" on ":date"', [
        ':mode' => $mode->getMode(),
        ':user' => $mode->getUserObject()?->getLogId(),
        ':date' => $mode->getDateTime()->format('Y-m-d H:i:s')
    ]));

} else {
    Log::success(tr('The system is NOT in maintenance mode'));
}


// Get readonly mode data
$mode = Core::getReadonlyMode();

if ($mode) {
    Log::warning(tr('The system is in ":mode" mode, set by ":user" on ":date"', [
        ':mode' => $mode->getMode(),
        ':user' => $mode->getUserObject()?->getLogId(),
        ':date' => $mode->getDateTime()->format('Y-m-d H:i:s')
    ]));

} else {
    Log::success(tr('The system is NOT in readonly mode'));
}
