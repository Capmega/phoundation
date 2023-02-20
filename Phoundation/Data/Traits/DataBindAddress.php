<?php

namespace Phoundation\Data\Traits;



/**
 * Class DataBindAddress
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataBindAddress
{
    /**
     * The IP address to which wget should bind
     *
     * @var string|null $bind_address
     */
    protected ?string $bind_address = null;



    /**
     * Returns the bind address for this connection
     *
     * @return string
     */
    public function getBindAddress(): string
    {
        return $this->bind_address;
    }



    /**
     * Sets the bind address for this connection
     *
     * @param string|null $bind_address
     * @return static
     */
    public function setBindAddress(?string $bind_address): static
    {
        $this->bind_address = $bind_address;
        return $this;
    }
}