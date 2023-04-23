<?php

namespace Phoundation\Network;

use Phoundation\Processes\Process;



/**
 * Network class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Network
 */
class Network
{
    /**
     * Returns the public IP address for this machine, if possible
     *
     * @return string
     */
    public static function getPublicIpAddress(): string
    {
        return Process::new('dig')
            ->addArgument('+short')
            ->addArgument('myip.opendns.com')
            ->addArgument('@resolver1.opendns.com')
            ->executeReturnString();
    }
}