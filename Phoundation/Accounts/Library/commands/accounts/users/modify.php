<?php

/**
 * Command accounts users modify
 *
 * This command will modify a user with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setAutoComplete(User::getAutoComplete([
                                                            'positions' => [
                                                                0 => [
                                                                    'word'   => 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE COALESCE(`username`, `email`, `code`) LIKE :word AND `status` IS NULL',
                                                                    'noword' => 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE `status` IS NULL',
                                                                ],
                                                            ],
                                                        ]));

CliDocumentation::setUsage('./pho accounts users modify USER [OPTIONS]
./pho system users modify USER -l -i --to ENVIRONMENT');

CliDocumentation::setHelp(User::getHelpText('This script allows you to modify users

This script allows you to modify all user data, with the exception of the password or status. If you wish to change the 
status, please use the "delete", "undelete" or "status" commands. To change the users password please use the "password" 
command.') . '


EXTRA INFORMATION


[--roles / --role / -r ROLE[,ROLE,ROLE]]                    A comma separated list of the roles that this user has on 
                                                            this system. The roles must already exist. These roles will 
                                                            grant the user rights which will give him or her access to 
                                                            the various parts of the system

[--emails EMAIL/TYPE/DESCRIPTION[,EMAIL/TYPE/DESCRIPTION]]  A comma separated list of extra email addresses, and their 
                                                            types

[--phones PHONE/TYPE/DESCRIPTION[,PHONE/TYPE/DESCRIPTION]]  A comma separated list of extra phone numbers, and their 
                                                            types');


// Validate user
$argv = ArgvValidator::new()
                     ->select('user', true)->hasMaxCharacters(128)->isEmail()
                     ->select('roles', true)->isOptional()->sanitizeForceArray()->eachField()->isName()
                     ->select('emails', true)->isOptional()->sanitizeForceArray()->eachField()->matchesRegex('/^.+?\/(?:personal|business|other/.+?)$/i')
                     ->select('phones', true)->isOptional()->sanitizeForceArray()->eachField()->matchesRegex('/^.+?\/(?:personal|business|other/.+?)$/i')
                     ->validate();


// Ensure that the specified roles exist
if ($argv['roles']) {
    foreach ($argv['roles'] as &$role) {
        $role = Role::load($role);
    }

    unset($role);
}


// Get the user and modify, then update roles
$user = User::find([
    'username' => $argv['user'],
    'email'    => $argv['user'],
    'code'     => $argv['user'],
], filter: 'OR')->apply()->save();

$user->getRolesObject()->setRoles($argv['roles']);
$user->getEmailsObject()->apply()->save();
$user->getPhonesObject()->apply()->save();


// Done!
Log::success(tr('Modified user ":user"', [':user' => $user->getDisplayName()]));
