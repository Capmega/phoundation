<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\DataBindAddress;
use Phoundation\Data\Traits\DataSource;
use Phoundation\Data\Traits\DataTarget;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;


/**
 * Class Axel
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Axel extends Command
{
    use DataSource;
    use DataTarget;
    use DataBindAddress;


    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param EnumExecuteMethodInterface $method
     * @return string|int|bool|array|null
     */
    public function download(EnumExecuteMethodInterface $method = EnumExecuteMethod::noReturn): string|int|bool|array|null
    {
        // Build the process parameters, then execute
        $this
            ->clearArguments()
            ->setInternalCommand('axel')
            ->addArgument($this->bind_address ? '--bind-address=' . $this->bind_address : null)
            ->addArguments($this->target ? ['-O ', $this->target] : null)
            ->addArgument($this->source);

        if ($method === EnumExecuteMethod::background) {
            $pid = $this->executeBackground();

            Log::success(tr('Executed wget as a background process with PID ":pid"', [
                ':pid' => $pid
            ]), 4);

            return $pid;

        }

        $results = $this->execute($method);

        Log::notice($results, 4);
        return null;
    }
}
