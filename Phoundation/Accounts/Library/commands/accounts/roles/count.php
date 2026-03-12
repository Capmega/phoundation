<?php

/**
 * Command accounts roles count
 *
 * This command displays the number of roles available on this system
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


CliDocumentation::setUsage('./pho accounts roles count');

CliDocumentation::setHelp(User::getHelpText('This command displays the number of roles available on this system  


ARGUMENTS 


-


OPTIONAL ARGUMENTS


-A, --all                               If specified, will include roles that are deleted'));


// This command allows no parameters
$argv = ArgvValidator::new()->validate();


// Display the number of roles
if (ALL) {
    Log::cli(sql()->getColumn('SELECT COUNT(*) AS `count` FROM `accounts_roles`'));

} else {
    Log::cli(sql()->getColumn('SELECT COUNT(*) AS `count` FROM `accounts_roles` WHERE `status` IS NULL OR `status` NOT LIKE "deleted%"'));
}