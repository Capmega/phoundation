<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Traits\DataFile;
use Phoundation\Data\Traits\DataPath;
use Phoundation\Data\Traits\DataValue;
use Phoundation\Os\Processes\Commands\Exception\CommandsException;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;


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
     * @param EnumExecuteMethodInterface $method
     * @return array
     */
    public function grep(EnumExecuteMethodInterface $method): array
    {
        if (!$this->path and !$this->file) {
            throw new CommandsException(tr('Cannot execute grep, no file or path specified'));
        }

        if (!$this->value) {
            throw new CommandsException(tr('Cannot execute grep, no filter value specified'));
        }

        // Return results
        return $this
            ->clearArguments()
            ->setInternalCommand('grep')
            ->addArgument($this->value)
            ->addArgument($this->path ?? $this->file)
            ->addArgument($this->path ? '-R' : null)
            ->execute($method);
   }
}
