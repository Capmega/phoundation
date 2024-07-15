<?php

declare(strict_types=1);

use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Limit;


/**
 * Command accounts/roles/add-roles
 *
 * This command will create a new role with the specified properties
 *
 * @author   Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license  http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyrole Copyrole (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package  Phoundation\Scripts
 */
CliDocumentation::setAutoComplete([
                                      'positions' => [
                                          0  => [
                                              'word'   => 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE COALESCE(`username`, `email`, `code`) LIKE :word AND `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
                                              'noword' => 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
                                          ],
                                          -1 => [
                                              'word'   => 'SELECT `name` FROM `accounts_roles` WHERE `name` LIKE :word AND `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
                                              'noword' => 'SELECT `name` FROM `accounts_roles` WHERE `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
                                          ],
                                      ],
                                  ]);

CliDocumentation::setUsage('./pho accounts users add-roles NAME "ROLE[,ROLE,ROLE,...]"
./pho system accounts users add-roles -n test -d "This is a test role!"');

CliDocumentation::setHelp('This command allows you to add roles to the specified user

ARGUMENTS



EMAIL                                   The identifier email of the user to which the roles should be added

ROLE[,ROLE,ROLE,...]                    The roles linked with the role. Each user that gets this role assigned will 
                                        also get these roles assigned');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('user', true)->isEmail()
                     ->select('roles', true)->isOptional(null)->sanitizeForceArray()->each()->isName()
                     ->validate();


try {
    // Ensure that specified roles exist
    if ($argv['roles']) {
        foreach ($argv['roles'] as &$role) {
            $role = Role::load($role);
        }

        unset($role);
    }

    // Get user and add roles
    $user  = User::load($argv['user']);
    $roles = $user->getRoles();

    foreach ($argv['roles'] as $role) {
        $roles->add(Role::load($role));
    }

    if ($roles->save()) {
        // Done!
        Log::success(tr('Modified user ":user"', [':user' => $user->getDisplayName()]));

    } else {
        // Done!
        Log::warning(tr('User ":user" was not modified', [':user' => $user->getDisplayName()]));
    }

} catch (DataEntryNotExistsException $e) {
    throw $e->makeWarning();
}
