<?php

declare(strict_types=1);

use Phoundation\Accounts\Users\Password;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Script accounts/users/info
 *
 * This script displays information about the specified user.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho accounts users encryption');

CliDocumentation::setHelp('This command determines the best encryption cost value for your machine so you can configure 
"security.passwords.cost" with the best value  


ARGUMENTS


-');


// Validate no arguments
$argv = ArgvValidator::new()->validate();


// Display
Core::setTimeout(60);
Log::information(tr('Calculating best encryption cost, this may take a few seconds so be patient...'));
Log::information(tr('Best encryption cost value is: " :value', [':value' => Password::findBestEncryptionCost()]));
