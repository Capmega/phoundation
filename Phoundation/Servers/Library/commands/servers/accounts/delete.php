<?php

declare(strict_types=1);

use Phoundation\Accounts\Accounts\Account;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Servers\SshAccount;


/**
 * Script servers/accounts/delete
 *
 * This script can delete accounts
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho servers accounts delete NAME');

CliDocumentation::setHelp('This command will delete the specified SSH account. Note that deleted accounts will not be
removed from the database, the status for the account will be updated to "deleted". The SSH key, however, will be
cleared



ARGUMENTS



NAME                                    The name of the account to delete');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('name')->isEmail()
                     ->validate();


// Delete the account
SshAccount::load($argv['name'])
          ->setSshKey('')
          ->save()
          ->delete();
