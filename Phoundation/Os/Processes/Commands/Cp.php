<?php

/**
 * Class Cp
 *
 * This class is a wrapper for the shell "cp" command using the Command class
 *
 * @see \Phoundation\Os\Processes\Commands\Command
 * @see \Phoundation\Os\Processes\Process
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;


class Cp extends Command
{
    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param FsPathInterface   $source
     * @param FsPathInterface   $target
     * @param EnumExecuteMethod $method
     *
     * @return void
     */
    public function archive(FsPathInterface $source, FsPathInterface $target, EnumExecuteMethod $method = EnumExecuteMethod::noReturn): void
    {
        $source->checkRestrictions(false);
        $target->checkRestrictions(true)
               ->getParentDirectory()
                   ->ensure();

        // Build the process parameters, then execute
        $this->setCommand('cp')
             ->clearArguments()
             ->addArgument('-a')
             ->addArguments((string) $source)
             ->addArgument((string) $target)
             ->execute($method);
    }
}
