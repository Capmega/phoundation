<?php

declare(strict_types=1);

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Script accounts/roles/remove-right
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
                     ->select('role', true)->isName()
                     ->select('rights', true)->isOptional(null)->sanitizeForceArray()->each()->isName()
                     ->validate();


try {
    // Ensure that specified $rights exist
    if ($argv['rights']) {
        foreach ($argv['rights'] as &$right) {
            $right = Right::get($right);
        }

        unset($right);
    }

    // Get role and add rights
    $role = Role::get($argv['role']);
    $role->getRights()->delete($argv['rights']);

} catch (DataEntryNotExistsException $e) {
    throw $e->makeWarning();
}


// Done!
Log::success(tr('Modified role ":role"', [':role' => $role->getName()]));
