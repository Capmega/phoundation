<?php

namespace Phoundation\Accounts\Rights;

use Phoundation\Accounts\Users\User;



/**
 * Class UserRights
 *
 * This class is a Rights list object with rights limited to the specified user
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class UserRights extends Rights
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
     * Load the data for this rights list into the object
     *
     * @param string|null $columns
     * @return static
     */
    public function load(?string $columns = null): static
    {
        $this->list = sql()->list('SELECT `accounts_users_rights`.* 
                                         FROM   `accounts_users_rights` 
                                         WHERE  `accounts_users_rights`.`users_id` = :users_id', [
            ':users_id' => $this->parent->getId()
        ]);

        return $this;
    }



    /**
     * Save the data for this rights list in the database
     *
     * @return static
     */
    public function save(): static
    {
        // Delete the current list
        sql()->query('DELETE FROM `accounts_users_rights` 
                            WHERE       `accounts_users_rights`.`users_id` = :users_id', [
            ':users_id' => $this->parent->getId()
        ]);

        // Add the new list
        sql()->query('DELETE FROM `accounts_users_rights` 
                            WHERE       `accounts_users_rights`.`users_id` = :users_id', [
            ':users_id' => $this->parent->getId()
        ]);

        return $this;
    }
}