<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataHostnamePort
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataHostnamePort
{
    /**
     * The hostname for this object
     *
     * @var string|null $hostname
     */
    protected ?string $hostname = null;

    /**
     * The port for this hostname
     *
     * @var int|null $port
     */
    protected ?int $port = null;


    /**
     * Returns the hostname for this object
     *
     * @return string|null
     */
    public function getHostname(): ?string
    {
        return $this->hostname;
    }


    /**
     * Sets the hostname for this object
     *
     * @param string|null $hostname
     * @return static
     */
    public function setHostname(?string $hostname): static
    {
        $this->hostname = $hostname;
        return $this;
    }


    /**
     * Returns the port for this object
     *
     * @return string|null
     */
    public function getPort(): ?string
    {
        return $this->port;
    }


    /**
     * Sets the port for this object
     *
     * @param string|null $port
     * @return static
     */
    public function setPort(?string $port): static
    {
        $this->port = $port;
        return $this;
    }
}