<?php

declare(strict_types=1);

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;


/**
 * Class Free
 *
 * This class manages the "free" command
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Free extends Command
{
    /**
     * Returns the available amount of memory
     *
     * @return array
     */
    public function free(): array
    {
        $output = $this
            ->setInternalCommand('free')
            ->setTimeout(1)
            ->executeReturnArray();

        // Parse the output
        $return = [
            'memory' => [],
            'swap'   => [],
        ];

        foreach ($output as $line_number => $line) {
            if (!$line_number) {
                continue;
            }

            $line = Strings::noDouble($line, ' ', ' ');

            $data['total']       = Strings::until(Strings::skip($line, ' ', 1, true), ' ');
            $data['used']        = Strings::until(Strings::skip($line, ' ', 2, true), ' ');
            $data['free']        = Strings::until(Strings::skip($line, ' ', 3, true), ' ');
            $data['shared']      = Strings::until(Strings::skip($line, ' ', 4, true), ' ');
            $data['buff/cached'] = Strings::until(Strings::skip($line, ' ', 5, true), ' ');
            $data['available']   = Strings::until(Strings::skip($line, ' ', 6, true), ' ');

            switch ($line_number) {
                case 1:
                    $return['memory'] = $data;
                    break;

                case 2:
                    unset($data['shared']);
                    unset($data['buff/cached']);
                    unset($data['available']);

                    $return['swap'] = $data;
                    break;

                default:
                    Log::warning(tr('Ignoring unknown output ":line" from the command "free"', [':line' => $line]));
            }
        }

        return $return;
    }
}
