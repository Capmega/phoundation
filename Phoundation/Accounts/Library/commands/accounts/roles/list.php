<?php

declare(strict_types=1);

use Phoundation\Accounts\Roles\Roles;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\SqlQueries;


/**
 * Script accounts/roles/list
 *
 * This script will list the available roles on this system
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho accounts roles list [OPTIONS]
./pho system accounts roles list -d -r god');

CliDocumentation::setHelp('This command will list the available roles on this system



ARGUMENTS


--deleted                               Will also show deleted roles

--rights RIGHT[RIGHT,RIGHT,...]        Will only display roles with the specified right');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('--status')->isOptional(null)->isVariable()
                     ->select('-r,--rights,--right', true)->isOptional(null)->sanitizeForceArray()->each()->isName()
                     ->validate();


$roles = Roles::new();
$query = $roles->getQueryBuilder()
               ->addSelect('`accounts_roles`.`id`, 
                 `accounts_roles`.`name`, 
                 IFNULL(`accounts_roles`.`status`, "' . tr('Ok') . '") AS `status`, 
                 GROUP_CONCAT(CONCAT(UPPER(LEFT(`rights`.`name`, 1)), SUBSTRING(`rights`.`name`, 2)) SEPARATOR ", ") AS `rights` ')
               ->addJoin('LEFT JOIN `accounts_roles_rights` AS `roles_rights` ON `roles_rights`.`roles_id` = `accounts_roles`.`id`')
               ->addJoin('LEFT JOIN `accounts_rights` AS `rights` ON `rights`.`id` = `roles_rights`.`rights_id`')
               ->addGroupBy('`id`, `name`, `status`')
               ->addOrderBy('`accounts_roles`.`name`');

if ($argv['rights']) {
    $rights = SqlQueries::in($argv['rights']);
    $query
        ->addJoin('JOIN `accounts_roles_rights` ON `accounts_roles_rights`.`roles_id` = `accounts_roles`.`id`')
        ->addJoin('JOIN `accounts_rights` ON `accounts_rights`.`name` IN (' . implode(', ', array_keys($rights)) . ') AND `accounts_rights`.`id` = `accounts_roles_rights`.`rights_id`', $rights);
}

if ($argv['status']) {
    $query->addWhere('`accounts_roles`.`status` ' . SqlQueries::is($argv['status'], 'status'));
} else {
    $query->addWhere('`accounts_roles`.`status` IS NULL');
}

$roles->displayCliTable();
