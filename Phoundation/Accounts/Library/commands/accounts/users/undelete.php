<?php

declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Limit;


/**
 * Script accounts/users/undelete
 *
 * This script can undelete users
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

CliDocumentation::setAutoComplete(User::getAutoComplete([
                                                            'positions' => [
                                                                0 => [
                                                                    'word'   => 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE COALESCE(`username`, `email`, `code`) LIKE :word AND `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
                                                                    'noword' => 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
                                                                ],
                                                            ],
                                                        ]));

CliDocumentation::setUsage('./pho accounts users undelete USER_EMAIL');

CliDocumentation::setHelp('This command will undelete the specified user. Note that undeleted users will have the
status updated to NULL

ARGUMENTS


USER_EMAIL                              The email address for the user to undelete');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('user')->isEmail()
                     ->validate();


// Undelete the user
User::load($argv['user'])->undelete();
