<?php

/**
 * Class Wget
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataBindAddress;
use Phoundation\Data\Traits\TraitDataSourceString;
use Phoundation\Data\Traits\TraitDataTarget;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;

class Wget extends Command
{
    use TraitDataSourceString;
    use TraitDataTarget;
    use TraitDataBindAddress;

    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param EnumExecuteMethod $method
     *
     * @return int|null
     */
    public function execute(EnumExecuteMethod $method = EnumExecuteMethod::passthru): ?int
    {
        // Build the process parameters, then execute
        $this->clearArguments()
             ->setCommand('wget')
             ->addArgument($this->bind_address ? '--bind-address=' . $this->bind_address : null)
             ->addArguments($this->target ? [
                 '-O',
                 $this->target,
             ] : null)
             ->addArgument($this->source);

        parent::execute($method);

        return null;
    }
}
