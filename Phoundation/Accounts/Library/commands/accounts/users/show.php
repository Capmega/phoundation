<?php

/**
 * Command accounts users show
 *
 * This script displays information about the specified user.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Limit;


CliDocumentation::setAutoComplete(User::getAutoComplete([
    'positions' => [
        0 => [
            'word'   => 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE COALESCE(`username`, `email`, `code`) LIKE :word AND `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
            'noword' => 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
        ],
    ],
]));

CliDocumentation::setUsage('./pho accounts users show USER');

CliDocumentation::setHelp(User::getHelpText('This script displays information about the specified user.  


ARGUMENTS


USER                                    The user to display information about. Specify either by user id or email 
                                        address'));


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('user')->hasMinCharacters(2)->hasMaxCharacters(255)
                     ->validate();


// Display user data
$user = User::load($argv['user']);
$user->displayCliForm();


// Display extra email addresses
Log::cli();
Log::information('Extra email addresses:', echo_prefix: false);

Log::cli();
$user->getEmailsObject()->displayCliTable([
    'email'        => tr('Email address'),
    'account_type' => tr('Email address type'),
]);


// Display extra phone numbers
Log::cli();
Log::information('Extra phone numbers:', echo_prefix: false);

Log::cli();
$user->getPhonesObject()->displayCliTable([
    'phone'        => tr('Phone number'),
    'account_type' => tr('Phone number type'),
]);


// Display roles
Log::cli();
$user->getRolesObject()->displayCliTable([
    'role' => tr('Roles assigned to this user:'),
]);


// Display rights
Log::cli();
$user->getRightsObject()->displayCliTable([
    'right' => tr('Rights assigned to this user through its roles:'),
]);
