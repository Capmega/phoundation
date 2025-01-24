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
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;


class Cp extends Command
{
    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param PhoPathInterface  $source
     * @param PhoPathInterface  $target
     * @param EnumExecuteMethod $method
     *
     * @return void
     */
    public function archive(PhoPathInterface $source, PhoPathInterface $target, EnumExecuteMethod $method = EnumExecuteMethod::noReturn): void
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
