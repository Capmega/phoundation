<?php

declare(strict_types=1);

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Script accounts/roles/create
 *
 * This script will create a new role with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho accounts roles create NAME [OPTIONS]
./pho system accounts roles create test -d "This is a test role!"');

CliDocumentation::setHelp('This command allows you to create roles


ARGUMENTS


NAME                                    The name for the role

[-d,--description]                      The description for the role

[-r,--rights,--right "RIGHT RIGHT..."]  The rights associated wit this role');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('name', true)->isName()
                     ->select('-d,--description', true)->isOptional(null)->isDescription()
                     ->select('-r,--rights,--right', true)->isOptional(null)->sanitizeForceArray()->each()->isName()
                     ->validate();


// Check if the role already exists
Role::notExists($argv['name'], 'name', null, true);


// Ensure that specified rights exist
if ($argv['rights']) {
    foreach ($argv['rights'] as &$right) {
        $right = Right::load($right);
    }

    unset($right);
}


// Create role and save it
show($argv);
$role = Role::new()->apply(false, $argv)->save();
show($role);


// Set the rights for this role
$role->getRights()->setRights($argv['rights']);


// Done!
Log::success(tr('Created new role ":role"', [':role' => $role->getName()]));
