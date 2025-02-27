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
use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho sessions dump SESSION');

CliDocumentation::setHelp(User::getHelpText('This command dumps the data for the specified session  


ARGUMENTS


SESSION                                 The code of the session that should be dumped'));


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('session')->isCode()
                     ->validate();


Log::cli(Session::new($argv['session'])->getSource());
