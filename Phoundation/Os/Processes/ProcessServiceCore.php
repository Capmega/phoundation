<?php

/**
 * Class ProcessServiceCore
 *
 * This class can manage service / daemon processes running in the background under the PID 1 (init) process
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 * @uses      ProcessVariables
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes;

use Phoundation\Core\Core;
use Phoundation\Os\Processes\Interfaces\ProcessServiceInterface;


class ProcessServiceCore extends ProcessCore implements ProcessServiceInterface
{
    /**
     * Ensures that the current process is running is a service.
     *
     * If the current process is NOT a service (that is, it's PPID is not 1) it will restart the program in the
     * background with nohup
     *
     * @param callable $callback
     *
     * @return static
     */
    public function ensure(callable $callback): static
    {
        if (static::isService()) {
            return $this;
        }

        $callback(ProcessThis::new()->setService(true)->executeBackground());

        return $this;
    }


    /**
     * Returns true if the current service is up and running as a background service
     *
     * @return bool
     */
    public function isService(): bool
    {
        return Core::getPpid() === 1;
    }
}
