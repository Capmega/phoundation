<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Roles;

use PDOStatement;
use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Roles\Interfaces\RoleInterface;
use Phoundation\Accounts\Roles\Interfaces\RolesInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Databases\Sql\QueryBuilder;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Input\Select;


/**
 * Class Roles
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Roles extends DataList implements RolesInterface
{
    /**
     * Roles class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null)
    {
        $this->unique_column = 'seo_name';
        $this->entry_class   = Role::class;
        $this->table         = 'accounts_roles';

        $this->setQuery('SELECT   `id`, `name`, `description` 
                               FROM     `accounts_roles` 
                               WHERE    `status` IS NULL 
                               ORDER BY `name`');

        parent::__construct($source, $execute);
    }


    /**
     * Set the entries to the specified list
     *
     * @param array|null $list
     * @return static
     */
    public function set(?array $list): static
    {
        $this->ensureParent('save entries');

        if (is_array($list)) {
            // Convert the list to id's
            $rights_list = [];

            foreach ($list as $right) {
                $rights_list[] = $this->entry_class::get($right)->getId();
            }

            // Get a list of what we have to add and remove to get the same list, and apply
            $diff = Arrays::valueDiff(array_keys($this->source), $rights_list);

            foreach ($diff['add'] as $right) {
                $this->parent->roles()->add($right);
            }

            foreach ($diff['remove'] as $right) {
                $this->parent->roles()->remove($right);
            }
        }

        return $this;
    }


    /**
     * Add the specified data entry to the data list
     *
     * @param RoleInterface|array|string|int|null $role
     * @return static
     */
    public function add(RoleInterface|array|string|int|null $role): static
    {
        $this->ensureParent('add entry to parent');

        if ($role) {
            if (is_array($role)) {
                // Add multiple rights
                foreach ($role as $entry) {
                    $this->add($entry);
                }

            } else {
                // Add single right. Since this is a Role object, the entry already exists in the database
                $role = Role::get($role);

                // Already exists?
                if (!array_key_exists($role->getId(), $this->source)) {
                    // Add entry to parent, User or Right
                    if ($this->parent instanceof UserInterface) {
                        Log::action(tr('Adding role ":role" to user ":user"', [
                            ':user' => $this->parent->getLogId(),
                            ':role' => $role->getLogId()
                        ]));

                        sql()->insert('accounts_users_roles', [
                            'users_id' => $this->parent->getId(),
                            'roles_id' => $role->getId()
                        ]);

                        // Add right to internal list
                        $this->addEntry($role);

                        // Add rights to the user
                        foreach ($role->rights() as $right) {
                            $this->parent->rights()->add($right);
                        }

                    } elseif ($this->parent instanceof RightInterface) {
                        Log::action(tr('Adding right ":right" to role ":role"', [
                            ':right' => $this->parent->getLogId(),
                            ':role'  => $role->getLogId()
                        ]));

                        sql()->insert('accounts_roles_rights', [
                            'rights_id' => $this->parent->getId(),
                            'roles_id'  => $role->getId()
                        ]);

                        // Add right to internal list
                        $this->addEntry($role);

                        // Update all users with this right to get the new right as well!
                        foreach ($this->parent->users() as $user) {
                            User::get($user)->rights()->add($this->parent);
                        }
                    }
                }
            }
        }

        return $this;
    }


    /**
     * Remove the specified role from the roles list
     *
     * @param RoleInterface|array|int|null $role
     * @return static
     */
    public function remove(RoleInterface|array|int|null $role): static
    {
        $this->ensureParent('remove entry from parent');

        if ($role) {
            if (is_array($role)) {
                // Add multiple rights
                foreach ($role as $entry) {
                    $this->remove($entry);
                }

            } else {
                // Add single right. Since this is a Role object, the entry already exists in the database
                $role = Role::get($role);

                if ($this->parent instanceof UserInterface) {
                    Log::action(tr('Removing role ":role" from user ":user"', [
                        ':user' => $this->parent->getLogId(),
                        ':role' => $role->getLogId()
                    ]));

                    sql()->delete('accounts_users_roles', [
                        'users_id' => $this->parent->getId(),
                        'roles_id' => $role->getId()
                    ]);

                    // Add right to internal list
                    $this->removeEntry($role);

                    foreach ($role->rights() as $right) {
                        $this->parent->rights()->remove($right);
                    }

                } elseif ($this->parent instanceof RightInterface) {
                    Log::action(tr('Removing right ":right" from role ":role"', [
                        ':right' => $this->parent->getLogId(),
                        ':role'  => $role->getLogId()
                    ]));

                    sql()->delete('accounts_roles_rights', [
                        'rights_id' => $this->parent->getId(),
                        'roles_id'  => $role->getId()
                    ]);

                    // Add right to internal list
                    $this->removeEntry($role);

                    // Update all users with this right to remove the new right as well!
                    foreach ($this->parent->users() as $user) {
                        User::get($user)->rights()->remove($this->parent);
                    }
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

        if ($this->parent instanceof UserInterface) {
            Log::action(tr('Removing all roles from user ":user"', [
                ':user' => $this->parent->getLogId(),
            ]));

            sql()->query('DELETE FROM `accounts_users_roles` WHERE `users_id` = :users_id', [
                'users_id'  => $this->parent->getId()
            ]);

        } elseif ($this->parent instanceof RightInterface) {
            Log::action(tr('Removing right ":right" from all roles', [
                ':right' => $this->parent->getLogId(),
            ]));

            sql()->query('DELETE FROM `accounts_roles_rights` WHERE `rights_id` = :rights_id', [
                'rights_id'  => $this->parent->getId()
            ]);
        }

        return parent::clearEntries();
    }


    /**
     * Load the data for this rights list into the object
     *
     * @param string|null $id_column
     * @return static
     */
    public function load(?string $id_column = 'roles_id'): static
    {
        if (!$id_column) {
            $id_column = 'roles_id';
        }

        if ($this->parent) {
            if ($this->parent instanceof UserInterface) {
                $this->source = sql()->list('SELECT `accounts_roles`.`seo_name` AS `key`, `accounts_roles`.*
                                                   FROM   `accounts_users_roles` 
                                                   JOIN   `accounts_roles` 
                                                   ON     `accounts_users_roles`.`roles_id`  = `accounts_roles`.`id`
                                                   WHERE  `accounts_users_roles`.`users_id` = :users_id', [
                    ':users_id' => $this->parent->getId()
                ]);

            } elseif ($this->parent instanceof RightInterface) {
                $this->source = sql()->list('SELECT `accounts_roles`.`seo_name` AS `key`, `accounts_roles`.* 
                                                   FROM   `accounts_roles_rights` 
                                                   JOIN   `accounts_roles` 
                                                   ON     `accounts_roles_rights`.`roles_id`  = `accounts_roles`.`id`
                                                   WHERE  `accounts_roles_rights`.`rights_id` = :rights_id', [
                    ':rights_id' => $this->parent->getId()
                ]);

            }

        } else {
            $this->source = sql()->list('SELECT `id` FROM `accounts_rights`');
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
    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // Default columns
        if (!$columns) {
            $columns = 'id,name,rights';
        }

        // Default ordering
        if (!$order_by) {
            $order_by = ['name' => false];
        }

        // Get column information
        $columns = Arrays::force($columns);
        $users   = Arrays::replaceIfExists($columns, 'users' , '1 AS `users`');
        $rights  = Arrays::replaceIfExists($columns, 'rights', '1 AS `rights`');
        $columns = Strings::force($columns);

        // Build query
        $builder = new QueryBuilder();
        $builder->addSelect($columns);
        $builder->addFrom('`accounts_roles`');

        // Add ordering
        foreach ($order_by as $column => $direction) {
            $builder->addOrderBy($column . '` ' . ($direction ? 'DESC' : 'ASC'));
        }

        // Build filters
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
        $this->ensureParent('save parent entries');

        if ($this->parent instanceof UserInterface) {
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

        } elseif ($this->parent instanceof RightInterface) {
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
     * Returns an HTML select component object containing the entries in this list
     *
     * @return SelectInterface
     */
    public function getHtmlSelect(): SelectInterface
    {
        return Select::new()
            ->setNone(tr('Select a role'))
            ->setEmpty(tr('No roles available'))
            ->setSourceQuery('SELECT `seo_name`, `name` FROM `accounts_roles` WHERE `status` IS NULL');
    }
}