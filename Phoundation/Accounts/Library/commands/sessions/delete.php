<?php

/**
 * Command sessions delete
 *
 * This command deletes the specified session
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\UserSession;
use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho sessions delete SESSIONID
./pho sessions delete mt5hvb34te9e5f9ge3e7n6rffg');

CliDocumentation::setHelp(User::getHelpText('This command deletes the data for the specified session. This will  


ARGUMENTS


SESSION ID                              The ID of the session to delete. This session MUST already exist'));


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('session')->isOptional()->hasCharacters(26)->matchesRegex('/^[a-z0-9]{26}$/')
                     ->validate();


UserSession::new($argv['session'])->delete();