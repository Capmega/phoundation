<?php

namespace Phoundation\Servers;

use Phoundation\Core\Core;



/**
 * ServerUser class
 *
 * This class contains basic server using architectur
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Servers
 */
class ServerUser
{
    /**
     * The file access permissions
     *
     * @var Server $server
     */
    protected Server $server;



    /**
     * Returns the server and filesystem restrictions for this File object
     *
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }



    /**
     * Sets the server and filesystem restrictions for this File object
     *
     * @param Server|array|string|null $server
     * @return static
     */
    public function setServer(Server|array|string|null $server = null): static
    {
        $this->server = Core::ensureServer($server);
        return $this;
    }
}
