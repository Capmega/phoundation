<?php

/**
 * Command network sockets clients repeat
 *
 * This command will modify a user with the specified properties
 *
 * @author Harrison Macey <harrison@medinet.ca>
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Network\Sockets\Client;

CliDocumentation::setAutoComplete(User::getAutoComplete([
    'arguments' => [
        '-h,--host'  => true,
        '-p,--port'  => true,
        '-m,--message'  => true,
        '-r,--repeat'   => true,
        '-i,--interval' => true,
    ]
]));

CliDocumentation::setUsage('./pho network sockets client repeat [OPTIONS]
./pho network sockets client repeat --host 127.0.0.1 4096 --message "This is a test"
./pho network sockets client repeat --host 127.0.0.1 4096 --message "This is a test" --repeat 10
./pho network sockets client repeat --host 127.0.0.1 4096 --message "This is a test" --repeat 20 --interval 1000');

CliDocumentation::setHelp('This command will create a PhoSocketClient that connects to the specified host and port
and send a specified message with optional repeating parameters, the number of times it will repeat, and the time
interval between repeats.


ARGUMENTS

-h, --host HOSTNAME                     The IP address to which this client should connect, should be a valid domain

-p, --port PORT_NUMBER                  The port number to which this client should connect, should be between 1 and 
                                        65535


OPTIONAL ARGUMENTS


[-i, --interval NUMBER]                 

[-r, --repeat NUMBER]                   The amount of times the specified message should be sent

');


// Validate user
$argv = ArgvValidator::new()
                     ->select('-h,--host', true)->isString()->isDomain()
                     ->select('-p,--port', true)->isInteger()->isBetween(1, 65536)
                     ->select('-m,--message', true)->hasMaxCharacters(8192)->isPrintable()
                     ->select('-r,--repeat', true)->isOptional(1)->isInteger()->isBetween(0, 1_000_000)
                     ->select('-i,--interval', true)->isOptional(1000)->isInteger()->isBetween(0, 86_400_000)
                     ->validate();


// Connect to remote host
Log::action(tr('Creating client with on ":host::port"',[
    ':host' => $argv['host'],
    ':port' => $argv['port'],
]));

$client = new Client($argv['host'], $argv['port']);
Log::success(tr('Opened connection'));


// Send message loop
for ($i = 1; $i <= $argv['repeat']; ++$i) {
    Log::action(tr('Sending message ":count", length: ":length"', [
        ':count' => $i,
        ':length' => strlen($argv['message'])
    ]));
    $client->send($argv['message']);

    Log::action(tr('Server responded: ":message"',[
        ':message' => $client->receive()
    ]));

    if ($i < $argv['repeat']){
        usleep($argv['interval'] * 1000);
    }
}


// Close connection once loops are done
$client->close();




