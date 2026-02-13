<?php

/**
 * Class Grep
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Traits\TraitDataObjectDirectory;
use Phoundation\Data\Traits\TraitDataFile;
use Phoundation\Data\Traits\TraitDataStringValue;
use Phoundation\Os\Processes\Commands\Exception\CommandsException;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;


class Grep extends Command
{
    use TraitDataFile;
    use TraitDataObjectDirectory;
    use TraitDataStringValue;

    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param EnumExecuteMethod $method
     *
     * @return array
     */
    public function grep(EnumExecuteMethod $method): array
    {
        if (!$this->_directory and !$this->_file) {
            throw new CommandsException(tr('Cannot execute grep, no file or path specified'));
        }
        if (!$this->value) {
            throw new CommandsException(tr('Cannot execute grep, no filter value specified'));
        }

        // Return results
        return $this->clearArguments()
                    ->setCommand('grep')
                    ->setAcceptedExitCodes([0, 1])
                    ->addArgument($this->value)
                    ->addArgument($this->_directory ?? $this->_file)
                    ->addArgument($this->_directory ? '-R' : null)
                    ->execute($method);
    }
}
