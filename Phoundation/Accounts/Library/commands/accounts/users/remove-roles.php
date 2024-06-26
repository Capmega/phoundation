<?php

declare(strict_types=1);

use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Command accounts/roles/add-right
 *
 * This script will create a new role with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho accounts roles add-right NAME "RIGHT[,RIGHT,RIGHT,...]"
./pho system accounts roles add-right -n test -d "This is a test role!"');

CliDocumentation::setHelp('This command allows you to add rights to the specified role



ARGUMENTS



NAME                                    The identifier name of the role to which the rights shoudl be added

RIGHT[,RIGHT,RIGHT,...]                 The rights linked with the role. Each user that gets this role assigned will 
                                        also get these rights assigned');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('user', true)->isName()
                     ->select('roles', true)->sanitizeForceArray()->each()->isName()
                     ->validate();


try {
    // Ensure that specified roles exist
    if ($argv['roles']) {
        foreach ($argv['roles'] as &$role) {
            $role = Role::load($role);
        }

        unset($role);
    }

    // Get role and remove rights
    $user  = User::load($argv['user']);
    $roles = $user->getRoles();

    foreach ($argv['roles'] as $role) {
        $roles->removeKeys(Role::load($role));
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
