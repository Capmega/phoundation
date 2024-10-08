<?php

declare(strict_types=1);

use Phoundation\Accounts\Servers\Server;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Script servers/delete
 *
 * This script can delete servers
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho servers delete USER_EMAIL');

CliDocumentation::setHelp('This command will delete the specified server. Note that deleted servers will not be removed from the database,
the status for the server will be updated to "deleted"



ARGUMENTS



USER_EMAIL                              The email address for the server to delete');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('server')->isEmail()
                     ->validate();


// Display server data
Server::get($argv['server'])->delete();
