<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Os\Processes\Commands\Exception\CommandsException;
use Phoundation\Os\Processes\Exception\ProcessFailedException;


/**
 * Class Id
 *
 * This class executes the id command
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class Id extends Command
{
    /**
     * Returns the user, group
     *
     * @param string $section
     * @return int
     */
    public function do(string $section): int
    {
        if (($section != 'u') and ($section != 'g')) {
            throw new OutOfBoundsException(tr('Invalid section ":section" specified. This value can only be "u" or "g"', [
                ':section' => $section
            ]));
        }

        $this->setCommand('id')
             ->addArgument('-' . $section)
             ->setTimeout(1);

        try {
            $output = $this->executeReturnArray();
            $result = reset($output);

            if (!is_numeric($result)) {
                // So which gave us a path that doesn't exist or that we can't access
                throw new CommandsException(tr('Failed to get id'));
            }

            return (int) $result;

        } catch (ProcessFailedException $e) {
            // The command id failed
            static::handleException('rm', $e);
        }
    }
}
