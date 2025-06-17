<?php

/**
 * Command accounts roles rights add
 *
 * This command will add the specified rights to the selected role, and update all users with said roles to now also
 * have these rights
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setAutoComplete([
    'positions' => [
        0    => function ($word) { return Roles::new()->loadLike(['name' => $word]); },
        null => function ($word) { return Rights::new()->loadLike(['name' => $word]); },
    ],
    'arguments' => [
        '-a,--auto-create' => false
    ]
]);

CliDocumentation::setUsage('./pho accounts roles rights add NAME "RIGHT[,RIGHT,RIGHT,...]"
./pho system accounts roles add-right -n test -d "This is a test role!"');

CliDocumentation::setHelp('This command will add the specified rights to the selected role, and update all users with 
said roles to now also have these rights


ARGUMENTS


NAME                                    The identifier name of the role to which the rights shoudl be added

RIGHT[,RIGHT,RIGHT,...]                 The rights linked with the role. Each user that gets this role assigned will 
                                        also get these rights assigned

                                        
OPTIONAL ARGUMENTS                      


-a, --auto-create                       If specified will create the specified right automatically if it does not exist
                                        yet.');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('role', true)->isName()
                     ->select('rights', true)->isOptional(null)->sanitizeForceArray()->forEachField()->isName()
                     ->select('-a,--auto-create')->isOptional(false)->isBoolean()
                     ->validate();


// Check role exists, get a role, and add rights
try {
    $role = Role::new()->load($argv['role']);
    $role->getRightsObject()->setAutoCreate($argv['auto_create'])->add($argv['rights']);

} catch (DataEntryNotExistsException $e) {
    throw $e->makeWarning();
}


// Done!
Log::success(ts('Modified role ":role"', [':role' => $role->getName()]), 10);
