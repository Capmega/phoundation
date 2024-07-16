<?php

/**
 * Trait TraitDataServer
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
    protected ?ServerInterface $server = null;


    /**
     * Returns the server
     *
     * @return ServerInterface|null
     */
    public function getServer(): ?ServerInterface
    {
        return $this->server;
    }


    /**
     * Sets the server
     *
     * @param ServerInterface|string|null $server
     *
     * @return $this
     */
    public function setServer(ServerInterface|string|null $server): static
    {
        $this->server = $server ? Server::load($server) : null;

        return $this;
    }
}
