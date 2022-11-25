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
     * DataList class constructor
     *
     * @param User|null $parent
     */
    public function __construct(?User $parent = null)
    {
        parent::__construct($parent);
    }



    /**
     * Returns the User object for this roles list
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->parent;
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

        $this->parent = $user;
        return $this;
    }



    /**
     * Load the data for this roles list
     *
     * @return $this
     */
    public function load(): static
    {
        // Load the roles for this user only
        $this->list = sql()->list('   SELECT     `accounts_roles`.* 
                                            FROM       `accounts_roles` 
                                            RIGHT JOIN `accounts_users_roles` 
                                            ON         `accounts_users_roles`.`roles_id` = `accounts_roles`.`id`
                                            AND        `accounts_users_roles`.`users_id` = :users_id', [
                                                ':users_id' => $this->parent->getId()
        ]);

        return $this;
    }



    /**
     * Save this roles list
     *
     * @return $this
     */
    public function save(): static
    {
showdie($this);
        // Delete current roles list
        sql()->query('DELETE FROM `accounts_users_roles` WHERE `users_id` = :users_id', [
            'users_id' => $this->parent->getId()
        ]);

        // Add new roles list
        $prepare = sql()->prepare('DELETE FROM `accounts_users_roles` WHERE `users_id` = :users_id', [
            'users_id' => $this->parent->getId()
        ]);

        foreach ($this->list as $item) {
            $prepare->execute([
                'users_id' => $this->parent->getId(),
                'roles_id' => $item,
            ]);
        }

        return $this;
    }
}