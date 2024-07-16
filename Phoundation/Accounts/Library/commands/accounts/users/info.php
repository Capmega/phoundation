<?php

/**
 * Command accounts/users/info
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

CliDocumentation::setUsage('./pho accounts users info USER');

CliDocumentation::setHelp('This command displays information about the specified user  

ARGUMENTS



USER                                    The user to display information about. Specify either by user id or email 
                                        address');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('user')->hasMinCharacters(2)->hasMaxCharacters(255)
                     ->validate();


$user = User::load($argv['user']);

// Display user data
$user->displayCliForm();

Log::information('Alternative email accounts for this user:');
$user->getEmailsObject()->displayCliTable();

Log::information('Roles assigned to this user:');
$user->getRolesObject()->displayCliTable();

Log::information('Roles assigned to this user through its roles:');
$user->getRightsObject()->displayCliTable();
