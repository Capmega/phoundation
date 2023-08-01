<?php

declare(strict_types=1);

namespace Phoundation\Developer\Project;


/**
 * Database class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Developer
 */
class Database
{
    /**
     * The database host
     *
     * @var string|null $host
     */
    protected ?string $host = null;

    /**
     * The database name
     *
     * @var string|null $name
     */
    protected ?string $name = null;

    /**
     * The database user
     *
     * @var string|null $user
     */
    protected ?string $user = null;

    /**
     * The database password
     *
     * @var string|null $pass
     */
    protected ?string $pass = null;


    /**
     * Returns the configured host for this environment
     *
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->host;
    }


    /**
     * Sets the configured host for this environment
     *
     * @param string $host
     * @return static
     */
    public function setHost(string $host): static
    {
        $this->host = $host;
        return $this;
    }


    /**
     * Returns the configured database name for this environment
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }


    /**
     * Sets the configured database name for this environment
     *
     * @param string $name
     * @return static
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }


    /**
     * Returns the configured user for this environment
     *
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->user;
    }


    /**
     * Sets the configured user for this environment
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
     * Returns the configured password for this environment
     *
     * @return string|null
     */
    public function getPass(): ?string
    {
        return $this->pass;
    }


    /**
     * Sets the configured password for this environment
     *
     * @param string $pass
     * @return static
     */
    public function setPass(string $pass): static
    {
        $this->pass = $pass;
        return $this;
    }
}