<?php

declare(strict_types=1);

namespace Phoundation\Servers\Traits;

use Phoundation\Servers\Interfaces\ServerInterface;
use Phoundation\Servers\Server;


/**
 * Trait DataEntryServer
 *
 * This trait contains methods for DataEntry objects that require a server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryServer
{
    /**
     * @var ServerInterface|null $server
     */
    protected ?ServerInterface $server;


    /**
     * Returns the servers_id for this object
     *
     * @return int|null
     */
    public function getServersId(): ?int
    {
        return $this->getSourceColumnValue('int', 'servers_id');

    }


    /**
     * Sets the servers_id for this object
     *
     * @param int|null $servers_id
     * @return static
     */
    public function setServersId(?int $servers_id): static
    {
        unset($this->server);
        return $this->setSourceValue('servers_id', $servers_id);
    }


    /**
     * Returns the ServerInterface object for this object
     *
     * @return ServerInterface|null
     */
    public function getServer(): ?ServerInterface
    {
        if (!isset($this->server)) {
            $this->server = Server::getOrNull($this->getServersId());
        }

        return $this->server;
    }


    /**
     * Sets the ServerInterface object for this object
     *
     * @param ServerInterface|null $server
     * @return static
     */
    public function setServer(?ServerInterface $server): static
    {
        if ($server) {
            $this->server = $server;
            return $this->setSourceValue('servers_id', $server->getId());
        }

        return $this->setServersId(null);
    }


    /**
     * Returns the servers hostname for this object
     *
     * @return string|null
     */
    public function getServersHostname(): ?string
    {
        return $this->getServer()?->getHostname();
    }


    /**
     * Sets the server hostname for this object
     *
     * @param string|null $hostname
     * @return static
     */
    public function setServersHostname(?string $hostname): static
    {
        return $this->setServer(Server::get($hostname, 'hostname'));
    }
}
