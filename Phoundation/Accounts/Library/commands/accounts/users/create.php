<?php

declare(strict_types=1);

use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\User;
use Phoundation\Cli\Cli;
use Phoundation\Cli\CliCommand;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Security\Passwords\Exception\PasswordFailedException;


/**
 * Command accounts/users/create
 *
 * This command will create a new user with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

// Documentation
CliDocumentation::setAutoComplete(User::getAutoComplete());

CliDocumentation::setUsage(tr('./pho accounts users create -e EMAIL [OPTIONS]
./pho users create user@example.com -d "This is a test user"'));

CliDocumentation::setHelp(User::getHelpText('This command allows you to create new users.

NOTE: This script is interactive as it asks the password for the user on the command line! ') . '


EXTRA INFORMATION


[--roles / --role / -r ROLE[,ROLE,ROLE]]                    A comma separated list of the roles that this user has on 
                                                            this system. The roles must already exist. These roles will 
                                                            grant the user rights which will give him or her access to 
                                                            the various parts of the system

--no-password                                               If this flag has been specified, the user password will not 
                                                            be set.

[--emails EMAIL/TYPE/DESCRIPTION[,EMAIL/TYPE/DESCRIPTION]]  A comma separated list of extra email addresses, and their 
                                                            types

[--phones PHONE/TYPE/DESCRIPTION[,PHONE/TYPE/DESCRIPTION]]  A comma separated list of extra phone numbers, and their 
                                                            types');


// Set up values
$retries           = 3;
$password          = '';
$password_validate = '';
$user              = User::new()->apply(false);


// Validate user roles
$argv = ArgvValidator::new()
                     ->select('--no-password')->isOptional(false)->isBoolean()
                     ->select('--roles', true)->isOptional()->sanitizeForceArray()->each()->isName()
                     ->validate(false);


// Ensure that specified roles exist
if ($argv['roles']) {
    foreach ($argv['roles'] as &$role) {
        $role = Role::load($role);
    }

    unset($role);
}


// Get the user's password
if ($argv['no_password']) {
    if (CliCommand::hasStdInStream()) {
        throw new OutOfBoundsException(tr('--no-password option specified but received password through pipe'));
    }

    Log::warning(tr('Not setting password due to "--no-password" flag'));

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
}


// Save the user
$user->save();


// Set password?
if (!$argv['no_password']) {
    $user->changePassword($password, $password_validate);
}


// Set users roles
$user->getRoles()->add($argv['roles']);
$user->getEmails()->apply()->save();
$user->getPhones()->apply()->save();


// Done!
Log::success(tr('Created new user ":user"', [':user' => $user->getDisplayName()]));
