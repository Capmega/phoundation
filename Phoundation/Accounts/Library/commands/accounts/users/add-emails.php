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
 * Script accounts/roles/add-email
 *
 * This script will create a new role with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyemail Copyemail (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
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

CliDocumentation::setUsage('./pho accounts roles add-email NAME "EMAIL[,EMAIL,EMAIL,...]"
./pho system accounts roles add-email -n test -d "This is a test role!"');

CliDocumentation::setHelp('This command allows you to add emails to the specified role

ARGUMENTS



NAME                                    The identifier email of the user to which the emails should be added

EMAIL[,EMAIL,EMAIL,...]                 The emails to add to the user');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('user', true)->isEmail()
                     ->select('--emails', true)->isOptional()->sanitizeForceArray()->each()->isEmail()
                     ->validate();


try {
    // Ensure that specified roles exist
    if ($argv['roles']) {
        foreach ($argv['roles'] as &$role) {
            $role = Role::load($role);
        }

        unset($role);
    }

    // Get role and add emails
    $user = User::getFromEmail($argv['user']);
    $user->getEmails()->add($argv['roles']);

} catch (DataEntryNotExistsException $e) {
    throw $e->makeWarning();
}


// Done!
Log::success(tr('Modified user ":user"', [':user' => $user->getDisplayName()]));
