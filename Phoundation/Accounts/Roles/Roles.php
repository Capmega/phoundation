<?php

namespace Phoundation\Accounts\Roles;

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Data\DataList;
use Phoundation\Data\Exception\DataEntryAlreadyExistsException;
use Phoundation\Databases\Sql\QueryBuilder;
use Phoundation\Exception\OutOfBoundsException;



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
class Roles extends DataList
{
    /**
     * DataList class constructor
     *
     * @param User|Role|null $parent
     */
    public function __construct(User|Role|null $parent = null)
    {
        $this->entry_class = Role::class;
        parent::__construct($parent);
    }



    /**
     * Set the entries to the specified list
     *
     * @return $this
     */
    public function set(array $list): static
    {
        // Convert the list to id's
        foreach ($list as $role) {
            $roles_list[] = Role::new($role)->getId();
        }

        show($this->list);
        show($roles_list);
        $diff = Arrays::valueDiff($this->list, $roles_list);

        showdie($diff);
    }



    /**
     * Add the specified data entry to the data list
     *
     * @param Role|array|null $roles
     * @return $this
     */
    public function add(Role|array|null $roles): static
    {
        if ($roles) {
            if (is_array($roles)) {
                // Add multiple rights
                foreach ($roles as $role) {
                    $this->add($role);
                }

            } else {
                // Add single right. Since this is a Role object, the entry already exists in the database
                if (!$this->parent) {
                    throw new OutOfBoundsException(tr('Cannot add entry to parent, no parent specified'));
                }

                // Already exists?
                if (in_array($roles->getId(), $this->list)) {
                    throw DataEntryAlreadyExistsException::new(tr('Cannot add role ":role", it already exists for ":type" ":parent"', [
                        ':type'   => Strings::fromReverse(get_class($this->parent), '\\'),
                        ':role'   => $roles->getName(),
                        ':parent' => $this->parent->getName()
                    ]))->makeWarning();
                }

                // Add entry to parent, User or Role
                if ($this->parent instanceof User) {
                    sql()->insert('accounts_users_roles', [
                        'users_id' => $this->parent->getId(),
                        'roles_id' => $roles->getId()
                    ]);

                    // Add right to internal list
                    $this->addEntry($roles);
                } elseif ($this->parent instanceof Right) {
                    sql()->insert('accounts_roles_rights', [
                        'rights_id' => $this->parent->getId(),
                        'roles_id'  => $roles->getId()
                    ]);

                    // Update all users with this right to get the new right as well!
                    foreach ($this->parent->users() as $user) {
                        $user->rights()->update();
                    }

                    // Add right to internal list
                    $this->addEntry($roles);
                }
            }
        }

        return $this;
    }



    /**
     * Remove the specified role from the roles list
     *
     * @param Role|array|null $roles
     * @return $this
     */
    public function remove(Role|array|null $roles): static
    {
        if ($roles) {
            if (is_array($roles)) {
                // Add multiple rights
                foreach ($roles as $role) {
                    $this->remove($role);
                }

            } else {
                // Add single right. Since this is a Role object, the entry already exists in the database
                if (!$this->parent) {
                    throw new OutOfBoundsException(tr('Cannot add entry to parent, no parent specified'));
                }

                if ($this->parent instanceof User) {
                    sql()->delete('accounts_users_roles', [
                        'users_id' => $this->parent->getId(),
                        'roles_id' => $roles->getId()
                    ]);

                    // Add right to internal list
                    $this->removeEntry($roles);
                } elseif ($this->parent instanceof Right) {
                    sql()->delete('accounts_roles_rights', [
                        'rights_id' => $this->parent->getId(),
                        'roles_id'  => $roles->getId()
                    ]);

                    // Update all users with this right to get the new right as well!
                    foreach ($this->parent->users() as $user) {
                        $user->rights()->update();
                    }

                    // Add right to internal list
                    $this->removeEntry($roles);
                }
            }
        }

        return $this;
    }



    /**
     * Remove all rights for this right
     *
     * @return $this
     */
    public function clear(): static
    {
        if (!$this->parent) {
            throw new OutOfBoundsException(tr('Cannot clear parent entries, no parent specified'));
        }

        if ($this->parent instanceof User) {
            sql()->query('DELETE FROM `accounts_users_roles` WHERE `users_id` = :users_id', [
                'users_id'  => $this->parent->getId()
            ]);

        } elseif ($this->parent instanceof Right) {
            sql()->query('DELETE FROM `accounts_roles_rights` WHERE `rights_id` = :rights_id', [
                'rights_id'  => $this->parent->getId()
            ]);
        }

        return parent::clearEntries();
    }



