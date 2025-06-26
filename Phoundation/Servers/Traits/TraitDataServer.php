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
    protected ?ServerInterface $o_server = null;


    /**
     * Returns the server
     *
     * @return ServerInterface|null
     */
    public function getServerObject(): ?ServerInterface
    {
        return $this->o_server;
    }


    /**
     * Sets the server
     *
     * @param ServerInterface|string|null $o_server
     *
     * @return static
     */
    public function setServerObject(ServerInterface|string|null $o_server): static
    {
        $this->o_server = $o_server ? Server::new()->load($o_server) : null;
        return $this;
    }
}
