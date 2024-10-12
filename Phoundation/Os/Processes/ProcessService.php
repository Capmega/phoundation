<?php

/**
 * Class ProcessService
 *
 *
 *
 * @see       https://stackoverflow.com/questions/2036654/run-php-script-as-daemon-process for more information of
 *            running services through systemd, upstart, etc
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 * @uses      ProcessVariables
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes;

use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;


class ProcessService extends ProcessServiceCore
{
    /**
     * Returns a new Service object
     *
     * @param string|null                               $command
     * @param FsRestrictionsInterface|array|string|null $restrictions
     *
     * @return static
     */
    public static function new(?string $command = null, FsRestrictionsInterface|array|string|null $restrictions = null): static
    {
        $static = new static($restrictions);

        return $static->setCommand($command);
    }
}