    /**
     * Load the data for this rights list into the object
     *
     * @return static
     */
    public function load(): static
    {
        if ($this->parent) {
            if ($this->parent instanceof User) {
                $this->list = sql()->list('SELECT `accounts_users_roles`.`rights_id` 
                                           FROM   `accounts_users_roles` 
                                           WHERE  `accounts_users_roles`.`users_id` = :users_id', [
                    ':users_id' => $this->parent->getId()
                ]);

            } elseif ($this->parent instanceof Right) {
                $this->list = sql()->list('SELECT `accounts_roles_rights`.`rights_id` 
                                           FROM   `accounts_roles_rights` 
                                           WHERE  `accounts_roles_rights`.`rights_id` = :rights_id', [
                    ':rights_id' => $this->parent->getId()
                ]);

            }

        } else {
            $this->list = sql()->list('SELECT `id` FROM `accounts_rights`');
        }

        return $this;
    }



    /**
     * Load the data for this roles list
     *
     * @param array|string|null $columns
     * @param array $filters
     * @return array
     */
    protected function loadDetails(array|string|null $columns, array $filters = []): array
    {
        // Default columns
        if (!$columns) {
            $columns = 'id,name,rights';
        }

        // Get column information
        $columns = Arrays::force($columns);
        $users   = Arrays::replaceIfExists($columns, 'users' , '1 AS `users`');
        $rights  = Arrays::replaceIfExists($columns, 'rights', '1 AS `rights`');
        $columns = Strings::force($columns);

        // Build query
        $builder = new QueryBuilder();
        $builder->addSelect(' SELECT ' . $columns);
        $builder->addFrom('FROM `accounts_roles`');

        foreach ($filters as $key => $value){
            switch ($key) {
                case 'users':
                    $builder->addJoin('JOIN `accounts_users` 
                                            ON   `accounts_users`.`email` ' . $builder->compareQuery('email', $value) . ' 
                                            JOIN `accounts_users_roles` 
                                            ON   `accounts_users_roles`.`users_id` = `accounts_users`.`id`  
                                            AND  `accounts_users_roles`.`roles_id` = `accounts_roles`.`id`');
                    break;

                case 'rights':
                    $builder->addJoin('JOIN `accounts_rights` 
                                            ON   `accounts_rights`.`name` ' . $builder->compareQuery('right', $value) . ' 
                                            JOIN `accounts_roles_rights` 
                                            ON   `accounts_roles_rights`.`rights_id` = `accounts_rights`.`id` 
                                            AND  `accounts_users_rights`.`roles_id`  = `accounts_roles`.`id`');
                    break;
            }
        }

        $return = sql()->list($builder->getQuery(), $builder->getExecute());

        if ($users) {
            // Add roles information to each user
            foreach ($return as $id => &$item) {
                $item['users'] = sql()->list('SELECT `email`
                                              FROM   `accounts_users`
                                              JOIN   `accounts_users_roles`
                                              ON     `accounts_users_roles`.`roles_id` = :roles_id
                                              AND    `accounts_users_roles`.`users_id` = `accounts_users`.`id`', [
                    ':roles_id' => $id
                ]);

                $item['users'] = implode(', ', $item['users']);
            }

            unset($item);
        }

        if ($rights) {
            // Add rights information to each user
            // Add roles information to each user
            foreach ($return as $id => &$item) {
                $item['rights'] = sql()->list('SELECT `name`
                                               FROM   `accounts_rights`
                                               JOIN   `accounts_roles_rights`
                                               ON     `accounts_roles_rights`.`roles_id`  = :roles_id
                                               AND    `accounts_roles_rights`.`rights_id` = `accounts_rights`.`id`', [
                    ':roles_id' => $id
                ]);

                $item['rights'] = implode(', ', $item['rights']);
            }

            unset($item);
        }

        return $return;
    }



    /**
     * Save the data for this roles list in the database
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
            sql()->query('DELETE FROM `accounts_users_roles` 
                                WHERE       `accounts_users_roles`.`users_id` = :users_id', [
                ':users_id' => $this->parent->getId()
            ]);

            // Add the new list
            sql()->query('DELETE FROM `accounts_users_roles` 
                                WHERE       `accounts_users_roles`.`users_id` = :users_id', [
                ':users_id' => $this->parent->getId()
            ]);

        } elseif ($this->parent instanceof Right) {
            // Delete the current list
            sql()->query('DELETE FROM `accounts_roles_rights` 
                                WHERE       `accounts_roles_rights`.`rights_id` = :rights_id', [
                ':rights_id' => $this->parent->getId()
            ]);

            // Add the new list
            sql()->query('DELETE FROM `accounts_roles_rights` 
                                WHERE       `accounts_roles_rights`.`rights_id` = :rights_id', [
                ':rights_id' => $this->parent->getId()
            ]);
        }

        return $this;
    }



    /**
     * Update the rights linked to the specified $role for the User parent
     *
     * @param Role $role
     * @return void
     */
    public function updateRights(): void
    {
        if (!$this->parent) {
            throw new OutOfBoundsException(tr('Cannot update user rights, this rights list has no parent user object specified', [
            ]));
        }

        if (!($this->parent instanceof User)) {
            throw new OutOfBoundsException(tr('Cannot update user rights, this rights list does not have a user parent but a ":type" parent', [
                ':type' => get_class($this->parent)
            ]));
        }

        // Remove the rights related to user
        sql()->delete('accounts_users_rights', ['users_id' => $this->parent->getId()]);

        // Insert all the rights for all the roles assigned to this user
        foreach ($this->list as $role) {
            $rights = Role::new($role)->rights();

            foreach($rights as $right) {
                sql()->insert('accounts_users_rights', [
                    'users_id'  => $this->parent->getId(),
                    'rights_id' => $right->getId(),
                ]);
            }
        }
    }
}