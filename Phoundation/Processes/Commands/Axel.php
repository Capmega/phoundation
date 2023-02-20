<?php

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\BindAddress;
use Phoundation\Data\Traits\Source;
use Phoundation\Data\Traits\Target;



/**
 * Class Axel
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Axel extends Command
{
    use Source;
    use Target;
    use BindAddress;



    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param bool $background
     * @return int|null
     */
    public function execute(bool $background = false): ?int
    {
        // Build the process parameters, then execute
        $this->process
            ->clearArguments()
            ->setCommand('axel')
            ->addArgument($this->bind_address ? '--bind-address=' . $this->bind_address : null)
            ->addArguments($this->target ? ['-O ', $this->target] : null)
            ->addArgument($this->source);

        if ($background) {
            $pid = $this->process->executeBackground();

            Log::success(tr('Executed wget as a background process with PID ":pid"', [
                ':pid' => $pid
            ]), 4);

            return $pid;

        }

        $results = $this->process->executeReturnArray();

        Log::notice($results, 4);
        return null;
    }
}