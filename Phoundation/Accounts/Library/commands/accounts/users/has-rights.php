<?php

/**
 * Command accounts users has-rights
 *
 * This command displays 1 if the user has the specified rights, or 0 if not
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Limit;


CliDocumentation::setAutoComplete(User::getAutoComplete([
    'positions' => [
        0 => function ($word) {
            return 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE COALESCE(`username`, `email`, `code`) LIKE :word AND `status` IS NULL LIMIT ' . Limit::getShellAutoCompletion();
        },
       -1 => function ($word) {
            return 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE COALESCE(`username`, `email`, `code`) LIKE :word AND `status` IS NULL LIMIT ' . Limit::getShellAutoCompletion();
        },
    ],
]));

CliDocumentation::setUsage('./pho accounts users has-rights EMAIL RIGHT
./pho accounts users has-rights EMAIL RIGHT RIGHT RIGHT');

CliDocumentation::setHelp('This command displays 1 if the user has the specified rights, or 0 if not

If multiple rights are specified, the user must have all the rights for the 1 to display


ARGUMENTS


USER_EMAIL                              The email address for the user to delete

RIGHT                                   The right that this user should have


OPTIONAL ARGUMENTS


[RIGHT, RIGHT, ...]                     Optionally more rights that this user should have');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('user')->isEmail()
                     ->selectAll('rights')->sanitizeForceArray()->forEachField()->isCode()
                     ->validate();


// Display if the user has the specified rights
Log::cli(User::new()->load($argv['user'])->hasAllRights($argv['rights'], null) ? 1 : 0);
