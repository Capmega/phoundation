<?php

/**
 * Command sessions handlers serialize
 *
 * This command displays the sessions serialize handler
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho sessions handlers serialize');

CliDocumentation::setHelp(User::getHelpText('This command displays the sessions serialize handler  


ARGUMENTS


-'));


// Validate arguments
$argv = ArgvValidator::new()->validate();


Log::cli(ini_get('session.serialize_handler'));
