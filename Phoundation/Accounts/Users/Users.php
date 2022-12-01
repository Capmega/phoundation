<?php

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Data\DataList;
use Phoundation\Data\Exception\DataEntryAlreadyExistsException;
use Phoundation\Databases\Sql\QueryBuilder;



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
     * @param array $list
     * @return static
     */
    public function set(array $list): static
    {
        $this->ensureParent('save entries');

        // Convert the list to id's
        $rights_list = [];

        foreach ($list as $right) {
            $rights_list[] = $this->entry_class::new($right)->getId();
        }

        // Get a list of what we have to add and remove to get the same list, and apply
        $diff = Arrays::valueDiff($this->list, $rights_list);

        foreach ($diff['add'] as $right) {
            $this->parent->roles()->add($right);
        }

        foreach ($diff['remove'] as $right) {
            $this->parent->roles()->remove($right);
        }

        return $this;
    }


    /**
     * Add the specified data entry to the data list
     *
     * @param User|array|int|null $user
     * @return static
     */
    public function add(User|array|int|null $user): static
    {
        $this->ensureParent('add entry to parent');

        if ($user) {
            if (is_array($user)) {
                // Add multiple rights
                foreach ($user as $entry) {
                    $this->add($entry);
                }

            } else {
                // Add single right. Since this is a User object, the entry already exists in the database
                $user = User::get($user);

                // Already exists?
                if (in_array($user->getId(), $this->list)) {
                    throw DataEntryAlreadyExistsException::new(tr('Cannot add user ":user", it already exists for ":type" ":parent"', [
                        ':type' => Strings::fromReverse(get_class($this->parent), '\\'),
                        ':right' => $user->getName(),
                        ':parent' => $this->parent->getName()
                    ]))->makeWarning();
                }

                // Add entry to parent, Role or User
                if ($this->parent instanceof Role) {
                    sql()->insert('accounts_users_rights', [
                        'roles_id' => $this->parent->getId(),
                        'users_id' => $user->getId()
                    ]);

                    // Add right to internal list
                    $this->addEntry($user);
                } elseif ($this->parent instanceof Right) {
                    sql()->insert('accounts_users_rights', [
                        'rights_id' => $this->parent->getId(),
                        'users_id' => $user->getId()
                    ]);

                    // Add right to internal list
                    $this->addEntry($user);
                }
            }
        }

        return $this;
    }


    /**
     * Remove the specified data entry from the data list
     *
     * @param User|array|int|null $user
     * @return static
     */
    public function remove(User|array|int|null $user): static
    {
        $this->ensureParent('remove entry from parent');

        if ($user) {
            if (is_array($user)) {
                // Add multiple rights
                foreach ($user as $entry) {
                    $this->remove($entry);
                }

            } else {
                // Add single user. Since this is a User object, the entry already exists in the database
                $user = User::get($user);

                if ($this->parent instanceof Role) {
                    sql()->delete('accounts_users_rights', [
                        'roles_id' => $this->parent->getId(),
                        'users_id' => $user->getId()
                    ]);

                    // Add right to internal list
                    $this->removeEntry($user);
                } elseif ($this->parent instanceof Right) {
                    sql()->delete('accounts_users_rights', [
                        'rights_id' => $this->parent->getId(),
                        'users_id' => $user->getId()
                    ]);

                    // Add right to internal list
                    $this->removeEntry($user);
                }
            }
        }

        return $this;
    }


    /**
     * Remove all rights for this right
     *
     * @return static
     */
    public function clear(): static
    {
        $this->ensureParent('clear all entries from parent');

        if ($this->parent instanceof Role) {
            sql()->query('DELETE FROM `accounts_users_roles` WHERE `roles_id` = :roles_id', [
                'roles_id' => $this->parent->getId()
            ]);

        } elseif ($this->parent instanceof Right) {
            sql()->query('DELETE FROM `accounts_users_rights` WHERE `rights_id` = :rights_id', [
                'rights_id' => $this->parent->getId()
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
                $this->list = sql()->list('SELECT `accounts_users_roles`.`users_id` 
                                           FROM   `accounts_users_roles` 
                                           WHERE  `accounts_users_roles`.`roles_id` = :roles_id', [
                    ':roles_id' => $this->parent->getId()
                ]);

            } elseif ($this->parent instanceof Right) {
                $this->list = sql()->list('SELECT `accounts_users_rights`.`users_id` 
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
        $roles = Arrays::replaceIfExists($columns, 'roles', '1 AS roles');
        $rights = Arrays::replaceIfExists($columns, 'rights', '1 AS rights');
        $columns = Strings::force($columns);

        // Build query
        $builder = new QueryBuilder();
        $builder->addSelect(' SELECT ' . $columns);
        $builder->addFrom('FROM `accounts_users`');

        foreach ($filters as $key => $value) {
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
        $this->ensureParent('save parent entries');

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
                    'users_id' => $id
                ]);
            }

            unset($user);
        }

        return $this;
    }
}