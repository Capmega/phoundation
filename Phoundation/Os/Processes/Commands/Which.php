<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Os\Processes\Commands\Exception\CommandNotFoundException;
use Phoundation\Os\Processes\Commands\Exception\CommandsException;
use Phoundation\Os\Processes\Exception\ProcessFailedException;


/**
 * Class Which
 *
 * This class manages the which command
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Which extends Command
{
    /**
     * Returns the realpath for the specified command
     *
     * @param string $command The command for which the realpath must be known
     * @return string The real path for the specified command
     */
    public function which(string $command): string
    {
        static $cache = [];

        // Do we have this which command in cache?
        if (array_key_exists($command, $cache)) {
            return $cache[$command];
        }

        $this->setInternalCommand('which', false)
             ->addArgument($command)
             ->setRegisterRunfile(false)
             ->setTimeout(1);

        try {
            $output   = $this->executeReturnArray();
            $result   = reset($output);
            $realpath = realpath($result);

            if (!$realpath) {
                // So which gave us a path that doesn't exist or that we can't access
                throw new CommandsException(tr('Failed to get realpath for which result ":result" for command  ":command"', [
                    ':command' => $command,
                    ':result' => $result
                ]));
            }

            // Cache and return
            $cache[$command] = $realpath;
            return $realpath;

        } catch (ProcessFailedException $e) {
            // The command which failed, likely it could not find the requested command
            static::handleException('which', $e, function($first_line, $last_line, $e) use ($command) {
                if ($e->getCode() == 1) {
                    if (!$e->getData()['output']) {
                        throw new CommandNotFoundException(tr('Could not find command ":command"', [
                            ':command' => $command
                        ]));
                    }
                }
            });
        }
    }
}
