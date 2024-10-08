<?php

declare(strict_types=1);

use Phoundation\Accounts\Users\Users;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\SqlQueries;


/**
 * Script accounts/users/list
 *
 * This script will list the available users on this system
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho accounts users list [OPTIONS]
./pho system accounts users list -d -r god');

CliDocumentation::setHelp('This command will list the available users on this system



ARGUMENTS


--domains DOMAIN[,DOMAIN,DOMAIN,...]    Will only display users for the specified domains

--deleted                               Will also show deleted users

--roles ROLE[,ROLE,ROLE,...]           Will only display users with the specified role

--rights RIGHT[RIGHT,RIGHT,...]        Will only display users with the specified right');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('--domains,--domain')->isOptional(null)->sanitizeForceArray()->each()->isDomain()
                     ->select('--status')->isOptional(null)->isVariable()
                     ->select('-r,--roles,--role', true)->isOptional(null)->sanitizeForceArray()->each()->isName()
                     ->select('-r,--rights,--right', true)->isOptional(null)->sanitizeForceArray()->each()->isName()
                     ->validate();


$user  = Users::new();
$query = $user->getQueryBuilder()
              ->addSelect('`accounts_users`.`id`, 
                 `accounts_users`.`email`, 
                 IFNULL(`accounts_users`.`status`, "' . tr('Ok') . '") AS `status`, 
                 GROUP_CONCAT(CONCAT(UPPER(LEFT(`roles`.`name`, 1)), SUBSTRING(`roles`.`name`, 2)) SEPARATOR ", ") AS `roles` ')
              ->addJoin('LEFT JOIN `accounts_users_roles` AS `users_roles` ON `users_roles`.`users_id` = `accounts_users`.`id`')
              ->addJoin('LEFT JOIN `accounts_roles` AS `roles` ON `roles`.`id` = `users_roles`.`roles_id`')
              ->addGroupBy('`id`, `email`, `status`')
              ->addOrderBy('`accounts_users`.`email`');

if ($argv['roles']) {
    $roles = SqlQueries::in($argv['roles']);
    $query
        ->addJoin('JOIN `accounts_users_roles` ON `accounts_users_roles`.`users_id` = `accounts_users`.`id`')
        ->addJoin('JOIN `accounts_roles` ON `accounts_roles`.`name` IN (' . implode(', ', array_keys($roles)) . ') AND `accounts_roles`.`id` = `accounts_users_roles`.`roles_id`', $roles);
}

if ($argv['rights']) {
    $rights = SqlQueries::in($argv['rights']);
    $query
        ->addJoin('JOIN `accounts_users_rights` ON `accounts_users_rights`.`users_id` = `accounts_users`.`id`')
        ->addJoin('JOIN `accounts_rights` ON `accounts_rights`.`name` IN (' . implode(', ', array_keys($rights)) . ') AND `accounts_rights`.`id` = `accounts_users_rights`.`rights_id`', $rights);
}

if ($argv['domains']) {
    $domains = SqlQueries::in($argv['domains']);
    $query->addWhere('`accounts_users`.`domain` (' . implode(', ', array_keys($domains)) . ')', $domains);
}

if ($argv['status']) {
    $query->addWhere('`accounts_users`.`status` ' . SqlQueries::is($argv['status'], 'status'));
} else {
    $query->addWhere('`accounts_users`.`status` IS NULL');
}

$user->displayCliTable();
