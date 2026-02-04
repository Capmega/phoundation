<?php

/**
 * Command accounts roles list
 *
 * This command will list the available roles on this system
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Web\Json\Users;


CliDocumentation::setUsage('./pho accounts users for role [OPTIONS]
./pho accounts users for role god');

CliDocumentation::setHelp('This command will list the users that have the specified role on this system


ARGUMENTS 


-


OPTIONAL ARGUMENTS


[--deleted]                               Will also show deleted roles');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('--delete')->isOptional()->isBoolean()
                     ->select('role')->isVariable()
                     ->validate();


// Load the roles
$users = Users::new()->setQueryBuilderObject(QueryBuilder::new()
                                                         ->addJoin('JOIN `accounts_users_roles` 
                                                                      ON `accounts_users_roles`.`users_id` = `accounts_users`.`id`')
                                                         ->addJoin('JOIN `accounts_roles` 
                                                                      ON `accounts_roles`.`id` = `accounts_users_roles`.`roles_id`'));


// Apply status filter
if ($argv['delete']) {
    Users::new()->getQueryBuilderObject()->addWhere('`accounts_users`.`status` ' . QueryBuilder::is('deleted', 'status'));
}


// Display the table
$users->displayCliTable([
    'user' => tr('User'),
]);
