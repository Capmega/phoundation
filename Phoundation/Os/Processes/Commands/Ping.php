<?php

/**
 * Class Ping
 *
 * This class contains various "ping" commands
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Os\Processes\Exception\ProcessFailedException;

class Ping extends Command
{
    /**
     * Returns the process id for the specified command
     *
     * @param string $restrictions
     *
     * @return ?float
     */
    public function ping(string $restrictions): ?float
    {
        try {
            $output = $this->setCommand('ping')
                           ->addArguments([
                               '-c',
                               1,
                               $restrictions,
                           ])
                           ->setTimeout(1)
                           ->executeReturnArray();
            $output = array_pop($output);
            showdie($output);

        } catch (ProcessFailedException $e) {
            // The command failed
            static::handleException('ping', $e, function ($first_line, $last_line, $e) use ($file, $mode) {
                if ($e->getCode() == 1) {
//                    if (str_contains($last_line, 'no such file or directory')) {
//                        throw new CommandsException(tr('Failed to chmod file ":file" to ":mode", it does not exist', [':file' => $file, ':mode' => $mode]));
//                    }
                }
            });
        }
    }
}
