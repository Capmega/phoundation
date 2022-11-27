<?php

namespace Phoundation\Accounts\Roles;

use Phoundation\Accounts\Users\User;



/**
 * Class UserRoles
 *
 * This class is a Roles list object with roles limited to the specified user
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyrole Copyrole (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * Add the specified data entry to the data list
     *
     * @param Role|array|int|null $role
     * @param bool $database
     * @return $this
     */
    public function add(Role|array|int|null $role, bool $database = true): static
    {
        if ($role) {
            if (is_array($role)) {
                // Add multiple roles
                foreach ($role as $item) {
                    $this->add($item, $database);
                }

            } else {
                // Add single role
                if (is_integer($role)) {
                    // Role was specified as integer, get an object for it
                    $role = Role::get($role);
                }

                if ($database) {
                    // Insert data in database
                    sql()->insert('accounts_users_roles', [
                        'users_id' => $this->parent->getId(),
                        'roles_id' => $role->getId()
                    ]);
                }

                // Add role to internal list
                $this->addEntry($role);
            }

            $this->parent->rights()->load();
        }

        return $this;
    }



    /**
     * Remove the specified data entry from the data list
     *
     * @param Role|int|null $role
     * @param bool $database
     * @return $this
     */
    public function remove(Role|int|null $role, bool $database = true): static
    {
        if ($role) {
            if ($database) {
                sql()->query('DELETE FROM `accounts_users_roles` WHERE `users_id` = :users_id AND `roles_id` = :roles_id', [
                    'users_id'  => $this->parent->getId(),
                    'roles_id' => $role->getId()
                ]);
            }

            $this->removeEntry($role);
            $this->parent->rights()->load();
        }

        return $this;
    }



    /**
     * Remove all roles for this user
     *
     * @return $this
     */
    public function clear(bool $database = true): static
    {
        if ($database) {
            sql()->query('DELETE FROM `accounts_users_roles` WHERE `users_id` = :users_id', [
                'users_id'  => $this->parent->getId()
            ]);
        }



        return $this;
    }



    /**
     * Load the data for this roles list into the object
     *
     * @param bool $details
     * @return static
     */
    public function load(bool $details = false): static
    {
        $this->list = sql()->list('SELECT `accounts_users_roles`.* 
                                         FROM   `accounts_users_roles` 
                                         WHERE  `accounts_users_roles`.`users_id` = :users_id', [
            ':users_id' => $this->parent->getId()
        ]);
        return $this;
    }



    /**
     * Save the data for this roles list in the database
     *
     * @return static
     */
    public function save(): static
    {
        // Delete the current list
        sql()->query('DELETE FROM `accounts_users_roles` 
                            WHERE       `accounts_users_roles`.`users_id` = :users_id', [
            ':users_id' => $this->parent->getId()
        ]);

        // Add the new list
        sql()->query('DELETE FROM `accounts_users_roles` 
                            WHERE       `accounts_users_roles`.`users_id` = :users_id', [
            ':users_id' => $this->parent->getId()
        ]);

        return $this;
    }
}