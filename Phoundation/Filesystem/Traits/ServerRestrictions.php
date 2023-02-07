<?php

namespace Phoundation\Filesystem\Traits;

use Phoundation\Servers\Server;



/**
 * Trait ServerRestrictions
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Developer
 */
trait ServerRestrictions
{
    /**
     * Server object where the image conversion commands will be executed
     *
     * @var Server $restrictions
     */
    protected Server $restrictions;



    /**
     * Returns the server restrictions
     *
     * @return Server
     */
    public function getServerRestrictions(): Server
    {
        return $this->restrictions;
    }



    /**
     * Returns the server restrictions
     *
     * @param Server $restrictions
     * @return static
     */
    public function setServerRestrictions(Server $restrictions): static
    {
        $this->restrictions = $restrictions;
        return $this;
    }
}