<?php

/**
 * Class TraitDataNetworkConnection
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataNetworkConnection
{
    use TraitDataHostnamePort;


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
     * @param string|null $user
     *
     * @return static
     */
    public function setUser(?string $user): static
    {
        $this->user = get_null($user);
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
     * @param string|null $password
     *
     * @return static
     */
    public function setPassword(?string $password): static
    {
        $this->password = get_null($password);
        return $this;
    }
}
