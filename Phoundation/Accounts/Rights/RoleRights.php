<?php

namespace Phoundation\Accounts\Rights;

use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Roles\RoleUsers;


/**
 * Class RoleRights
 *
 * This class is a Rights list object with rights limited to the specified role
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class RoleRights extends Rights
{
    /**
     * DataList class constructor
     *
     * @param Role|null $parent
     */
    public function __construct(?Role $parent = null)
    {
        parent::__construct($parent);
    }



    /**
     * Add the specified data entry to the data list
     *
     * @param Right|array|null $rights
     * @return $this
     */
    public function add(Right|array|null $rights): static
    {
        if ($rights) {
            if (is_array($rights)) {
                // Add multiple rights
                foreach ($rights as $right) {
                    $this->add($right);
                }

            } else {
                // Add single right
                sql()->insert('accounts_roles_rights', [
                    'roles_id'  => $this->parent->getId(),
                    'rights_id' => $rights->getId()
                ]);

//                // Update all users with this role to get the new right as well!
//                foreach ($this->parent->users() as $user) {
//                    $user->updateRights();
//                }

                // Add right to internal list
                $this->addEntry($rights);
            }
        }

        return $this;
    }



    /**
     * Remove the specified data entry from the data list
     *
     * @param Right|array|null $rights
     * @return $this
     */
    public function remove(Right|array|null $rights): static
    {
        if ($rights) {
            if (is_array($rights)) {
                // Remove multiple rights
                foreach ($rights as $right) {
                    $this->remove($right);
                }

            } else {
                // Remove single right
                sql()->query('DELETE FROM `accounts_roles_rights` 
                                    WHERE       `roles_id` = :roles_id AND `rights_id` = :rights_id', [
                    'roles_id' => $this->parent->getId(),
                    'rights_id' => $rights->getId()
                ]);

//                // Update all users with this role to get the new right as well!
//                foreach ($this->parent->users() as $user) {
//                    $user->updateRights();
//                }

                $this->removeEntry($rights);
            }
        }

        return $this;
    }



    /**
     * Remove all rights for this role
     *
     * @return $this
     */
    public function clear(): static
    {
        sql()->query('DELETE FROM `accounts_roles_rights` WHERE `roles_id` = :roles_id', [
            'roles_id'  => $this->parent->getId()
        ]);

        return parent::clearEntries();
    }



    /**
     * Load the data for this rights list into the object
     *
     * @param string|null $columns
     * @return static
     */
    public function load(?string $columns = null): static
    {
        $this->list = sql()->list('SELECT `accounts_roles_rights`.* 
                                         FROM   `accounts_roles_rights` 
                                         WHERE  `accounts_roles_rights`.`roles_id` = :roles_id', [
                                             ':roles_id' => $this->parent->getId()
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
        sql()->query('DELETE FROM `accounts_roles_rights` 
                            WHERE       `accounts_roles_rights`.`roles_id` = :roles_id', [
            ':roles_id' => $this->parent->getId()
        ]);

        // Add the new list
        sql()->query('DELETE FROM `accounts_roles_rights` 
                            WHERE       `accounts_roles_rights`.`roles_id` = :roles_id', [
            ':roles_id' => $this->parent->getId()
        ]);

        return $this;
    }
}