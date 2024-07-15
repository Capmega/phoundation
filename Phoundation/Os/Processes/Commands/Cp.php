<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Stringable;

/**
 * Class Cp
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
class Cp extends Command
{
    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param Stringable|string          $source
     * @param FsRestrictions             $source_restrictions
     * @param Stringable|string          $target
     * @param FsRestrictions             $target_restrictions
     * @param EnumExecuteMethod $method
     *
     * @return void
     */
    public function archive(Stringable|string $source, FsRestrictions $source_restrictions, Stringable|string $target, FsRestrictions $target_restrictions, EnumExecuteMethod $method = EnumExecuteMethod::noReturn): void
    {
        $source = (string) $source;
        $target = (string) $target;
        $source_restrictions->check($source, false);
        $target_restrictions->check($target, true);
        FsDirectory::new(dirname($target), $target_restrictions)
                 ->ensure();
        // Build the process parameters, then execute
        $this->setCommand('cp')
             ->clearArguments()
             ->addArgument('-a')
             ->addArguments($source)
             ->addArgument($target)
             ->execute($method);
    }
}
