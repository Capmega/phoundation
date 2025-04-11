<?php

/**
 * Command sessions truncate
 *
 * This command  the accounts_user_sessions table. USE WITH CARE!
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Sessions;
use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;

CliDocumentation::setUsage('./pho sessions truncate -F');

CliDocumentation::setHelp(User::getHelpText('This command truncates the accounts_user_sessions table. USE WITH CARE!  


ARGUMENTS


-F, --force                             Required FORCE flag to ensure people do not accidentally clear the sessions 
                                        table'));


// Validate arguments
$argv = ArgvValidator::new()->validate();


if (!FORCE) {
    throw new ValidationFailedException(tr('Cannot truncate accounts_user_sessions table without FORCE flag (-F, --force)'));
}


Sessions::truncate();
