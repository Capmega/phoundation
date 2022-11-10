<?php

namespace Phoundation\Servers;

use Phoundation\Filesystem\Restrictions;



/**
 * Local class
 *
 * This class is a special version of the Server class, it is restricted to the localhost server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Servers
 */
class Local extends Server
{
    /**
     * Local constructor
     *
     * @param Restrictions|null $restrictions
     */
    public function __construct(?Restrictions $restrictions) {
        parent::__construct('localhost', $restrictions);
    }



}