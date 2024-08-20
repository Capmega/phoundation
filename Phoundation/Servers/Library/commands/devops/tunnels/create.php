<?php

/**
 * Command devops tunnels create
 *
 * This command will create an SSH tunnel to another server
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
use Phoundation\Filesystem\FsDirectory;


CliDocumentation::setAutoComplete([
                                      'arguments' => [
                                          '-l,--local-port' => true,
                                          '-p, --port'      => true,
                                          '-h,--host'       => true,
                                          '-s,--server'     => true,
                                      ]
                                  ]);

CliDocumentation::setUsage('./pho devops tunnels create [OPTIONS]
./pho devops tunnels create -l 1234 -h servername -p 80
./pho devops tunnels create -l 4000 -h google.com -p 443 -s localhost');

CliDocumentation::setHelp('This command allows you to create a new SSH tunnel


ARGUMENTS


-l,--local-port                         The port on the localhost to which the application can connect

-h,--host                               The destination host to which the tunnel should connect

-p,--port                               The port on the destination host to which the tunnel should connect


OPTIONAL ARGUMENTS


[-b,--background]                       [false] Run the tunnel in the background. The command will display the PID of  
                                        the background command and the tunnel so that either can be terminated more  
                                        easily later on
                                        
[-i,--ssh-key-file FILE]                The SSH key file to use 

[-k,--ssh-key KEY]                      The SSH key to use

[-s,--server SERVER]                    [localhost] The server on which to create the tunnel.');


// Process the arguments
$argv = ArgvValidator::new()
                     ->select('local_port', true)->isInteger()->isBetween(1, 65535)
                     ->select('port', true)->isInteger()->isBetween(1, 65535)
                     ->select('host', true)->isDomainOrIp()
                     ->select('server')->isOptional('localhost')->isDomainOrIp()
                     ->select('background')->isOptional()->isBoolean()
                     ->select('ssh_key')->isOptional()->isBoolean()
                     ->select('ssh_key_file')->isOptional()->isFile(FsDirectory::getFilesystemRootObject())
                     ->validate();


// Create the SSH tunnel
$tunnel = SshTunnel::new()
    ->setServer($argv['server'])
    ->setLocalPort($argv['local_port'])
    ->setDestinationPort($argv['port'])
    ->setDestinationHost($argv['host'])
    ->setBackground($argv['background'])
    ->setSshKey($argv['ssh_key'])
    ->setSshKeyFile($argv['ssh_key_file']);

$tunnel->execute();


// If the command was executed in the background, return the PID of the command and the tunnel itself so we can easily
// terminate it later
if ($argv['background']) {
    Log::cli(tr('Created SSH tunnel ":tunnel" with PID ":pid", tunnel PID ":tunnel_pid"', [
        'pid'        => $tunnel->getPid(),
        'tunnel_pid' => $tunnel->getTunnelPid(),
        'tunnel'     => $argv['local_port'] . ':' . $argv['host'] . ':' . $argv['port'],
    ]));
}
