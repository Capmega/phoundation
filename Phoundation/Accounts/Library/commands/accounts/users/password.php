<?php

/**
 * Command accounts/users/authenticate
 *
 * This script can be used to test the authentication for the specified user
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Cli\Cli;
use Phoundation\Cli\CliCommand;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Limit;
use Phoundation\Security\Passwords\Exception\PasswordFailedException;

CliDocumentation::setAutoComplete(User::getAutoComplete([
                                                            'positions' => [
                                                                0 => [
                                                                    'word'   => 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE COALESCE(`username`, `email`, `code`) LIKE :word AND `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
                                                                    'noword' => 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
                                                                ],
                                                            ],
                                                            'arguments' => [
                                                                '--no-password' => false,
                                                            ],
                                                        ]));

CliDocumentation::setUsage('./pho accounts users password USER
echo PASSWORD | ./pho accounts users password USER');

CliDocumentation::setHelp('This command can be used to test the authentication for the specified user

This command accepts the password through a command line pipe in which case it will run in non-interactive mode

NOTE: This script is interactive as it asks the password for the user on the command line! 

NOTE This can run into non-interactive mode and accept the password through a CLI pipe. See USAGE for an example of this 

ARGUMENTS


EMAIL                                                       Required email for the user which password we want to change

--no-password                                               If this flag has been specified, the user password will not
                                                            be set.');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('user')->hasMinCharacters(2)->hasMaxCharacters(255)
                     ->select('--no-password')->isOptional()->isBoolean()
                     ->validate();


// Get user
try {
    $user = User::load($argv['user']);

} catch (DataEntryNotExistsException $e) {
    throw $e->makeWarning();
}


// Get the user's password
if ($argv['no_password']) {
    if (CliCommand::hasStdInStream()) {
        throw new OutOfBoundsException(tr('--no-password option specified but received password through pipe'));
    }

    Log::warning(tr('Setting password to empty due to "--no-password" flag'));

    // Update password to empty
    $user->clearPassword();
    Log::success(tr('Successfully cleared password for user ":user"', [':user' => $user->getDisplayName()]));

} else {
    // Read password from stdin stream?
    if (CliCommand::hasStdInStream()) {
        $password          = CliCommand::getStdInStream();
        $password_validate = $password;

    } else {
        // Read password from command line (interactive!)
        while (true) {
            try {
                $password          = Cli::readPassword(tr('Please type the users password:'));
                $password_validate = Cli::readPassword(tr('Please re-type the users password:'));
                break;

            } catch (Throwable) {
                // Password update failed, fall through to try again?
            }

            if ($retries-- > 0) {
                // Borked up, retry!
                Log::warning(tr('Failed to set password, please try again'));
                continue;
            }

            // Yeah, we're done retrying
            throw PasswordFailedException::new('Failed to setup a password for the user');
        }
    }

    // Update password
    $user->changePassword($password, $password_validate);
    Log::success(tr('Successfully set new password for user ":user"', [':user' => $user->getDisplayName()]));
}
