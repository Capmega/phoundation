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

CliDocumentation::setUsage('./pho sessions copy SESSIONID SESSIONID
./pho sessions copy mt5hvb34te9e5f9ge3e7n6rffg kloq3564j4j6k277xcern6qqz3');

CliDocumentation::setHelp(User::getHelpText('This command copies the content of one session to another, allowing the 
target session to continue as if it were the source session  

WARNING: This command allows users to take over sessions from other users. It should not require a lot of thought of how 
dangerous this can be and the command as-is is only supplied for debugging purposes


ARGUMENTS


SESSION ID                              The ID of the source session. This session MUST already exist

SESSION ID                              The ID of the target session This session MUST already exist and typically would 
                                        be a session that you control'));


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('source')->isOptional()->hasCharacters(26)->matchesRegex('/^[a-z0-9]{26}$/')
                     ->select('target')->isOptional()->hasCharacters(26)->matchesRegex('/^[a-z0-9]{26}$/')
                     ->validate();


$source = UserSession::new($argv['session'])->getSource();