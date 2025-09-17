<?php

/**
 * Command servers modify
 *
 * This command will modify a server with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Limit;
use Phoundation\Servers\Server;


CliDocumentation::setAutoComplete(Server::getAutoComplete([
                                                              'positions' => [
                                                                  0 => [
                                                                      'word'   => 'SELECT `hostname` FROM `servers` WHERE `hostname` LIKE :word AND `status` IS NULL LIMIT ' . Limit::getShellAutoCompletion(),
                                                                      'noword' => 'SELECT `hostname` FROM `servers` WHERE `status` IS NULL LIMIT ' . Limit::getShellAutoCompletion(),
                                                                  ],
                                                              ],
                                                          ]));

CliDocumentation::setUsage('./pho servers modify HOSTNAME [OPTIONS]
./pho servers modify HOSTNAME -l -i --to ENVIRONMENT');

CliDocumentation::setHelp(Server::getHelpText('This command allows you to modify servers


ARGUMENTS


HOSTNAME                                The server to modify. Always specify servers by their hostname '));


// Validate the specified hostname
$argv = ArgvValidator::new()
                     ->select('hostname')->hasMaxCharacters(128)->isDomain()
                     ->validate();


// Get the server, modify, and save
$server = Server::new()->load($argv['hostname'])->apply()->save();


// Done!
Log::success(ts('Modified server ":server"', [':server' => $server->getHostname()]), 10);
