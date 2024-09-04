<?php

/**
 * Command system modes readonly status
 *
 * This command will display the current readonly mode status
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
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho system modes readonly status');

CliDocumentation::setHelp('This command will display the current readonly mode status

When readonly mode is enabled, all file and query write requests on both CLI and WEB scripts will immediately be blocked 
until readonly mode has been disabled. On CLI the read only mode can be ignored using the --ignore-readonly flag

Readonly mode is enabled by generating the file ROOT/data/system/readonly/USEREMAIL. If the path
ROOT/data/system/readonly exists, readonly mode has been enabled

Readonly mode is enabled (and disabled when finished) automatically by a number of scripts and library calls, like
for example:

./pho databases import
./pho databases export
./pho system deploy
./pho system sync

Enable readonly mode manually with ./pho system modes readonly enable or reset all modes with ./pho system modes reset


ARGUMENTS


-');


// Validate arguments
ArgvValidator::new()->validate();


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
