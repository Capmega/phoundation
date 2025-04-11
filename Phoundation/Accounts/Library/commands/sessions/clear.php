<?php

/**
 * Command sessions clear
 *
 * This command closes all expired sessions
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

CliDocumentation::setUsage('./pho sessions clear');

CliDocumentation::setHelp(User::getHelpText('This command closes all expired sessions  


ARGUMENTS


-'));


// Validate arguments
$argv = ArgvValidator::new()->validate();


Sessions::stopExpired();
