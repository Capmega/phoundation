<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Rights;

use PDOStatement;
use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Roles\Interfaces\RoleInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Business\Companies\Departments\Department;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Databases\Sql\QueryBuilder;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Web\Http\Html\Components\Input\InputSelect;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;


/**
 * Class Rights
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Rights extends DataList implements RightsInterface
{
    /**
     * Roles class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `id`, `name` AS `right`, `description` 
                               FROM     `accounts_rights` 
                               WHERE    `status` IS NULL 
                               ORDER BY `name`');

        parent::__construct();
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'accounts_rights';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return Right::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return 'seo_name';
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
                $rights_list[] = static::getEntryClass()::get($right)->getId();
            }

            // Get a list of what we have to add and remove to get the same list, and apply
            $diff = Arrays::valueDiff($this->source, $rights_list);

            foreach ($diff['add'] as $right) {
                $this->parent->getRights()->addRight($right);
            }

            foreach ($diff['remove'] as $right) {
                $this->parent->getRights()->remove($right);
            }
        }

        return $this;
    }


    /**
     * Add the specified data entry to the data list
     *
     * @param RightInterface|array|string|int|null $right
     * @return static
     */
    public function addRight(RightInterface|array|string|int|null $right): static
    {
        $this->ensureParent('add entry to parent');

        if ($right) {
            if (is_array($right)) {
                // Add multiple rights
                foreach ($right as $entry) {
                    $this->addRight($entry);
                }

            } else {
                // Add single right. Since this is a Right object, the entry already exists in the database
                $right = Right::get($right);

                // Already exists?
                if (!array_key_exists($right->getId(), $this->source)) {
                    // Add entry to parent, User or Role
                    if ($this->parent instanceof UserInterface) {
                        Log::action(tr('Adding right ":right" to user ":user"', [
                            ':user'  => $this->parent->getLogId(),
                            ':right' => $right->getLogId()
                        ]));

                        sql()->dataEntryInsert('accounts_users_rights', [
                            'users_id'  => $this->parent->getId(),
                            'rights_id' => $right->getId(),
                            'name'      => $right->getName(),
                            'seo_name'  => $right->getSeoName()
                        ]);

                        // Add right to internal list
                        $this->addDataEntry($right);

                    } elseif ($this->parent instanceof RoleInterface) {
                        Log::action(tr('Adding right ":right" to role ":role"', [
                            ':role' => $this->parent->getLogId(),
                            ':right' => $right->getLogId()
                        ]));

                        sql()->dataEntryInsert('accounts_roles_rights', [
                            'roles_id'  => $this->parent->getId(),
                            'rights_id' => $right->getId()
                        ]);

                        // Add right to internal list
                        $this->addDataEntry($right);

                        // Update all users with this role to get the new right as well!
                        foreach ($this->parent->getUsers() as $user) {
                            User::get($user)->getRights()->addRight($right);
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
     * @param RightInterface|array|int|null $right
     * @return static
     */
    public function remove(RightInterface|array|int|null $right): static
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

                if ($this->parent instanceof UserInterface) {
                    Log::action(tr('Removing right ":right" from user ":user"', [
                        ':user'  => $this->parent->getLogId(),
                        ':right' => $right->getLogId()
                    ]));

                    sql()->dataEntrydelete('accounts_users_rights', [
                        'users_id'  => $this->parent->getId(),
                        'rights_id' => $right->getId()
                    ]);

                    // Add right to internal list
                    $this->deleteEntry($right);
                } elseif ($this->parent instanceof RoleInterface) {
                    Log::action(tr('Removing right ":right" from role ":role"', [
                        ':role' => $this->parent->getLogId(),
                        ':right' => $right->getLogId()
                    ]));

                    sql()->dataEntrydelete('accounts_roles_rights', [
                        'roles_id'  => $this->parent->getId(),
                        'rights_id' => $right->getId()
                    ]);

                    // Update all users with this role to get the new right as well!
                    foreach ($this->parent->getUsers() as $user) {
                        User::get($user)->getRights()->remove($right);
                    }

                    // Add right to internal list
                    $this->deleteEntry($right);
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

        if ($this->parent instanceof UserInterface) {
            Log::action(tr('Removing all rights from user ":user"', [
                ':user' => $this->parent->getLogId(),
            ]));

            sql()->query('DELETE FROM `accounts_users_rights` WHERE `users_id` = :users_id', [
                'users_id'  => $this->parent->getId()
            ]);

        } elseif ($this->parent instanceof RoleInterface) {
            Log::action(tr('Removing all rights from role ":role"', [
                ':role' => $this->parent->getLogId(),
            ]));

            sql()->query('DELETE FROM `accounts_roles_rights` WHERE `roles_id` = :roles_id', [
                'roles_id'  => $this->parent->getId()
            ]);
        }

        return parent::clear();
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
            if ($this->parent instanceof UserInterface) {
                $this->source = sql()->list('SELECT `accounts_rights`.`seo_name` AS `key`, `accounts_rights`.*
                                                   FROM   `accounts_users_rights` 
                                                   JOIN   `accounts_rights` 
                                                   ON     `accounts_users_rights`.`rights_id` = `accounts_rights`.`id`
                                                   WHERE  `accounts_users_rights`.`users_id`  = :users_id', [
                    ':users_id' => $this->parent->getId()
                ]);

            } elseif ($this->parent instanceof RoleInterface) {
                $this->source = sql()->list('SELECT `accounts_rights`.`seo_name` AS `key`, `accounts_rights`.* 
                                                   FROM   `accounts_roles_rights` 
                                                   JOIN   `accounts_rights` 
                                                   ON     `accounts_roles_rights`.`rights_id` = `accounts_rights`.`id`
                                                   WHERE  `accounts_roles_rights`.`roles_id`  = :roles_id', [
                    ':roles_id' => $this->parent->getId()
                ]);

            }

        } else {
            // Load all
            $this->source = sql()->list('SELECT `id` FROM `accounts_rights`');
        }

        return $this;
    }


    /**
     * Checks the list of specified rights if they exist and returns those rights that do not exist.
     *
     * @param array|string $rights
     * @return array
     */
    public static function getNotExist(array|string $rights): array
    {
        $rights = Arrays::force($rights);
        $values = Sql::in($rights);
        $rights = array_flip($rights);

        $exist  = sql()->query('SELECT `seo_name` 
                                      FROM   `accounts_rights` 
                                      WHERE  `seo_name` IN (' . implode(', ', array_keys($values)) . ')', $values);

        while ($right = $exist->fetchColumn(0)) {
            unset($rights[$right]);
        }

        return array_flip($rights);
    }


    /**
     * Load the data for this right list
     *
     * @param array|string|null $columns
     * @param array $filters
     * @return array
     */
    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
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
        $builder->addSelect($columns);
        $builder->addFrom('`accounts_rights`');

        // Add ordering
        foreach ($order_by as $column => $direction) {
            $builder->addOrderBy('`' . $column . '` ' . ($direction ? 'DESC' : 'ASC'));
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

        if ($this->parent instanceof UserInterface) {
            // Delete the current list
            sql()->query('DELETE FROM `accounts_users_rights` 
                                WHERE       `accounts_users_rights`.`users_id` = :users_id', [
                ':users_id' => $this->parent->getId()
            ]);

            // Add the new list
            foreach ($this->source as $id) {
                $right = new Right($id);

                sql()->dataEntryInsert('accounts_users_rights', [
                    'users_id'  => $this->parent->getId(),
                    'rights_id' => $id,
                    'name'      => $right->getName(),
                    'seo_name'  => $right->getSeoName()
                ]);
            }

        } elseif ($this->parent instanceof RoleInterface) {
            // Delete the current list
            sql()->query('DELETE FROM `accounts_roles_rights` 
                                WHERE       `accounts_roles_rights`.`roles_id` = :roles_id', [
                ':roles_id' => $this->parent->getId()
            ]);

            // Add the new list
            foreach ($this->source as $id) {
                sql()->dataEntryInsert('accounts_roles_rights', [
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
     * @return InputSelect
     */
    public function getHtmlSelect(string $value_column = 'CONCAT(UPPER(LEFT(`name`, 1)), SUBSTRING(`name`, 2)) AS `name`', string $key_column = 'seo_name', ?string $order = '`name` ASC'): SelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column, $order)
            ->setName('rights_id')
            ->setNone(tr('Select a right'))
            ->setEmpty(tr('No rights available'));
    }
}