<?php

namespace Phoundation\Processes\Commands;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Processes\Commands\Exception\CommandsException;
use Phoundation\Processes\Exception\ProcessFailedException;
use Phoundation\Processes\Process;



/**
 * Class SystemCommands
 *
 * This class contains various easy-to-use and ready-to-go command line commands in static methods.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class SystemCommands extends Command
{
    /**
     * Returns the realpath for the specified command
     *
     * @param string $command The command for which the realpath must be known
     * @return string The real path for the specified command
     */
    public function which(string $command): string
    {
        $process = Process::new('which', $this->server)
            ->addArgument($command)
            ->setRegisterRunfile(false)
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
            // The command which failed, likely it could not find the requested command
            Command::handleException('rm', $e, function($first_line, $last_line, $e) use ($command) {
                if ($e->getCode() == 1) {
                    if (!$e->getData()['output']) {
                        throw new CommandsException(tr('The which could not find the specified command ":command"', [':command' => $command]));
                    }
                }
            });
        }
    }



    /**
     * Returns the user, group
     *
     * @param string $section
     * @return int
     */
    public function id(string $section): int
    {
        if (($section != 'u') and ($section != 'g')) {
            throw new OutOfBoundsException(tr('Invalid section ":section" specified. This value can only be "u" or "g"', [':section' => $section]));
        }

        $process = Process::new('id', $this->server)
            ->addArgument('-' . $section)
            ->setTimeout(1);

        try {
            $output = $process->executeReturnArray();
            $result = reset($output);

            if (!$result or !is_numeric($result)) {
                // So which gave us a path that doesn't exist or that we can't access
                throw new CommandsException(tr('Failed to get id'));
            }

            return (int) $result;

        } catch (ProcessFailedException $e) {
            // The command id failed
            Command::handleException('rm', $e);
        }
    }



    /**
     * Install the specified packages
     *
     * @param array|string $packages
     * @return array
     */
    public function aptGetInstall(array|string $packages): array
    {
        $process = Process::new('apt-get', $this->server)
            ->setSudo(true)
            ->addArguments(['-y', 'install'])
            ->addArguments($packages)
            ->setTimeout(1);

        return $process->executeReturnArray();
    }
}