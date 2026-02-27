<?php

/**
 * Command sessions count
 *
 * This command displays the number of registered sessions
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\UserSessions;
use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;

CliDocumentation::setUsage('./pho sessions count');

CliDocumentation::setHelp(User::getHelpText('This command displays the number of registered sessions  


ARGUMENTS


-'));


// Validate arguments
$argv = ArgvValidator::new()->validate();


// Display active session count
Log::cli(UserSessions::new()->getActiveCount());
