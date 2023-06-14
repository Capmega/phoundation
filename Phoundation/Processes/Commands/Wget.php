<?php

declare(strict_types=1);

namespace Phoundation\Processes\Commands;

use Phoundation\Data\Traits\DataBindAddress;
use Phoundation\Data\Traits\DataSource;
use Phoundation\Data\Traits\DataTarget;
use Phoundation\Processes\Enum\ExecuteMethod;


/**
 * Class Wget
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Wget extends Command
{
    use DataSource;
    use DataTarget;
    use DataBindAddress;


    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param ExecuteMethod $method
     * @return int|null
     */
    public function execute(ExecuteMethod $method = ExecuteMethod::passthru): ?int
    {
        // Build the process parameters, then execute
        $this->process
            ->clearArguments()
            ->setCommand('wget')
            ->addArgument($this->bind_address ? '--bind-address=' . $this->bind_address : null)
            ->addArguments($this->target ? ['-O', $this->target] : null)
            ->addArgument($this->source)
            ->execute($method);

        return null;
    }
}