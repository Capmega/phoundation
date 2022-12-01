<?php

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Data\DataList;
use Phoundation\Data\Exception\DataEntryAlreadyExistsException;
use Phoundation\Databases\Sql\QueryBuilder;
use Phoundation\Exception\OutOfBoundsException;



/**
 * Class Users
 *
 *
 *
 * @see \Phoundation\Data\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Users extends DataList
{
    /**
     * DataList class constructor
     *
     * @param Role|User|null $parent
     */
    public function __construct(Role|User|null $parent = null)
    {
        $this->entry_class = User::class;
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
        foreach ($list as $user) {
            $users_list[] = User::new($user)->getId();
        }

        show($this->list);
        show($users_list);
        $diff = Arrays::valueDiff($this->list, $users_list);

        showdie($diff);
    }



    /**
     * Add the specified data entry to the data list
     *
     * @param User|array|null $users
     * @return $this
     */
    public function add(User|array|null $users): static
    {
        if ($users) {
            if (is_array($users)) {
                // Add multiple rights
                foreach ($users as $user) {
                    $this->add($user);
                }

            } else {
                // Add single right. Since this is a User object, the entry already exists in the database
                if (!$this->parent) {
                    throw new OutOfBoundsException(tr('Cannot add entry to parent, no parent specified'));
                }

                // Already exists?
                if (in_array($users->getId(), $this->list)) {
                    throw DataEntryAlreadyExistsException::new(tr('Cannot add user ":user", it already exists for ":type" ":parent"', [
                        ':type'   => Strings::fromReverse(get_class($this->parent), '\\'),
                        ':right'  => $users->getName(),
                        ':parent' => $this->parent->getName()
                    ]))->makeWarning();
                }

                // Add entry to parent, Role or User
                if ($this->parent instanceof Role) {
                    sql()->insert('accounts_users_rights', [
                        'roles_id' => $this->parent->getId(),
                        'users_id' => $users->getId()
                    ]);

                    // Add right to internal list
                    $this->addEntry($users);
                } elseif ($this->parent instanceof Right) {
                    sql()->insert('accounts_users_rights', [
                        'rights_id' => $this->parent->getId(),
                        'users_id'  => $users->getId()
                    ]);

                    // Add right to internal list
                    $this->addEntry($users);
                }
            }
        }

        return $this;
    }



    /**
     * Remove the specified data entry from the data list
     *
     * @param User|array|null $users
     * @return $this
     */
    public function remove(User|array|null $users): static
    {
        if ($users) {
            if (is_array($users)) {
                // Add multiple rights
                foreach ($users as $user) {
                    $this->remove($user);
                }

            } else {
                // Add single right. Since this is a User object, the entry already exists in the database
                if (!$this->parent) {
                    throw new OutOfBoundsException(tr('Cannot add entry to parent, no parent specified'));
                }

                if ($this->parent instanceof Role) {
                    sql()->delete('accounts_users_rights', [
                        'roles_id' => $this->parent->getId(),
                        'users_id' => $users->getId()
                    ]);

                    // Add right to internal list
                    $this->removeEntry($users);
                } elseif ($this->parent instanceof Right) {
                    sql()->delete('accounts_users_rights', [
                        'rights_id' => $this->parent->getId(),
                        'users_id'  => $users->getId()
                    ]);

                    // Add right to internal list
                    $this->removeEntry($users);
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

        if ($this->parent instanceof Role) {
            sql()->query('DELETE FROM `accounts_users_roles` WHERE `roles_id` = :roles_id', [
                'roles_id'  => $this->parent->getId()
            ]);

        } elseif ($this->parent instanceof Right) {
            sql()->query('DELETE FROM `accounts_users_rights` WHERE `rights_id` = :rights_id', [
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
            if ($this->parent instanceof Role) {
                $this->list = sql()->list('SELECT `accounts_users_roles`.`rights_id` 
                                           FROM   `accounts_users_roles` 
                                           WHERE  `accounts_users_roles`.`roles_id` = :roles_id', [
                    ':roles_id' => $this->parent->getId()
                ]);

            } elseif ($this->parent instanceof Right) {
                $this->list = sql()->list('SELECT `accounts_users_rights`.`rights_id` 
                                           FROM   `accounts_users_rights` 
                                           WHERE  `accounts_users_rights`.`rights_id` = :rights_id', [
                    ':rights_id' => $this->parent->getId()
                ]);

            }

        } else {
            $this->list = sql()->list('SELECT `id` FROM `accounts_rights`');
        }

        return $this;
    }



    /**
     * Load the data for this users list
     *
     * @param array|string|null $columns
     * @param array $filters
     * @return array
     */
    protected function loadDetails(array|string|null $columns, array $filters = []): array
    {
        // Default columns
        if (!$columns) {
            $columns = 'id,domain,email,name,phones,roles';
        }

        // Get column information
        $columns = Arrays::force($columns);
        $roles   = Arrays::replaceIfExists($columns, 'roles' , '1 AS roles');
        $rights  = Arrays::replaceIfExists($columns, 'rights', '1 AS rights');
        $columns = Strings::force($columns);

        // Build query
        $builder = new QueryBuilder();
        $builder->addSelect(' SELECT ' . $columns);
        $builder->addFrom('FROM `accounts_users`');

        foreach ($filters as $key => $value){
            switch ($key) {
                case 'roles':
                    $builder->addJoin('JOIN `accounts_roles`       
                                            ON   `accounts_roles`.`name` ' . $builder->compareQuery('role', $value) . ' 
                                            JOIN `accounts_users_roles` 
                                            ON   `accounts_users_roles`.`roles_id` = `accounts_roles`.`id` 
                                            AND  `accounts_users_roles`.`users_id` = `accounts_users`.`id`');
                    break;

                case 'rights':
                    $builder->addJoin('JOIN `accounts_rights` 
                                            ON   `accounts_rights`.`name` ' . $builder->compareQuery('right', $value) . ' 
                                            JOIN `accounts_users_rights` 
                                            ON   `accounts_users_rights`.`rights_id` = `accounts_rights`.`id` 
                                            AND  `accounts_users_rights`.`users_id`  = `accounts_users`.`id`');
                    break;
            }
        }

        $return = sql()->list($builder->getQuery(), $builder->getExecute());

        if ($roles) {
            // Add roles information to each item
            foreach ($return as $id => &$item) {
                $item['roles'] = sql()->list('SELECT `name`
                                              FROM   `accounts_roles`
                                              JOIN   `accounts_users_roles`
                                              ON     `accounts_users_roles`.`users_id` = :users_id
                                              AND    `accounts_users_roles`.`roles_id` = `accounts_roles`.`id`', [
                    ':users_id' => $id
                ]);

                $item['roles'] = implode(', ', $item['roles']);
            }

            unset($item);
        }

        if ($rights) {
            // Add rights information to each item
            // Add roles information to each item
            foreach ($return as $id => &$item) {
                $item['rights'] = sql()->list('SELECT `name`
                                               FROM   `accounts_rights`
                                               JOIN   `accounts_users_rights`
                                               ON     `accounts_users_rights`.`users_id`  = :users_id
                                               AND    `accounts_users_rights`.`rights_id` = `accounts_rights`.`id`', [
                    ':users_id' => $id
                ]);

                $item['rights'] = implode(', ', $item['rights']);
            }
        }

        return $return;
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

        if ($this->parent instanceof Role) {
            // Delete the current list
            sql()->query('DELETE FROM `accounts_users_roles` 
                                WHERE       `accounts_users_roles`.`roles_id` = :roles_id', [
                ':roles_id' => $this->parent->getId()
            ]);

            // Add the new list
            foreach ($this->list as $id) {
                sql()->insert('accounts_users_roles', [
                    'roles_id' => $this->parent->getId(),
                    'users_id' => $id
                ]);
            }

        } elseif ($this->parent instanceof Right) {
            // Delete the current list
            sql()->query('DELETE FROM `accounts_users_rights` 
                                WHERE       `accounts_users_rights`.`rights_id` = :rights_id', [
                ':rights_id' => $this->parent->getId()
            ]);

            // Add the new list
            foreach ($this->list as $id) {
                sql()->insert('accounts_users_rights', [
                    'rights_id' => $this->parent->getId(),
                    'users_id'  => $id
                ]);
            }

            unset($user);
        }

        return $this;
    }
}