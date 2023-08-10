<?php

declare(strict_types=1);

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\DataBindAddress;
use Phoundation\Data\Traits\DataFile;
use Phoundation\Data\Traits\DataPath;
use Phoundation\Data\Traits\DataProcessName;
use Phoundation\Data\Traits\DataSource;
use Phoundation\Data\Traits\DataTarget;
use Phoundation\Data\Traits\DataValue;
use Phoundation\Processes\Commands\Exception\CommandsException;


/**
 * Class Pgrep
 *
 * pgrep (ProcessGrep) command
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Pgrep extends Command
{
    use DataProcessName;


    /**
     * Execute the pgrep operation and return the found PID's
     *
     * @return array
     */
    public function execute(): array
    {
        if (!$this->process_name) {
            throw new CommandsException(tr('Cannot execute pgrep, no process name specified'));
        }

        // Return results
        return $this->process
            ->clearArguments()
            ->addAcceptedExitCode(1)
            ->setCommand('pgrep')
            ->addArgument($this->process_name)
            ->executeReturnArray();
   }
}