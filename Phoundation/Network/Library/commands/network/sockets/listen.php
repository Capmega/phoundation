<?php

/**
 * Command network public-ip
 *
 * This command will detect and display the public IP address
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Network\Enums\EnumNetworkSocketDomain;
use Phoundation\Network\Sockets\PhoSocket;
use Phoundation\Network\Sockets\SocketServer;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Utils;

$domains = get_enum_names(EnumNetworkSocketDomain::cases());
showdie($domains);
CliDocumentation::setAutoComplete([
    'arguments' => [
        '-p,--port'      => true,
        '-i,--interface' => true,
        '-e,--execute'   => true,
        '-o,--protocol'  => [
            'word'   => function (string $word) { return Arrays::keepMatchingValuesStartingWith(['tcp', 'udp'], $word, Utils::MATCH_CASE_INSENSITIVE); },
            'noword' => function ()             { return ['tcp', 'udp']; },
        ],
        '-d,--domain'  => [
            'word'   => function (string $word) use ($domains) { return Arrays::keepMatchingValuesStartingWith($domains, $word, Utils::MATCH_CASE_INSENSITIVE); },
            'noword' => function ()             use ($domains) { return $domains; },
        ],
    ]
]);

CliDocumentation::setUsage('./pho network sockets listen');

CliDocumentation::setHelp('This command will listen on the specified interface / port and execute the specified command
when data has been received


ARGUMENTS


-p, --port PORT                         The port on which to listen

-e, --execute COMMAND                   The command to execute once data has been received


OPTIONAL ARGUMENTS


[-i, --interface INTERFACE]             The interface on which to listen. If not specified, the command will listen on 
                                        interface 0.0.0.0 (all interfaces)

[-o, --protocol PROTOCOL]               The protocol to use, either "tcp" (default) or "udp"
');


// Get arguments
$argv = ArgvValidator::new()
                     ->select('-d,--domain', true)->isOptional(EnumNetworkSocketDomain::AF_INET)->isInEnum(EnumNetworkSocketDomain::class)
                     ->select('-i,--interface', true)->isOptional('0.0.0.0')->isIp()
                     ->select('-p,--port', true)->isInteger()->isBetween(1, 65535)
                     ->select('-o,--protocol', true)->isOptional('tcp')->isInArray(['tcp', 'udp'])
                     ->select('-e,--execute', true)->doNotValidate()
                     ->validate();


PhoSocket::new()
         ->setDomain($ar)
    ->setProtocol($argv['protocol'])
    ->setPort($argv['port'])
    ->create();

// Start the socket server
Log::cli(SocketServer::new()
                     ->setInterface($argv['interface'])
                     ->setProtocol($argv['protocol'])
                     ->setPort($argv['port'])
                     ->onData(function (string $data) {
                         // This callback will be executed on data reception
                     }));
