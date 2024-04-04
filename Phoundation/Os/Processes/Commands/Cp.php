<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;
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
     * @param Restrictions               $source_restrictions
     * @param Stringable|string          $target
     * @param Restrictions               $target_restrictions
     * @param EnumExecuteMethodInterface $method
     *
     * @return void
     */
    public function archive(Stringable|string $source, Restrictions $source_restrictions, Stringable|string $target, Restrictions $target_restrictions, EnumExecuteMethodInterface $method = EnumExecuteMethod::noReturn): void
    {
        $source = (string)$source;
        $target = (string)$target;

        $source_restrictions->check($source, false);
        $target_restrictions->check($target, true);

        Directory::new(dirname($target), $target_restrictions)->ensure();

        // Build the process parameters, then execute
        $this->setCommand('cp')
             ->clearArguments()
             ->addArgument('-a')
             ->addArguments($source)
             ->addArgument($target)
             ->execute($method);
    }
}
