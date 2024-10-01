<?php

/**
 * Command servers authenticate
 *
 * This script can be used to test the authentication for the specified server
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Servers\Server;


CliDocumentation::setUsage('./pho servers authenticate USER');

CliDocumentation::setHelp('This command can be used to test the authentication for the specified server


ARGUMENTS


-');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('server')->hasMinCharacters(2)->hasMaxCharacters(255)
                     ->validate();


try {
    // Get a password and try to authenticate
    $password = Cli::readPassword(tr('Password:'));
    $server   = Server::authenticate($argv['server'], $password);

} catch (DataEntryNotExistsException $e) {
    throw $e->makeWarning();
}


Log::success(tr('Server ":server" was authenticated successfully', [':server' => $server->getDisplayName()]));
