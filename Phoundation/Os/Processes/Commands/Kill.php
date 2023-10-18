<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Os\Processes\Exception\ProcessFailedException;


/**
 * Class Kill
 *
 * This class contains various "kill" commands
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Kill extends Command
{
    /**
     * Sends the specified signal to the specified process ids
     *
     * @param int $signal
     * @param array|int $pids
     * @return void
     */
    public function pid(int $signal, array|int $pids): void
    {
        try {
            // Validate arguments
            if (($signal < 1) or ($signal > 64)) {
                throw new OutOfBoundsException(tr('Specified signal ":signal" is invalid, ensure it is an integer number between 1 and 64', [':signal' => $signal]));
            }

            foreach ($pids as $pid) {
                if (!is_integer($pid)) {
                    throw new OutOfBoundsException(tr('Specified pid ":pid" is invalid, it should be an integer number 2 or higher', [':pid' => $pid]));
                }

                if (($pid < 2)) {
                    throw new OutOfBoundsException(tr('Specified pid ":pid" is invalid, it should be an integer number 2 or higher', [':pid' => $pid]));
                }
            }

            $this
                ->setInternalCommand('kill')
                ->addArgument('-' . $signal)
                ->addArguments($pids)
                ->setTimeout(10)
                ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command kill failed
            static::handleException('kill', $e);
        }
    }


    /**
     * Sends the specified signal to the specified process names
     *
     * @param int $signal
     * @param array|string $processes
     * @return void
     */
    public function processes(int $signal, array|string $processes): void
    {
        try {
            // Validate arguments
            if (($signal < 1) or ($signal > 64)) {
                throw new OutOfBoundsException(tr('Specified signal ":signal" is invalid, ensure it is an integer number between 1 and 64', [':signal' => $signal]));
            }

            foreach ($processes as $process) {
                if (!is_scalar($process)) {
                    throw new OutOfBoundsException(tr('Specified process ":process" is invalid, it should be a string', [':process' => $process]));
                }

                if (strlen($process) < 2) {
                    throw new OutOfBoundsException(tr('Specified process ":process" is invalid, it should be 2 characters or more', [':process' => $process]));
                }
            }

            $this
                ->setInternalCommand('pkill')
                ->addArgument('-' . $signal)
                ->addArguments($processes)
                ->setTimeout(10)
                ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command pkill failed
            static::handleException('pkill', $e);
        }
    }
}
