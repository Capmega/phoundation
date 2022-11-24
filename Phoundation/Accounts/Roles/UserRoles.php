<?php

namespace Phoundation\Accounts\Roles;

use Phoundation\Accounts\Users\User;



/**
 * Class Roles
 *
 *
 *
 * @see \Phoundation\Data\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class UserRoles extends Roles
{
    /**
     * The user for this roles list
     *
     * @var User
     */
    protected User $user;



    /**
     * Returns the User object for this roles list
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }



    /**
     * Set the user for this roles list
     *
     * @param User|string|int|null $user
     * @return $this
     */
    public function setUser(User|string|int|null $user): static
    {
        if (!is_object($user)) {
            $user = new User($user);
        }

        $this->user = $user;
        return $this;
    }



    /**
     * Load the data for this roles list
     *
     * @return $this
     */
    public function load(): static
    {

        return $this;
    }



    /**
     * Save this roles list
     *
     * @return $this
     */
    public function save(): static
    {

        return $this;
    }
}