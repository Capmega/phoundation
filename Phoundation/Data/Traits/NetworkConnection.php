<?php

namespace Phoundation\Data\Traits;



/**
 * Class Rsync
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
trait NetworkConnection
{
    /**
     * The host for this connection
     *
     * @var string $host
     */
    protected string $host = 'localhost';

    /**
     * The port for this connection
     *
     * @var string|null $port
     */
    protected ?string $port = null;

    /**
     * The user for this connection
     *
     * @var string|null $user
     */
    protected ?string $user = null;

    /**
     * The password for this connection
     *
     * @var string|null $password
     */
    protected ?string $password = null;



    /**
     * Returns the host for this connection
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }



    /**
     * Sets the host for this connection
     *
     * @param string|null $host
     * @return static
     */
    public function setHost(?string $host): static
    {
        if (!$host) {
            $host = 'localhost';
        }

        $this->host = $host;
        return $this;
    }



    /**
     * Returns the port for this connection
     *
     * @return string|null
     */
    public function getPort(): ?string
    {
        return $this->port;
    }



    /**
     * Sets the port for this connection
     *
     * @param string $port
     * @return static
     */
    public function setPort(string $port): static
    {
        $this->port = $port;
        return $this;
    }



    /**
     * Returns the user for this connection
     *
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->user;
    }



    /**
     * Sets the user for this connection
     *
     * @param string $user
     * @return static
     */
    public function setUser(string $user): static
    {
        $this->user = $user;
        return $this;
    }



    /**
     * Returns the password for this connection
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }



    /**
     * Sets the password for this connection
     *
     * @param string $password
     * @return static
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }
}