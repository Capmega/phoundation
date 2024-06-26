<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataBindAddress;
use Phoundation\Data\Traits\TraitDataSourceString;
use Phoundation\Data\Traits\TraitDataTarget;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;

/**
 * Class Axel
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
class Axel extends Command
{
    use TraitDataSourceString;
    use TraitDataTarget;
    use TraitDataBindAddress;

    /**
     * ExecuteExecuteInterface the rsync operation and return the PID (background) or -1
     *
     * @param EnumExecuteMethod $method
     *
     * @return string|int|bool|array|null
     */
    public function download(EnumExecuteMethod $method = EnumExecuteMethod::noReturn): string|int|bool|array|null
    {
        // Build the process parameters, then execute
        $this->clearArguments()
             ->setCommand('axel')
             ->addArgument($this->bind_address ? '--bind-address=' . $this->bind_address : null)
             ->addArguments($this->target ? [
                 '-O ',
                 $this->target,
             ] : null)
             ->addArgument($this->source);
        if ($method === EnumExecuteMethod::background) {
            $pid = $this->executeBackground();
            Log::success(tr('Executed wget as a background process with PID ":pid"', [
                ':pid' => $pid,
            ]), 4);

            return $pid;

        }
        $results = $this->execute($method);
        Log::notice($results, 4);

        return null;
    }
}
