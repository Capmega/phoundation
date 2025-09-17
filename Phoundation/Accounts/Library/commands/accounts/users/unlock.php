<?php

/**
 * Command accounts users unlock
 *
 * This command can unlock users
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Limit;


CliDocumentation::setAutoComplete(User::getAutoComplete([
                                                            'positions' => [
                                                                0 => [
                                                                    'word'   => 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE COALESCE(`username`, `email`, `code`) LIKE :word AND `status` = "locked" LIMIT ' . Limit::getShellAutoCompletion(),
                                                                    'noword' => 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE `status` = "locked" LIMIT ' . Limit::getShellAutoCompletion(),
                                                                ],
                                                            ],
                                                        ]));

CliDocumentation::setUsage('./pho accounts users unlock USER_EMAIL');

CliDocumentation::setHelp('This command will unlock the specified user. Note that unlocked users will have the status
 updated to NULL


ARGUMENTS


USER_EMAIL                              The email address for the user to unlock');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('user')->isEmail()
                     ->validate();


// Unlock this user
User::new()->load($argv['user'])->unlock();
