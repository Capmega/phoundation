<?php

namespace Phoundation\Servers;

use Phoundation\Core\Core;
use Phoundation\Filesystem\Restrictions;


/**
 * UsesServer trait
 *
 * This trait contains basic server access restrictions architecture
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Servers
 */
trait UsesServer
{
    /**
     * The file access permissions
     *
     * @var Server $server_restrictions
     */
    protected Server $server_restrictions;



    /**
     * Returns the server and filesystem restrictions for this File object
     *
     * @return Server
     */
    public function getServerRestrictions(): Server
    {
        return $this->server_restrictions;
    }



    /**
     * Sets the server and filesystem restrictions for this File object
     *
     * @param Server|Restrictions|array|string|null $server_restrictions
     * @return static
     */
    public function setServerRestrictions(Server|Restrictions|array|string|null $server_restrictions = null): static
    {
        $this->server_restrictions = Core::ensureServer($server_restrictions);
        return $this;
    }
}
