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

use Phoundation\Accounts\Users\Sessions\UserSession;
use Phoundation\Core\Sessions\Sessions;
use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho sessions list
./pho sessions list -u sven@medinet.ca
./pho sessions list --ip 127.0.0.1');

CliDocumentation::setHelp(User::getHelpText('This command lists the currently active sessions  


ARGUMENTS


-


OPTIONAL ARGUMENTS


[-u,--user <EMAIL>]                     The user (specified by email address) to list sessions for

[-i,--users-id <id>]                    The user (specified by users id) to list sessions for

[--ip <ip>]                            The ip address to list sessions for'));


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('-u,--user')->isOptional()->or('users_id,ip')->isEmail()->requiresValueEmpty(ALL, tr('cannot be used in combination with -A,--all'))
                     ->select('-i,--users-id')->isOptional()->or('user,ip')->isDbId()->requiresValueEmpty(ALL, tr('cannot be used in combination with -A,--all'))
                     ->select('--ip')->isOptional()->or('user,users_id')->isIp()->requiresValueEmpty(ALL, tr('cannot be used in combination with -A,--all'))
                     ->validate();


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


// Display active sessions
foreach ($sessions as $session) {
    // Ensure the session exists!
    if (UserSession::isActive($session['identifier'])) {
        Log::cli($session['identifier']);
    }
}
