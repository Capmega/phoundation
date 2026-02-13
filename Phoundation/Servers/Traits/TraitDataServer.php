<?php

/**
 * Trait TraitDataServer
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Servers\Traits;

use Phoundation\Servers\Interfaces\ServerInterface;
use Phoundation\Servers\Server;


trait TraitDataServer
{
    /**
     * Tracks the server
     *
     * @var ServerInterface|null
     */
    protected ?ServerInterface $_server = null;


    /**
     * Returns the server
     *
     * @return ServerInterface|null
     */
    public function getServerObject(): ?ServerInterface
    {
        return $this->_server;
    }


    /**
     * Sets the server
     *
     * @param ServerInterface|string|null $_server
     *
     * @return static
     */
    public function setServerObject(ServerInterface|string|null $_server): static
    {
        $this->_server = $_server ? Server::new()->load($_server) : null;
        return $this;
    }
}
