<?php

declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Cli\Cli;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Script accounts/users/authenticate
 *
 * This script can be used to test the authentication for the specified user
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho accounts users authenticate USER');

CliDocumentation::setHelp('This command can be used to test the authentication for the specified user


ARGUMENTS


-');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('user')->hasMinCharacters(2)->hasMaxCharacters(255)
                     ->validate();


try {
    // Get a password and try to authenticate
    $password = Cli::readPassword(tr('Password:'));
    $user     = User::authenticate($argv['user'], $password);

} catch (DataEntryNotExistsException $e) {
    throw $e->makeWarning();
}


// Done!
Log::success(tr('User ":user" was authenticated successfully', [':user' => $user->getDisplayName()]));
