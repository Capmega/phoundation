<?php

declare(strict_types=1);

namespace Phoundation\Core\Log;


/**
 * Class LogManager
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class LogManager
{
    /**
     * Rotate all current logs
     *
     * @return void
     */
    public function rotate()
    {

    }


    /**
     * Clears old log files
     *
     * @return void
     */
    public function clear()
    {
        // Cleanup log sudo rm data/log -rf; sudo mkdir data/log; sudo touch data/log/syslog; sudo chmod 770 data/log;

    }
}