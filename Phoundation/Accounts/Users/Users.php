<?php

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\DataList\DataList;
use Phoundation\Databases\Sql\QueryBuilder;
use Phoundation\Web\Http\Html\Components\Input\Select;
use Phoundation\Web\Http\Html\Components\Table;


/**
 * Class Users
 *
 *
 *
 * @see \Phoundation\Data\DataList\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Users extends DataList
{
    /**
     * Users class constructor
     *
     * @param Role|User|null $parent
     * @param string|null $id_column
     */
    public function __construct(Role|User|null $parent = null, ?string $id_column = null)
    {
        $this->entry_class = User::class;
        $this->table_name  = 'accounts_users';

        $this->setHtmlQuery('SELECT   `id`, CONCAT(`first_names`, `last_names`) AS `name`, `nickname`, `email`, `status`, `created_on` 
                                   FROM     `accounts_users` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `name`');
        parent::__construct($parent, $id_column);
    }



    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @return Table
     */
    public function getHtmlTable(): Table
    {
        $table = parent::getHtmlTable();
        $table->setCheckboxSelectors(true);

        return $table;
    }



    /**
     * Returns an HTML <select> object with all available users
     *
     * @param string $name
     * @return Select
     */
    public static function getHtmlSelect(string $name = 'users_id'): Select
    {
        return Select::new()
            ->setSourceQuery('SELECT COALESCE(NULLIF(TRIM(CONCAT_WS(" ", `first_names`, `last_names`)), ""), `nickname`, `username`, `email`) AS `name` 
                                          FROM  `accounts_users`
                                          WHERE `status` IS NULL ORDER BY `name`')
            ->setName($name)
            ->setNone(tr('Please select a user'))
            ->setEmpty(tr('No users available'));
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
            $diff = Arrays::valueDiff($this->list, $rights_list);

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
     * @param User|array|string|int|null $user
     * @return static
     */
    public function add(User|array|string|int|null $user): static
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
                if (!array_key_exists($user->getId(), $this->list)) {
                    // Add entry to parent, Role or Right
                    if ($this->parent instanceof Role) {
                        Log::action(tr('Adding role ":role" to user ":user"', [
                            ':role' => $this->parent->getLogId(),
                            ':user' => $user->getLogId()
                        ]));

                        sql()->insert('accounts_users_roles', [
                            'roles_id' => $this->parent->getId(),
                            'users_id' => $user->getId()
                        ]);

                        // Add right to internal list
                        $this->addEntry($user);
                    } elseif ($this->parent instanceof Right) {
                        Log::action(tr('Adding right ":right" to user ":user"', [
                            ':right' => $this->parent->getLogId(),
                            ':user'  => $user->getLogId()
                        ]));

                        sql()->insert('accounts_users_rights', [
                            'rights_id' => $this->parent->getId(),
                            'users_id'  => $user->getId(),
                            'name'      => $this->parent->getName(),
                            'seo_name'  => $this->parent->getSeoName()
                        ]);

                        // Add right to internal list
                        $this->addEntry($user);
                    }
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
                    Log::action(tr('Removing role ":role" from user ":user"', [
                        ':role' => $this->parent->getLogId(),
                        ':user' => $user->getLogId()
                    ]));

                    sql()->delete('accounts_users_rights', [
                        'roles_id' => $this->parent->getId(),
                        'users_id' => $user->getId()
                    ]);

                    // Add right to internal list
                    $this->removeEntry($user);
                } elseif ($this->parent instanceof Right) {
                    Log::action(tr('Removing right ":right" from user ":user"', [
                        ':right' => $this->parent->getLogId(),
                        ':user'  => $user->getLogId()
                    ]));

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
            Log::action(tr('Removing role ":role" from all users', [
                ':right' => $this->parent->getLogId(),
            ]));

            sql()->query('DELETE FROM `accounts_users_roles` WHERE `roles_id` = :roles_id', [
                'roles_id' => $this->parent->getId()
            ]);

        } elseif ($this->parent instanceof Right) {
            Log::action(tr('Removing right ":right" from all users', [
                ':right' => $this->parent->getLogId(),
            ]));

            sql()->query('DELETE FROM `accounts_users_rights` WHERE `rights_id` = :rights_id', [
                'rights_id' => $this->parent->getId()
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
    public function load(?string $id_column = 'users_id'): static
    {
        if (!$id_column) {
            $id_column = 'users_id';
        }

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

        // The keys contain the ids...
        $this->list = array_flip($this->list);
        return $this;
    }



    /**
     * Load the data for this users list
     *
     * @param array|string|null $columns
     * @param array $filters
     * @return array
     */
    protected function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // Default columns
        if (!$columns) {
            $columns = 'id,domain,email,first_names,last_names,phones,roles';
        }

        // Default ordering
        if (!$order_by) {
            $order_by = ['email' => false];
        }

        // Get column information
        $columns = Arrays::force($columns);
        $roles   = Arrays::replaceIfExists($columns, 'roles', '1 AS roles');
        $rights  = Arrays::replaceIfExists($columns, 'rights', '1 AS rights');
        $columns = Strings::force($columns);

        // Build query
        $builder = new QueryBuilder();
        $builder->addSelect('SELECT ' . $columns);
        $builder->addFrom('FROM `accounts_users`');

        // Add ordering
        foreach ($order_by as $column => $direction) {
            $builder->addOrderBy('ORDER BY `' . $column . '` ' . ($direction ? 'DESC' : 'ASC'));
        }

        // Build filters
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
                    'users_id'  => $id,
                    'name'      => $this->parent->getName(),
                    'seo_name'  => $this->parent->getSeoName()
                ]);
            }

            unset($user);
        }

        return $this;
    }
}