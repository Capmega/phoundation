<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;


/**
 * Class Fprint
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class Fprint extends Command
{
    /**
     * Enroll a new fingerprint
     *
     * @param string|int $id
     * @return int
     */
    public function enroll(string|int $id, EnumExecuteMethodInterface $method = EnumExecuteMethod::background): ?int
    {
        // Build the process parameters, then execute
        $this->setCommand('fprint-enroll')
             ->clearArguments()
             ->addArgument($id);

        if ($method === EnumExecuteMethod::background) {
            $pid = $this->executeBackground();

            Log::success(tr('Executed fprint-enroll as a background process with PID ":pid"', [
                ':pid' => $pid
            ]), 4);

            return $pid;
        }

        $results = $this->execute($method);

        Log::notice($results, 4);
        return null;
    }
}
