<?php

declare(strict_types=1);

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\DataBindAddress;
use Phoundation\Data\Traits\DataFile;
use Phoundation\Data\Traits\DataPath;
use Phoundation\Data\Traits\DataSource;
use Phoundation\Data\Traits\DataTarget;
use Phoundation\Data\Traits\DataValue;
use Phoundation\Processes\Commands\Exception\CommandsException;

/**
 * Class Grep
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Grep extends Command
{
    use DataFile;
    use DataPath;
    use DataValue;


    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @return array
     */
    public function execute(): array
    {
        if (!$this->path and !$this->file) {
            throw new CommandsException(tr('Cannot execute grep, no file or path specified'));
        }

        if (!$this->value) {
            throw new CommandsException(tr('Cannot execute grep, no filter value specified'));
        }

        // Return results
        return $this->process
            ->clearArguments()
            ->setCommand('grep')
            ->addArgument($this->value)
            ->addArgument($this->path ?? $this->file)
            ->addArgument($this->path ? '-R' : null)
            ->executeReturnArray();
   }
}