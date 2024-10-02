<?php

/**
 * Command accounts users lock
 *
 * This script can lock users
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Accounts\Users\Users;
use Phoundation\Cli\CliDocumentation;
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

CliDocumentation::setUsage('./pho accounts users lock USER_EMAIL
./pho accounts users lock -A -F (DANGEROUS)');

CliDocumentation::setHelp('This command will lock the specified user. Note that locked users will not be removed from
the database, the status for the user will be updated to "locked"

ARGUMENTS


USER_EMAIL                              The email address for the user to lock');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('user')->isOptionalIfTrue(ALL, null)->isEmail()
                     ->validate();


// Lock either all NULL users, or the specified user
if (ALL) {
    if (!FORCE) {
        throw new OutOfBoundsException(tr('Cannot lock all users (due to --all option), this requires --force as well'));
    }

    // Lock all users, DANGEROUS
    Users::new()->load()->lock();

} else {
    // Lock this user
    User::load($argv['user'])->lock();
}
