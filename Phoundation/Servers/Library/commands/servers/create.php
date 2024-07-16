<?php

/**
 * Command servers/create
 *
 * This command will create a new server with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Servers\Server;

CliDocumentation::setAutoComplete(Server::getAutoComplete());

CliDocumentation::setUsage('./pho servers create [OPTIONS]
./pho system servers create --name phoundation --hostname www.phoundation.org -d "This is a phoundation server" --port 22');

CliDocumentation::setHelp(Server::getHelpText('This script allows you to create servers


ARGUMENTS'));


// Create server.
$server = Server::new()->apply()->save();


// Done!
Log::success(tr('Created new server ":server"', [':server' => $server->getHostName()]));