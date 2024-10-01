<?php

/**
 * Command accounts roles list
 *
 * This command will list the available roles on this system
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Accounts\Roles\Roles;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\SqlQueries;


CliDocumentation::setUsage('./pho accounts roles list [OPTIONS]
./pho system accounts roles list -d -r god');

CliDocumentation::setHelp('This command will list the available roles on this system


OPTIONAL ARGUMENTS


[--deleted]                               Will also show deleted roles

[--rights RIGHT... ,RIGHT,RIGHT,...]      Will only display roles with the specified right');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('--status')->isOptional()->isVariable()
                     ->select('-r,--rights,--right', true)->isOptional(null)->sanitizeForceArray()->eachField()->isName()
                     ->validate();


// Load the roles
$roles   = Roles::new();
$builder = $roles->getQueryBuilder();


// Apply rights filter
if ($argv['rights']) {
    $rights = SqlQueries::in($argv['rights']);
    $builder->addJoin('JOIN `accounts_roles_rights` ON `accounts_roles_rights`.`roles_id` = `accounts_roles`.`id`')
            ->addJoin('JOIN `accounts_rights` ON `accounts_rights`.`name` IN (' . implode(', ', array_keys($rights)) . ') AND `accounts_rights`.`id` = `accounts_roles_rights`.`rights_id`', $rights);
}


// Apply status filter
if ($argv['status']) {
    $builder->addWhere('`accounts_roles`.`status` ' . SqlQueries::is($argv['status'], 'status'));

} else {
    $builder->addWhere('`accounts_roles`.`status` IS NULL');
}


// Display the table
$roles->displayCliTable([
    'role'   => tr('Role'),
    'rights' => tr('Rights')
]);
