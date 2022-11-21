<?php

namespace Phoundation\Processes\Commands;

use Phoundation\Processes\Exception\ProcessFailedException;



/**
 * Class NetworkCommands
 *
 * This class contains various easy-to-use and ready-to-go command line commands in static methods to manage network
 * processes.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class NetworkCommands extends Command
{
    /**
     * Returns the process id for the specified command
     *
     * @param string $server_restrictions
     * @return ?float
     */
    public function ping(string $server_restrictions): ?float
    {
        try {
            $output = Process::new('pgrep', $this->server_restrictions, true)
                ->addArguments(['-c', 1, $server_restrictions])
                ->setTimeout(1)
                ->executeReturnArray();
            $output = array_pop($output);
showdie($output);

        } catch (ProcessFailedException $e) {
            // The command failed
            Command::handleException('ping', $e, function($first_line, $last_line, $e) use ($file, $mode) {
                if ($e->getCode() == 1) {
//                    if (str_contains($last_line, 'no such file or directory')) {
//                        throw new CommandsException(tr('Failed to chmod file ":file" to ":mode", it does not exist', [':file' => $file, ':mode' => $mode]));
//                    }
                }
            });
        }
    }
}