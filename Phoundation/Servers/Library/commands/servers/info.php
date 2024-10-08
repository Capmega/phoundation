<?php

declare(strict_types=1);

use Phoundation\Accounts\Servers\Server;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Script servers/info
 *
 * This script displays information about the specified server.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho servers info USER');

CliDocumentation::setHelp('This command displays information about the specified server.



ARGUMENTS



USER                                    The server to display information about. Specify either by server id or email
                                        address');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('server')->hasMinCharacters(2)->hasMaxCharacters(255)
                     ->validate();


try {
    // Display server data
    Server::get($argv['server'])->getCliForm();

    Log::information('Roles assigned to this server:');
    Server::get($argv['server'])->roles()->CliDisplayTable();

    Log::information('Roles assigned to this server through its roles:');
    Server::get($argv['server'])->rights()->CliDisplayTable();


} catch (DataEntryNotExistsException $e) {
    throw $e->makeWarning();
}
