<?php

/**
 * Command accounts/rights/list
 *
 * This command will list the available rights on this system
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Accounts\Rights\Rights;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\SqlQueries;

CliDocumentation::setUsage('./pho accounts rights list [OPTIONS]
./pho system accounts rights list -d -r god');

CliDocumentation::setHelp('This command will list the available rights on this system



ARGUMENTS


--deleted                               Will also show deleted rights

-r/--roles ROLE                         Will only display rights associated with the specified role(s)');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('--status')->isOptional(null)->isVariable()
                     ->select('-r,--roles,--role', true)->isOptional(null)->sanitizeForceArray()->each()->isName()
                     ->validate();


$user  = Rights::new();
$query = $user->getQueryBuilder()
              ->addSelect('`accounts_rights`.`id`, 
                       `accounts_rights`.`name`, 
                       IFNULL(`accounts_rights`.`status`, "' . tr('Ok') . '") AS `status`, 
                       GROUP_CONCAT(CONCAT(UPPER(LEFT(`roles`.`name`, 1)), SUBSTRING(`roles`.`name`, 2)) SEPARATOR ", ") AS `roles` ')
              ->addJoin('LEFT JOIN `accounts_roles_rights` AS `roles_rights` ON `roles_rights`.`rights_id` = `accounts_rights`.`id`')
              ->addJoin('LEFT JOIN `accounts_roles` AS `roles` ON `roles`.`id` = `roles_rights`.`roles_id`')
              ->addGroupBy('`id`, `name`, `status`');

if ($argv['roles']) {
    $roles = SqlQueries::in($argv['roles']);
    $query
        ->addJoin('JOIN `accounts_roles_rights` ON `accounts_roles_rights`.`rights_id` = `accounts_rights`.`id`')
        ->addJoin('JOIN `accounts_roles` ON `accounts_roles`.`name` IN (' . implode(', ', array_keys($roles)) . ') AND `accounts_roles`.`id` = `accounts_roles_rights`.`roles_id`', $roles);
}

if ($argv['status']) {
    $query->addWhere('`accounts_rights`.`status` ' . SqlQueries::is($argv['status'], 'status'));
} else {
    $query->addWhere('`accounts_rights`.`status` IS NULL');
}

$user->displayCliTable();
