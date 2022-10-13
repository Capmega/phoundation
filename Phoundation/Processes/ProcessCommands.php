<?php

namespace Phoundation\Processes;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Processes\Exception\ProcessFailedException;



/**
 * Class ProcessCommands
 *
 * This class contains various easy-to-use and ready-to-go command line commands in static methods to manage Linux
 * processes.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class ProcessCommands extends Commands
{
    /**
     * Returns the process id for the specified command
     *
     * @note Returns NULL if the process wasn't found
     * @param string $process
     * @return ?int
     */
    public function pgrep(string $process): ?int
    {
        try {
            $output = Processes::create('pgrep', $this->server, true)
                ->addArgument($process)
                ->setTimeout(1)
                ->executeReturnArray();
            $output = array_pop($output);

            if (!$output or !is_numeric($output)) {
                return null;
            }

            return (integer) $output;

        } catch (ProcessFailedException $e) {
            return null;
        }
    }



    /**
     * Returns the process id's for all children of the specified parent process id
     *
     * @note This method will also return the PID for the pgrep command that was used to create this list!
     * @param int $pid
     * @return array
     */
    public function getChildren(int $pid): array
    {
        try {
            if ($pid < 0) {
                throw new OutOfBoundsException(tr('The specified process id ":pid" is invalid. Please specify a positive integer', [':pid' => $pid]));
            }

            $children = Processes::create('pgrep', $this->server, true)
                ->addArguments(['-P', $pid])
                ->setTimeout(1)
                ->executeReturnArray();

            // Remove the pgrep command PID
            unset($children[0]);

            return $children;

        } catch (ProcessFailedException $e) {
            // The command id failed
            Commands::handleException('rm', $e);
        }
    }
}