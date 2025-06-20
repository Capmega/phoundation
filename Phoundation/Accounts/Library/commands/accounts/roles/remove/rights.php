<?php

/**
 * Command accounts roles remove rights
 *
 * This command will remove the specified rights from the specified role
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Rights\Right;
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
]);

CliDocumentation::setUsage('./pho accounts roles remove rights ROLE "RIGHT[,RIGHT,RIGHT,...]"
./pho accounts roles remove rights test right1 right 2');

CliDocumentation::setHelp('This command allows you to remove rights from the specified role

Any rights removed from the role will also be removed from each user that has that role


ARGUMENTS


ROLE                                    The identifier name of the role to which the rights should be added

RIGHT[,RIGHT,RIGHT,...]                 The rights linked with the role. Each user that gets this role assigned will 
                                        also get these rights assigned');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('role', true)->isName()
                     ->selectAll('rights')->sanitizeForceArray()->forEachField()->isName()
                     ->validate();


try {
    // Ensure that specified $rights exist
    foreach ($argv['rights'] as &$right) {
        $right = Right::new()->load($right);
    }

    unset($right);

    // Get the role and add rights
    $role = Role::new()->load($argv['role']);
    $role->getRightsObject()->delete($argv['rights']);

} catch (DataEntryNotExistsException $e) {
    throw $e->makeWarning();
}


// Done!
Log::success(ts('Modified role ":role"', [':role' => $role->getName()]), 10);
