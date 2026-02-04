<?php

/**
 * Command accounts users list
 *
 * This command will list the available users on this system
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Users;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;


CliDocumentation::setUsage('./pho accounts users list [OPTIONS]
./pho accounts users list -d -r god');

CliDocumentation::setHelp('This command will list the available users on this system


ARGUMENTS


--domains DOMAIN[,DOMAIN,DOMAIN,...]    Will only display users for the specified domains

--deleted                               Will also show deleted users

--roles ROLE[,ROLE,ROLE,...]           Will only display users with the specified role

--rights RIGHT[RIGHT,RIGHT,...]        Will only display users with the specified right');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('--domains,--domain')->isOptional(null)->sanitizeForceArray()->forEachField()->isDomain()
                     ->select('--status')->isOptional(null)->isVariable()
                     ->select('-r,--roles,--role', true)->isOptional(null)->sanitizeForceArray()->forEachField()->isName()
                     ->select('-r,--rights,--right', true)->isOptional(null)->sanitizeForceArray()->forEachField()->isName()
                     ->validate();


$user  = Users::new();
$query = $user->getQueryBuilderObject()
              ->addSelect(' 
                 `accounts_users`.`id`,
                 `accounts_users`.`id` AS `key`,
                 `accounts_users`.`email`, 
                 IFNULL(`accounts_users`.`status`, "' . tr('Ok') . '") AS `status`, 
                 GROUP_CONCAT(CONCAT(UPPER(LEFT(`roles`.`name`, 1)), SUBSTRING(`roles`.`name`, 2)) SEPARATOR ", ") AS `roles` ')
              ->addJoin('LEFT JOIN `accounts_users_roles` AS `users_roles` ON `users_roles`.`users_id` = `accounts_users`.`id`')
              ->addJoin('LEFT JOIN `accounts_roles` AS `roles` ON `roles`.`id` = `users_roles`.`roles_id`')
              ->addGroupBy('`id`, `email`, `status`')
              ->addOrderBy('`accounts_users`.`email`');

if ($argv['roles']) {
    $roles = QueryBuilder::in($argv['roles']);
    $query
        ->addJoin('JOIN `accounts_users_roles` ON `accounts_users_roles`.`users_id` = `accounts_users`.`id`')
        ->addJoin('JOIN `accounts_roles` ON `accounts_roles`.`name` IN (' . implode(', ', array_keys($roles)) . ') AND `accounts_roles`.`id` = `accounts_users_roles`.`roles_id`', $roles);
}

if ($argv['rights']) {
    $rights = QueryBuilder::in($argv['rights']);
    $query
        ->addJoin('JOIN `accounts_users_rights` ON `accounts_users_rights`.`users_id` = `accounts_users`.`id`')
        ->addJoin('JOIN `accounts_rights` ON `accounts_rights`.`name` IN (' . implode(', ', array_keys($rights)) . ') AND `accounts_rights`.`id` = `accounts_users_rights`.`rights_id`', $rights);
}

if ($argv['domains']) {
    $domains = QueryBuilder::in($argv['domains']);
    $query->addWhere('`accounts_users`.`domain` (' . implode(', ', array_keys($domains)) . ')', $domains);
}

if ($argv['status']) {
    $query->addWhere('`accounts_users`.`status` ' . QueryBuilder::is($argv['status'], 'status'));
} else {
    $query->addWhere('`accounts_users`.`status` IS NULL');
}

$user->displayCliTable([
   'key'         => tr('Id'),
   'status'      => tr('Status'),
   'email'       => tr('Email'),
   'last_names'  => tr('Last names'),
   'first_names' => tr('First names'),
   'remote_id'   => tr('Remote user'),
//   'roles'       => tr('Roles'),
]);
