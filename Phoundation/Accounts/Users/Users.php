<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Roles\Interfaces\RoleInterface;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Interfaces\UsersInterface;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Input\InputSelect;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\InputSelectInterface;
use Stringable;


/**
 * Class Users
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Users extends DataList implements UsersInterface
{
    /**
     * Users class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT    `accounts_users`.`id`, 
                                         TRIM(CONCAT(`first_names`, " ", `last_names`)) AS `name`, 
                                         GROUP_CONCAT(CONCAT(UPPER(LEFT(`accounts_roles`.`name`, 1)), SUBSTRING(`accounts_roles`.`name`, 2)) SEPARATOR ", ") AS `roles`, 
                                         `accounts_users`.`email`, 
                                         `accounts_users`.`status`, 
                                         `accounts_users`.`created_on`
                               FROM      `accounts_users` 
                               LEFT JOIN `accounts_users_roles`
                               ON        `accounts_users_roles`.`users_id` = `accounts_users`.`id`  
                               LEFT JOIN `accounts_roles`
                               ON        `accounts_roles`.`id` = `accounts_users_roles`.`roles_id`
                               WHERE     `accounts_users`.`status` IS NULL AND `email` != "guest" 
                               GROUP BY  `accounts_users`.`id`
                               ORDER BY  `name`');

        parent::__construct();
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'accounts_users';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return User::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return 'email';
    }


    /**
     * Set the new users for the current parents to the specified list
     *
     * @param array|null $list
     * @return static
     */
    public function setUsers(?array $list): static
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
                $this->parent->getRoles()->addRole($right);
            }

            foreach ($diff['delete'] as $right) {
                $this->parent->getRoles()->remove($right);
            }
        }

        return $this;
    }


    /**
     * Add the specified user to the data list
     *
     * @param UserInterface|array|string|int|null $user
     * @param string|null $column
     * @return static
     */
    public function addUser(UserInterface|array|string|int|null $user, ?string $column = null): static
    {
        $this->ensureParent('add User entry to parent');

        if ($user) {
            if (is_array($user)) {
                // Add multiple rights
                foreach ($user as $entry) {
                    $this->addUser($entry, $column);
                }

            } else {
                // Add single right. Since this is a User object, the entry already exists in the database
                $user = User::get($user, $column);

                // User already exists for this parent?
                if ($this->hasUser($user)) {
                    // Ignore and continue
                    return $this;
                }

                // Add entry to parent, Role or Right
                if ($this->parent instanceof RoleInterface) {
                    Log::action(tr('Adding role ":role" to user ":user"', [
                        ':role' => $this->parent->getLogId(),
                        ':user' => $user->getLogId()
                    ]), 3);

                    sql()->dataEntryInsert('accounts_users_roles', [
                        'roles_id' => $this->parent->getId(),
                        'users_id' => $user->getId()
                    ]);

                    // Add right to internal list
                    $this->addDataEntry($user);
                } elseif ($this->parent instanceof RightInterface) {
                    Log::action(tr('Adding right ":right" to user ":user"', [
                        ':right' => $this->parent->getLogId(),
                        ':user'  => $user->getLogId()
                    ]), 3);

                    sql()->dataEntryInsert('accounts_users_rights', [
                        'rights_id' => $this->parent->getId(),
                        'users_id'  => $user->getId(),
                        'name'      => $this->parent->getName(),
                        'seo_name'  => $this->parent->getSeoName()
                    ]);

                    // Add right to internal list
                    $this->addDataEntry($user);
                }
            }
        }

        return $this;
    }


    /**
     * Remove the specified data entry from the data list
     *
     * @param UserInterface|Stringable|array|string|float|int $user
     * @return static
     */
    public function deleteKeys(UserInterface|Stringable|array|string|float|int $user): static
    {
        $this->ensureParent('delete entry from parent');

        if ($user) {
            if (is_array($user)) {
                // Add multiple rights
                foreach ($user as $entry) {
                    $this->deleteKeys($entry);
                }

            } else {
                // Add single user. Since this is a User object, the entry already exists in the database
                $user = User::get($user);

                if ($this->parent instanceof RoleInterface) {
                    Log::action(tr('Removing user ":user" from role ":role"', [
                        ':role' => $this->parent->getLogId(),
                        ':user' => $user->getLogId()
                    ]));

                    sql()->dataEntryDelete('accounts_users_rights', [
                        'roles_id' => $this->parent->getId(),
                        'users_id' => $user->getId()
                    ]);

                    // Remove user from internal list
                    parent::deleteAll($user->getId());

                } elseif ($this->parent instanceof RightInterface) {
                    Log::action(tr('Removing user ":user" from right ":right"', [
                        ':right' => $this->parent->getLogId(),
                        ':user'  => $user->getLogId()
                    ]), 3);

                    sql()->dataEntryDelete('accounts_users_rights', [
                        'rights_id' => $this->parent->getId(),
                        'users_id' => $user->getId()
                    ]);

                    // Remove user from internal list
                    parent::deleteAll($user->getId());
                }
            }
        }

        return $this;
    }


    /**
     * Returns true if the parent has the specified user
     *
     * @param UserInterface $user
     * @return bool
     */
    public function hasUser(UserInterface $user): bool
    {
        if (!$this->parent) {
            throw OutOfBoundsException::new('Cannot check if parent has the specified user, this users list has no parent specified');
        }

        if ($this->parent instanceof RoleInterface) {
            return (bool) sql()->get('SELECT `id` 
                                            FROM   `accounts_users_roles` 
                                            WHERE  `users_id` = :users_id 
                                            AND    `roles_id` = :roles_id', [
                ':roles_id' => $this->parent->getId(),
                ':users_id' => $user->getId()
            ]);
        }

        // No user? Then it must be a right
        return (bool) sql()->get('SELECT `id` 
                                        FROM   `accounts_users_rights` 
                                        WHERE  `users_id`  = :users_id 
                                        AND    `rights_id` = :rights_id', [
            ':rights_id' => $this->parent->getId(),
            ':users_id'  => $user->getId()
        ]);
    }


    /**
     * Remove all rights for this right
     *
     * @return static
     */
    public function clear(): static
    {
        $this->ensureParent('clear all entries from parent');

        if ($this->parent instanceof RoleInterface) {
            Log::action(tr('Removing role ":role" from all users', [
                ':right' => $this->parent->getLogId(),
            ]));

            sql()->query('DELETE FROM `accounts_users_roles` WHERE `roles_id` = :roles_id', [
                'roles_id' => $this->parent->getId()
            ]);

        } elseif ($this->parent instanceof RightInterface) {
            Log::action(tr('Removing right ":right" from all users', [
                ':right' => $this->parent->getLogId(),
            ]), 3);

            sql()->query('DELETE FROM `accounts_users_rights` WHERE `rights_id` = :rights_id', [
                'rights_id' => $this->parent->getId()
            ]);
        }

        return parent::clear();
    }


    /**
     * Load the data for this rights list into the object
     *
     * @return static
     */
    public function load(): static
    {
        if ($this->parent) {
            if ($this->parent instanceof RoleInterface) {
                $this->source = sql()->list('SELECT `accounts_users`.`email` AS `key`, `accounts_users`.* 
                                                   FROM   `accounts_users_roles` 
                                                   JOIN   `accounts_users` 
                                                   ON     `accounts_users_roles`.`users_id` = `accounts_users`.`id`
                                                   WHERE  `accounts_users_roles`.`roles_id` = :roles_id', [
                    ':roles_id' => $this->parent->getId()
                ]);

            } elseif ($this->parent instanceof RightInterface) {
                $this->source = sql()->list('SELECT `accounts_users`.`email` AS `key`, `accounts_users`.* 
                                                   FROM   `accounts_users_rights` 
                                                   JOIN   `accounts_users` 
                                                   ON     `accounts_users_rights`.`users_id`  = `accounts_users`.`id`
                                                   WHERE  `accounts_users_rights`.`rights_id` = :rights_id', [
                    ':rights_id' => $this->parent->getId()
                ]);

            }

        } else {
            parent::load();
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
    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
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
        $builder->addSelect($columns);
        $builder->addFrom('`accounts_users`');

        // Add ordering
        foreach ($order_by as $column => $direction) {
            $builder->addOrderBy($column . '` ' . ($direction ? 'DESC' : 'ASC'));
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
//        $this->ensureParent('save parent entries');
//
//        if ($this->parent instanceof RoleInterface) {
//            // Delete the current list
//            sql()->query('DELETE FROM `accounts_users_roles`
//                                WHERE       `accounts_users_roles`.`roles_id` = :roles_id', [
//                ':roles_id' => $this->parent->getId()
//            ]);
//
//            // Add the new list
//            foreach ($this->source as $id) {
//                sql()->dataEntryInsert('accounts_users_roles', [
//                    'roles_id' => $this->parent->getId(),
//                    'users_id' => $id
//                ]);
//            }
//
//        } elseif ($this->parent instanceof RightInterface) {
//            // Delete the current list
//            sql()->query('DELETE FROM `accounts_users_rights`
//                                WHERE       `accounts_users_rights`.`rights_id` = :rights_id', [
//                ':rights_id' => $this->parent->getId()
//            ]);
//
//            // Add the new list
//            foreach ($this->source as $id) {
//                sql()->dataEntryInsert('accounts_users_rights', [
//                    'rights_id' => $this->parent->getId(),
//                    'users_id'  => $id,
//                    'name'      => $this->parent->getName(),
//                    'seo_name'  => $this->parent->getSeoName()
//                ]);
//            }
//
//            unset($user);
//        }

        return $this;
    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @param string|null $order
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = '', string $key_column = 'id', ?string $order = null): InputSelectInterface
    {
        if (!$value_column) {
            $value_column = 'COALESCE(NULLIF(TRIM(CONCAT_WS(" ", `first_names`, `last_names`)), ""), `nickname`, `username`, `email`, "' . tr('System') . '") AS name';
        }

        return InputSelect::new()
            ->setSourceQuery('SELECT `' . $key_column . '`, ' . $value_column . ' 
                                         FROM  `accounts_users`
                                         WHERE `status` IS NULL ORDER BY `' . Strings::fromReverse($value_column, ' ') . '`')
            ->setName('users_id')
            ->setNone(tr('Select a user'))
            ->setObjectEmpty(tr('No users available'));
    }


    /**
     * Returns Users list object with users for the specified role.
     *
     * Will throw an NotEx
     *
     * @param RoleInterface|Stringable|string $role
     * @return static
     * @throws SqlMultipleResultsException, NotExistsException
     */
    public function loadForRole(RoleInterface|Stringable|string $role): static
    {
        $role  = Role::get($role, 'seo_name');
        $this->getQueryBuilder()
            ->addSelect('`accounts_users`.*')
            ->addJoin('JOIN `accounts_users_roles` ON `accounts_users_roles`.`users_id` = `accounts_users`.`id`')
            ->addWhere('`accounts_users_roles`.`roles_id` = :roles_id', [':roles_id' => $role->getId()])
            ->addWhere('`accounts_users`.`status`   IS NULL');

        return $this->load();
    }
}
