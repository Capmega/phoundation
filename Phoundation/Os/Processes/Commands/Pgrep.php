<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Os\Processes\Exception\ProcessFailedException;


/**
 * Class ProcessCommands
 *
 * This class contains various easy-to-use and ready-to-go command line commands in static methods to manage Linux
 * processes.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
class Pgrep extends Command
{
    /**
     * Returns the process id for the specified command
     *
     * @note Returns NULL if the process wasn't found
     *
     * @param string $process
     *
     * @return ?int
     */
    public function do(string $process): ?int
    {
        try {
            $output = $this
                ->setCommand('pgrep')
                ->addArgument($process)
                ->setTimeout(1)
                ->executeReturnArray();

            $output = array_pop($output);

            if (!$output or !is_numeric($output)) {
                return null;
            }

            return (integer)$output;

        } catch (ProcessFailedException $e) {
            static::handleException('pgrep', $e);
        }
    }


    /**
     * Returns the process id's for all children of the specified parent process id
     *
     * @note This method will also return the PID for the pgrep command that was used to create this list!
     *
     * @param int $pid
     *
     * @return array
     */
    public function getChildren(int $pid): array
    {
        try {
            if ($pid < 0) {
                throw new OutOfBoundsException(tr('The specified process id ":pid" is invalid. Please specify a positive integer', [':pid' => $pid]));
            }

            $output = $this
                ->setCommand('pgrep')
                ->addArguments([
                                   '-P',
                                   $pid,
                               ])
                ->setTimeout(1)
                ->executeReturnArray();

            // Remove the pgrep command PID
            unset($output[0]);

            return $output;

        } catch (ProcessFailedException $e) {
            static::handleException('pgrep', $e);
        }
    }
}
