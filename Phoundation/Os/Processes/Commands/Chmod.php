<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Os\Processes\Commands\Exception\CommandsException;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Utils\Strings;


/**
 * Class Chmod
 *
 * This class contains various "chmod" commands
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class Chmod extends Command
{
    /**
     * Returns the realpath for the specified command
     *
     * @param string $file
     * @param string|int $mode
     * @param bool $recurse
     * @return void
     */
    public function do(string $file, string|int $mode, bool $recurse = false): void
    {
        try {
            $mode = Strings::fromOctal($mode);

            $this->setInternalCommand('chmod')
                 ->addArguments([$mode, $file, ($recurse ? '-R' : '')])
                 ->setTimeout(2)
                 ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            // The command chmod failed, most of the time either $file doesn't exist, or we don't have access to change the mode
            static::handleException('chmod', $e, function($first_line, $last_line, $e) use ($file, $mode) {
                if ($e->getCode() == 1) {
                    if (str_contains($last_line, 'no such file or directory')) {
                        throw new CommandsException(tr('Failed to chmod file ":file" to ":mode", it does not exist', [':file' => $file, ':mode' => $mode]));
                    }

                    if (str_contains($last_line, 'operation not permitted')) {
                        throw new CommandsException(tr('Failed to chmod file ":file" to ":mode", permission denied', [':file' => $file, ':mode' => $mode]));
                    }
                }
            });
        }
    }
}
