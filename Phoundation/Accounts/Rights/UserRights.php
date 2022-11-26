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
     * Add the specified data entry to the data list
     *
     * @param Right|array|int|null $right
     * @return $this
     */
    public function add(Right|array|int|null $right): static
    {
        if ($right) {
            if (is_array($right)) {
                // Add multiple rights
                foreach ($right as $item) {
                    $this->add($right);
                }

            } else {
                // Add single right
                if (is_integer($right)) {
                    // Right was specified as integer, get an object for it
                    $right = Right::get($right);
                }

                // Insert data in database
                sql()->insert('accounts_users_rights', [
                    'users_id'  => $this->parent->getId(),
                    'rights_id' => $right->getId()
                ]);

                // Add right to internal list
                $this->addEntry($right);
            }
        }

        return $this;
    }



    /**
     * Remove the specified data entry from the data list
     *
     * @param Right|int|null $right
     * @return $this
     */
    public function remove(Right|int|null $right): static
    {
        if ($right) {
            sql()->query('DELETE FROM `accounts_users_rights` WHERE `users_id` = :users_id AND `rights_id` = :rights_id', [
                'users_id'  => $this->parent->getId(),
                'rights_id' => $right->getId()
            ]);

            $this->removeEntry($right);
        }

        return $this;
    }



    /**
     * Remove all rights for this user
     *
     * @return $this
     */
    public function clear(): static
    {
        sql()->query('DELETE FROM `accounts_users_rights` WHERE `users_id` = :users_id', [
            'users_id'  => $this->parent->getId()
        ]);

        return $this;
    }



    /**
     * Load the data for this rights list into the object
     *
     * @return static
     */
    public function load(): static
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