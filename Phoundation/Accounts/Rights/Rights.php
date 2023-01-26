<?php

namespace Phoundation\Accounts\Rights;

use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\DataList\DataList;
use Phoundation\Databases\Sql\QueryBuilder;
use Phoundation\Web\Http\Html\Components\Input\Select;


/**
 * Class Rights
 *
 *
 *
 * @see \Phoundation\Data\DataList\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Rights extends DataList
{
    /**
     * Rights class constructor
     *
     * @param User|Role|null $parent
     * @param string|null $id_column
     */
    public function __construct(User|Role|null $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Right::class;
        $this->table_name  = 'accounts_rights';

        $this->setHtmlQuery('SELECT   `id`, `name`, `description` 
                                   FROM     `accounts_rights` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `name`');
        parent::__construct($parent, $id_column);
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
                $this->parent->rights()->add($right);
            }

            foreach ($diff['remove'] as $right) {
                $this->parent->rights()->remove($right);
            }
        }

        return $this;
    }



    /**
     * Add the specified data entry to the data list
     *
     * @param Right|array|string|int|null $right
     * @return static
     */
    public function add(Right|array|string|int|null $right): static
    {
        $this->ensureParent('add entry to parent');

        if ($right) {
            if (is_array($right)) {
                // Add multiple rights
                foreach ($right as $entry) {
                    $this->add($entry);
                }

            } else {
                // Add single right. Since this is a Right object, the entry already exists in the database
                $right = Right::get($right);

                // Already exists?
                if (!array_key_exists($right->getId(), $this->list)) {
                    // Add entry to parent, User or Role
                    if ($this->parent instanceof User) {
                        Log::action(tr('Adding right ":right" to user ":user"', [
                            ':user'  => $this->parent->getLogId(),
                            ':right' => $right->getLogId()
                        ]));

                        sql()->insert('accounts_users_rights', [
                            'users_id'  => $this->parent->getId(),
                            'rights_id' => $right->getId(),
                            'name'      => $right->getName(),
                            'seo_name'  => $right->getSeoName()
                        ]);

                        // Add right to internal list
                        $this->addEntry($right);

                    } elseif ($this->parent instanceof Role) {
                        Log::action(tr('Adding right ":right" to role ":role"', [
                            ':role' => $this->parent->getLogId(),
                            ':right' => $right->getLogId()
                        ]));

                        sql()->insert('accounts_roles_rights', [
                            'roles_id'  => $this->parent->getId(),
                            'rights_id' => $right->getId()
                        ]);

                        // Add right to internal list
                        $this->addEntry($right);

                        // Update all users with this role to get the new right as well!
                        foreach ($this->parent->users() as $user) {
                            User::get($user)->rights()->add($right);
                        }
                    }
                }
            }
        }

        return $this;
    }



    /**
     * Remove the specified data entry from the data list
     *
     * @param Right|array|int|null $right
     * @return static
     */
    public function remove(Right|array|int|null $right): static
    {
        $this->ensureParent('remove entry from parent');

        if ($right) {
            if (is_array($right)) {
                // Add multiple rights
                foreach ($right as $entry) {
                    $this->remove($entry);
                }

            } else {
                // Add single right. Since this is a Right object, the entry already exists in the database
                $right = Right::get($right);

                if ($this->parent instanceof User) {
                    Log::action(tr('Removing right ":right" from user ":user"', [
                        ':user' => $this->parent->getLogId(),
                        ':right' => $right->getLogId()
                    ]));

                    sql()->delete('accounts_users_rights', [
                        'users_id'  => $this->parent->getId(),
                        'rights_id' => $right->getId()
                    ]);

                    // Add right to internal list
                    $this->removeEntry($right);
                } elseif ($this->parent instanceof Role) {
                    Log::action(tr('Removing right ":right" from role ":role"', [
                        ':role' => $this->parent->getLogId(),
                        ':right' => $right->getLogId()
                    ]));

                    sql()->delete('accounts_roles_rights', [
                        'roles_id'  => $this->parent->getId(),
                        'rights_id' => $right->getId()
                    ]);

                    // Update all users with this role to get the new right as well!
                    foreach ($this->parent->users() as $user) {
                        User::get($user)->rights()->remove($right);
                    }

                    // Add right to internal list
                    $this->removeEntry($right);
                }
            }
        }

        return $this;
    }



    /**
     * Remove all rights for this role
     *
     * @return static
     */
    public function clear(): static
    {
        $this->ensureParent('clear all entries from parent');

        if ($this->parent instanceof User) {
            Log::action(tr('Removing all rights from user ":user"', [
                ':user' => $this->parent->getLogId(),
            ]));

            sql()->query('DELETE FROM `accounts_users_rights` WHERE `users_id` = :users_id', [
                'users_id'  => $this->parent->getId()
            ]);

        } elseif ($this->parent instanceof Role) {
            Log::action(tr('Removing all rights from role ":role"', [
                ':role' => $this->parent->getLogId(),
            ]));

            sql()->query('DELETE FROM `accounts_roles_rights` WHERE `roles_id` = :roles_id', [
                'roles_id'  => $this->parent->getId()
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
    public function load(?string $id_column = 'rights_id'): static
    {
        if (!$id_column) {
            $id_column = 'rights_id';
        }

        if ($this->parent) {
            // Load only rights for specified parent
            if ($this->parent instanceof User) {
                $this->list = sql()->list('SELECT `accounts_users_rights`.`' . $id_column . '` 
                                               FROM   `accounts_users_rights` 
                                               WHERE  `accounts_users_rights`.`users_id` = :users_id', [
                    ':users_id' => $this->parent->getId()
                ]);

            } elseif ($this->parent instanceof Role) {
                $this->list = sql()->list('SELECT `accounts_roles_rights`.`' . $id_column . '` 
                                           FROM   `accounts_roles_rights` 
                                           WHERE  `accounts_roles_rights`.`roles_id` = :roles_id', [
                    ':roles_id' => $this->parent->getId()
                ]);

            }

        } else {
            // Load all
            $this->list = sql()->list('SELECT `id` FROM `accounts_rights`');
        }

        // The keys contain the ids...
        $this->list = array_flip($this->list);

        return $this;
    }



    /**
     * Load the data for this right list
     *
     * @param array|string|null $columns
     * @param array $filters
     * @return array
     */
    protected function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // Default columns
        if (!$columns) {
            $columns = 'id,name,roles';
        }

        // Default ordering
        if (!$order_by) {
            $order_by = ['name' => false];
        }

        // Get column information
        $columns = Arrays::force($columns);
        $users   = Arrays::replaceIfExists($columns, 'users', '1 AS `users`');
        $roles   = Arrays::replaceIfExists($columns, 'roles', '1 AS `roles`');
        $columns = Strings::force($columns);

        // Build query
        $builder = new QueryBuilder();
        $builder->addSelect(' SELECT ' . $columns);
        $builder->addFrom('FROM `accounts_rights`');

        // Add ordering
        foreach ($order_by as $column => $direction) {
            $builder->addOrderBy('ORDER BY `' . $column . '` ' . ($direction ? 'DESC' : 'ASC'));
        }

        // Build filters
        foreach ($filters as $key => $value){
            switch ($key) {
                case 'users':
                    $builder->addJoin('JOIN `accounts_users` 
                                            ON   `accounts_users`.`email` ' . $builder->compareQuery('email', $value) . ' 
                                            JOIN `accounts_users_rights` 
                                            ON   `accounts_users_rights`.`users_id`  = `accounts_users`.`id` 
                                            AND  `accounts_users_rights`.`rights_id` = `accounts_rights`.`id`');
                    break;

                case 'roles':
                    $builder->addJoin('JOIN `accounts_roles` 
                                            ON   `accounts_roles`.`name` ' . $builder->compareQuery('role', $value) . ' 
                                            JOIN `accounts_roles_rights` 
                                            ON   `accounts_roles_rights`.`roles_id`  = `accounts_roles`.`id` 
                                            AND  `accounts_roles_rights`.`rights_id` = `accounts_rights`.`id`');
                    break;
            }
        }

        $return = sql()->list($builder->getQuery(), $builder->getExecute());

        if ($users) {
            // Add roles information to each user
            foreach ($return as $id => &$item) {
                $item['users'] = sql()->list('SELECT `email`
                                              FROM   `accounts_users`
                                              JOIN   `accounts_users_rights`
                                              ON     `accounts_users_rights`.`rights_id` = :rights_id
                                              AND    `accounts_users_rights`.`users_id` = `accounts_users`.`id`', [
                    ':rights_id' => $id
                ]);

                $item['users'] = implode(', ', $item['users']);
            }

            unset($item);
        }

        if ($roles) {
            // Add roles information to each user
            // Add roles information to each user
            foreach ($return as $id => &$item) {
                $item['roles'] = sql()->list('SELECT `name`
                                               FROM   `accounts_roles`
                                               JOIN   `accounts_roles_rights`
                                               ON     `accounts_roles_rights`.`rights_id`  = :rights_id
                                               AND    `accounts_roles_rights`.`roles_id` = `accounts_roles`.`id`', [
                    ':rights_id' => $id
                ]);

                $item['roles'] = implode(', ', $item['roles']);
            }

            unset($item);
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

        if ($this->parent instanceof User) {
            // Delete the current list
            sql()->query('DELETE FROM `accounts_users_rights` 
                                WHERE       `accounts_users_rights`.`users_id` = :users_id', [
                ':users_id' => $this->parent->getId()
            ]);

            // Add the new list
            foreach ($this->list as $id) {
                $right = new Right($id);

                sql()->insert('accounts_users_rights', [
                    'users_id'  => $this->parent->getId(),
                    'rights_id' => $id,
                    'name'      => $right->getName(),
                    'seo_name'  => $right->getSeoName()
                ]);
            }

        } elseif ($this->parent instanceof Role) {
            // Delete the current list
            sql()->query('DELETE FROM `accounts_roles_rights` 
                                WHERE       `accounts_roles_rights`.`roles_id` = :roles_id', [
                ':roles_id' => $this->parent->getId()
            ]);

            // Add the new list
            foreach ($this->list as $id) {
                sql()->insert('accounts_roles_rights', [
                    'roles_id'  => $this->parent->getId(),
                    'rights_id' => $id
                ]);
            }
        }

        return $this;
    }



    /**
     * Returns a select with the available rights
     *
     * @return Select
     */
    public function getHtmlSelect(): Select
    {
        return Select::new()
            ->setNone(tr('Select a right'))
            ->setEmpty(tr('No rights available'))
            ->setSourceQuery('SELECT `seo_name`, `name` FROM `accounts_rights` WHERE `status` IS NULL');
    }
}