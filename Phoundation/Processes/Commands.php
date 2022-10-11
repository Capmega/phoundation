<?php

namespace Phoundation\Processes;

use Phoundation\Processes\Exception\CommandsException;
use Phoundation\Processes\Exception\ProcessFailedException;
use Phoundation\Servers\Server;



/**
 * Class Commands
 *
 * This class contains various easy-to-use and ready-to-go command line commands in static methods.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Commands
{
    /**
     * Where will this be executed? Locally or on the specified server
     *
     * @var Server|null $server
     */
    protected ?Server $server = null;



    /**
     * @param Server|null $server
     */
    public function __construct(?Server $server)
    {

    }



    /**
     * Returns the realpath for the specified command
     *
     * @param string $command The command for which the realpath must be known
     * @return string The real path for the specified command
     */
    public static function which(string $command): string
    {
        $process = Processes::create('which')
            ->addArgument($command)
            ->setTimeout(1);

        try {
            $output = $process->executeReturnArray();
            $result = reset($output);
            $realpath = realpath($result);

            if (!$realpath) {
                // So which gave us a path that doesn't exist or that we can't access
                throw new CommandsException(tr('Failed to get realpath for which result ":result" for command  ":command"', [':command' => $command, ':result' => $result]));
            }

            return $realpath;

        } catch (ProcessFailedException $e) {
            // Which failed, likely it could not find the requested command
            if ($e->getData()['exit_code'] == 1) {
                if (!$e->getData()['output']) {
                    throw new CommandsException(tr('The which could not find the specified command ":command"', [':command' => $command]));
                }
            }

            // Something else went wrong
            throw new CommandsException(tr('The which failed for command ":command"', [':command' => $command]));
        }
    }



    /**
     * Returns the realpath for the specified command
     *
     * @param string $file
     * @param string $mode
     * @param bool $recurse
     * @return void
     */
    public static function chmod(string $file, string $mode, bool $recurse = false): void
    {
        try {
            if (is_numeric($mode)) {
                $mode = sprintf('0%o', $mode);
            }

            Processes::create('/usr/bin/chmod')
                ->addArguments([$mode, $file, $recurse ?? '-R'])
                ->setTimeout(2)
                ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // Chmod failed, most of the time either $file doesn't exist, or we don't have access to change the mode
            if ($e->getData()['exit_code'] == 1) {
showdie($e);
                if (!$e->getData()['output']) {
                    throw new CommandsException(tr('The which could not find the specified command ":command"', [':command' => $command]));
                }
            }

            // Something else went wrong
            throw new CommandsException(tr('The which failed for command ":command"', [':command' => $command]));
        }
    }



    /**
     * Returns a commands object for the specified server so that we can execute commands there
     *
     * @param string|null $name
     * @return Commands
     */
    public static function server(?string $name = null): Commands
    {
        $server = null;

        if ($name) {
            $server = new Server($name);
        }

        return new Commands($server);
    }
}