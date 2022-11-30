<?php

namespace Phoundation\Accounts\Rights;

use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Data\DataList;
use Phoundation\Data\Exception\DataEntryAlreadyExistsException;
use Phoundation\Exception\OutOfBoundsException;



/**
 * Class Rights
 *
 *
 *
 * @see \Phoundation\Data\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Rights extends DataList
{
    /**
     * DataList class constructor
     *
     * @param User|Role|null $parent
     * @param bool $load
     */
    public function __construct(User|Role|null $parent = null, bool $load = true)
    {
        $this->entry_class = Right::class;
        parent::__construct($parent, $load);
    }



    /**
     * Set the entries to the specified list
     *
     * @return $this
     */
    public function set(array $list): static
    {
        // Convert the list to id's
        foreach ($list as $right) {
            $rights_list[] = Right::new($right)->getId();
        }

show($this->list);
show($rights_list);
        $diff = Arrays::valueDiff($this->list, $rights_list);

        showdie($diff);
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
                // Add single right. Since this is a Right object, the entry already exists in the database
                if (!$this->parent) {
                    throw new OutOfBoundsException(tr('Cannot add entry to parent, no parent specified'));
                }

                // Already exists?
                if (in_array($rights->getId(), $this->list)) {
                    throw DataEntryAlreadyExistsException::new(tr('Cannot add right ":right", it already exists for ":type" ":parent"', [
                        ':type'   => Strings::fromReverse(get_class($this->parent), '\\'),
                        ':right'  => $rights->getName(),
                        ':parent' => $this->parent->getName()
                    ]))->makeWarning();
                }

                // Add entry to parent, User or Role
                if ($this->parent instanceof User) {
                    sql()->insert('accounts_users_rights', [
                        'users_id'  => $this->parent->getId(),
                        'rights_id' => $rights->getId()
                    ]);

                    // Add right to internal list
                    $this->addEntry($rights);
                } elseif ($this->parent instanceof Role) {
                    sql()->insert('accounts_roles_rights', [
                        'roles_id'  => $this->parent->getId(),
                        'rights_id' => $rights->getId()
                    ]);

                    // Update all users with this role to get the new right as well!
                    foreach ($this->parent->users() as $user) {
                        $user->roles()->update();
                    }

                    // Add right to internal list
                    $this->addEntry($rights);
                }
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
                // Add multiple rights
                foreach ($rights as $right) {
                    $this->remove($right);
                }

            } else {
                // Add single right. Since this is a Right object, the entry already exists in the database
                if (!$this->parent) {
                    throw new OutOfBoundsException(tr('Cannot add entry to parent, no parent specified'));
                }

                if ($this->parent instanceof User) {
                    sql()->delete('accounts_users_rights', [
                        'users_id'  => $this->parent->getId(),
                        'rights_id' => $rights->getId()
                    ]);

                    // Add right to internal list
                    $this->removeEntry($rights);
                } elseif ($this->parent instanceof Role) {
                    sql()->delete('accounts_roles_rights', [
                        'roles_id'  => $this->parent->getId(),
                        'rights_id' => $rights->getId()
                    ]);

                    // Update all users with this role to get the new right as well!
                    foreach ($this->parent->users() as $user) {
                        $user->roles()->update();
                    }

                    // Add right to internal list
                    $this->removeEntry($rights);
                }
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
        if (!$this->parent) {
            throw new OutOfBoundsException(tr('Cannot clear parent entries, no parent specified'));
        }

        if ($this->parent instanceof User) {
            sql()->query('DELETE FROM `accounts_users_rights` WHERE `users_id` = :users_id', [
                'users_id'  => $this->parent->getId()
            ]);

        } elseif ($this->parent instanceof Role) {
            sql()->query('DELETE FROM `accounts_roles_rights` WHERE `roles_id` = :roles_id', [
                'roles_id'  => $this->parent->getId()
            ]);
        }

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
        if ($this->parent) {
            if ($this->parent instanceof Role) {
                $this->list = sql()->list('SELECT `accounts_roles_rights`.`rights_id` 
                                           FROM   `accounts_roles_rights` 
                                           WHERE  `accounts_roles_rights`.`roles_id` = :roles_id', [
                    ':roles_id' => $this->parent->getId()
                ]);

            } elseif ($this->parent instanceof User) {
                $this->list = sql()->list('SELECT `accounts_users_rights`.`rights_id` 
                                           FROM   `accounts_users_rights` 
                                           WHERE  `accounts_users_rights`.`users_id` = :users_id', [
                    ':users_id' => $this->parent->getId()
                ]);
            }

        } else {
            $this->list = sql()->list('SELECT * FROM `accounts_rights`');
        }

        return $this;
    }



    /**
     * Save the data for this rights list in the database
     *
     * @return static
     */
    public function save(): static
    {
        if (!$this->parent) {
            throw new OutOfBoundsException(tr('Cannot clear parent entries, no parent specified'));
        }

        if ($this->parent instanceof User) {
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

        } elseif ($this->parent instanceof Role) {
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
        }

        return $this;
    }
}