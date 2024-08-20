<?php

/**
 * Command accounts roles create
 *
 * This command will create a new role with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho accounts roles create -n NAME [OPTIONS]
./pho system accounts roles create -n test -d "This is a test role!"');

CliDocumentation::setHelp('This command allows you to create roles


ARGUMENTS


-n / --name                             The name for the role

[-d / --description]                    The description for the role

[-r / --rights] RIGHT[,RIGHT,RIGHT,...] The rights for this role');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('role', true)->isName()
                     ->select('-n,--name', true)->isOptional(null)->isName()
                     ->select('-d,--description', true)->isOptional(null)->isDescription()
                     ->select('-r,--rights,--right', true)->isOptional(null)->sanitizeForceArray()->each()->isName()
                     ->validate();


// Ensure that specified roles exist
if ($argv['rights']) {
    foreach ($argv['rights'] as &$right) {
        $right = Right::load($right);
    }

    unset($right);
}


// Load role, ensure the new name doesn't exist yet, then modify it, save it
$role = Role::load($argv['role']);

if ($argv['name']) {
    // If changing name, ensure it doesn't exist yet as it's a unique identifier
    Role::notExists(['name' => $argv['name']], $role->getId(), true);
}

$role->apply(false, $argv)->save();


// Set the rights for this role
$role->getRights()->setRights($argv['rights']);


// Done!
Log::success(tr('Modified role ":role"', [':role' => $role->getName()]));
