<?php

namespace Phoundation\Accounts\Roles;

use MongoDB\Exception\UnsupportedException;



/**
 * Class RoleUsers
 *
 * This class contains all the users that have this role
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyrole Copyrole (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class RoleUsers extends Roles
{
    /**
     * Load the data for this roles list into the object
     *
     * @param string|null $columns
     * @return static
     */
    public function load(?string $columns = null): static
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
        throw new UnsupportedException(tr('RoleUsers objects cannot be saved'));
    }
}