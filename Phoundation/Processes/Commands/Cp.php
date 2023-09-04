<?php

declare(strict_types=1);

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\DataBindAddress;
use Phoundation\Data\Traits\DataSource;
use Phoundation\Data\Traits\DataTarget;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Enum\ExecuteMethod;
use Phoundation\Processes\Enum\Interfaces\ExecuteMethodInterface;
use Stringable;


/**
 * Class Cp
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Cp extends Command
{
    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param Stringable|string $source
     * @param Restrictions $source_restrictions
     * @param Stringable|string $target
     * @param Restrictions $target_restrictions
     * @param ExecuteMethodInterface $method
     * @return void
     */
    public function archive(Stringable|string $source, Restrictions $source_restrictions, Stringable|string $target, Restrictions $target_restrictions, ExecuteMethodInterface $method = ExecuteMethod::noReturn): void
    {
        $source = (string) $source;
        $target = (string) $target;

        $source_restrictions->check($source, false);
        $target_restrictions->check($target, true);

        // Build the process parameters, then execute
        $this->setInternalCommand('cp')
             ->clearArguments()
             ->addArgument('-a')
             ->addArguments($source)
             ->addArgument($target)
             ->execute($method);
    }
}
