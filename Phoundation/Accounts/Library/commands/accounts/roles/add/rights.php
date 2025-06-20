<?php

/**
 * Command accounts roles add rights
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
        0    => function ($word) { return Roles::new()->loadForAutocomplete($word, 'name'); },
        null => function ($word) { return Rights::new()->loadForAutocomplete($word, 'name'); },
    ],
    'arguments' => [
        '-a,--auto-create' => false
    ]
]);

CliDocumentation::setUsage('./pho accounts roles add rights ROLE "RIGHT[,RIGHT,RIGHT,...]"
./pho accounts roles add rights role_name right1 right2 right3');

CliDocumentation::setHelp('This command will add the specified rights to the selected role, and update all users with 
said roles to now also have these rights


ARGUMENTS


NAME                                    The identifier name of the role to which the rights should be added

RIGHT[ RIGHT RIGHT ...]                 The rights linked with the role. Each user that gets this role assigned will 
                                        also get these rights assigned

                                        
OPTIONAL ARGUMENTS                      


-a, --auto-create                       If specified will create the specified right automatically if it does not yet 
                                        exist');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('-a,--auto-create')->isOptional(false)->isBoolean()
                     ->select('role', true)->isName()
                     ->selectAll('rights')->sanitizeForceArray()->forEachField()->isName()
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
