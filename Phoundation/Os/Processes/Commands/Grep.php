<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Traits\DataFile;
use Phoundation\Data\Traits\DataDirectory;
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
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class Grep extends Command
{
    use DataFile;
    use DataDirectory;
    use DataValue;


    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param EnumExecuteMethodInterface $method
     * @return array
     */
    public function grep(EnumExecuteMethodInterface $method): array
    {
        if (!$this->directory and !$this->file) {
            throw new CommandsException(tr('Cannot execute grep, no file or path specified'));
        }

        if (!$this->value) {
            throw new CommandsException(tr('Cannot execute grep, no filter value specified'));
        }

        // Return results
        return $this
            ->clearArguments()
            ->setCommand('grep')
            ->addArgument($this->value)
            ->addArgument($this->directory ?? $this->file)
            ->addArgument($this->directory ? '-R' : null)
            ->execute($method);
   }
}
