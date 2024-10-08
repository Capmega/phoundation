<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Filesystem\Path;
use Phoundation\Utils\Strings;

/**
 * Class Lsblk
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
class Lsof extends Command
{
    /**
     * Returns information about what processes have the specified file open
     *
     * @param Path|string $file
     *
     * @return IteratorInterface
     */
    public function getForFile(Path|string $file): IteratorInterface
    {
        $return    = [];
        $processes = $this->clearArguments()
                          ->setCommand('lsof')
                          ->addArgument($file)
                          ->executeReturnArray();
        foreach ($processes as $line => $process) {
            if ($line <= 2) {
                continue;
            }
            $return[] = Strings::characterSplit($process, ' ', [
                'command',
                'pid',
                'user',
                'fd',
                'type',
                'device',
                'size/off',
                'node',
                'name',
                'mount_source',
            ]);
        }

        return new Iterator($return);
    }
}
