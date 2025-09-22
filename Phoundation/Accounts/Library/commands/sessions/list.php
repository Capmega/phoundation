<?php

/**
 * Command sessions dump
 *
 * This command dumps the data for the specified session
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Sessions;
use Phoundation\Accounts\Users\Sessions\UserSession;
use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Date\PhoDateTime;
use Phoundation\Utils\Strings;

CliDocumentation::setUsage('./pho sessions list
./pho sessions list -u sven@medinet.ca
./pho sessions list --ip 127.0.0.1');

CliDocumentation::setHelp(User::getHelpText('This command lists the currently active sessions  


ARGUMENTS


-


OPTIONAL ARGUMENTS


[-u,--user <EMAIL>]                     The user (specified by email address) to list sessions for

[-i,--users-id <id>]                    The user (specified by users id) to list sessions for

[--ip <ip>]                             The ip address to list sessions for'));


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('-u,--user')->isOptional()->orColumn('users_id,ip')->isEmail()->requiresValueEmpty(ALL, tr('cannot be used in combination with -A,--all'))
                     ->select('-i,--users-id')->isOptional()->orColumn('user,ip')->isDbId()->requiresValueEmpty(ALL, tr('cannot be used in combination with -A,--all'))
                     ->select('--ip')->isOptional()->orColumn('user,users_id')->isIpAddress()->requiresValueEmpty(ALL, tr('cannot be used in combination with -A,--all'))
                     ->validate();


// Fetch a list of sessions
if (ALL) {
    $sessions = Sessions::getAll();

} elseif ($argv['user']) {
    $sessions = Sessions::getActiveForUsersId(User::new()->loadColumns(['email' => $argv['user']])->getId());

} elseif ($argv['users_id']) {
    $sessions = Sessions::getActiveForUsersId($argv['users_id']);

} elseif ($argv['ip']) {
    $sessions = Sessions::getActiveForIp($argv['ip']);

} else {
    $sessions = Sessions::getActive();
}


// Add user and session data to the list, then order it by last_activity
$sessions = Sessions::addData($sessions)->uasort(function ($a, $b) {
    if ($a['last_activity'] < $b['last_activity']) {
        return 1;
    }

    if ($a['last_activity'] > $b['last_activity']) {
        return -1;
    }

    return 0;
});


// Display sessions
foreach ($sessions as $identifier => $session) {
    Log::cli(Strings::size($session['user']?->getLogId() ?? 'guest', 64) . ' ' . Strings::size($identifier, 32) . ' ' . (array_get($session, 'last_activity')?->setTimezone('user') ?? '-'));
}
