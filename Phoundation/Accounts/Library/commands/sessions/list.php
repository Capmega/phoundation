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

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Accounts\Users\Sessions\Sessions;
use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho sessions list');

CliDocumentation::setHelp(User::getHelpText('This command lists the currently active sessions  


ARGUMENTS


-'));


// Validate arguments
$argv = ArgvValidator::new()->validate();


foreach (Sessions::list() as $session) {
    Log::cli($session);
}
