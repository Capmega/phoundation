<?php

namespace Phoundation\Servers;

use Phoundation\Filesystem\Restrictions;



/**
 * Server class
 *
 * This class manages the localhost server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Servers
 */
class Server extends Localhost
{
    /**
     * Server constructor
     *
     * @param Restrictions|array|string|null $restrictions
     * @param string $hostname
     */
    public function __construct(Restrictions|array|string|null $restrictions, string $hostname)
    {
        $this->setHostname($hostname);
        parent::__construct($restrictions);
    }
}