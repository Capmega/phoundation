<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Roles;

use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Roles\Interfaces\RoleInterface;
use Phoundation\Accounts\Roles\Interfaces\RolesInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Exception\Interfaces\OutOfBoundsExceptionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\InputSelectInterface;
use Stringable;


/**
 * Class Roles
 *
 *
 *
 * @see DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Roles extends DataList implements RolesInterface
{
    /**
     * Roles class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT     `accounts_roles`.`id`, 
                                          CONCAT(UPPER(LEFT(`accounts_roles`.`name`, 1)), SUBSTRING(`accounts_roles`.`name`, 2)) AS `role`, 
                                          GROUP_CONCAT(CONCAT(UPPER(LEFT(`accounts_rights`.`name`, 1)), SUBSTRING(`accounts_rights`.`name`, 2)) SEPARATOR ", ") AS `rights`, 
                                          `accounts_roles`.`description` 
                               FROM       `accounts_roles` 
                               LEFT JOIN  `accounts_roles_rights`
                               ON         `accounts_roles_rights`.`roles_id` = `accounts_roles`.`id` 
                               LEFT JOIN  `accounts_rights`
                               ON         `accounts_rights`.`id` = `accounts_roles_rights`.`rights_id` 
                                 AND      `accounts_rights`.`status` IS NULL 
                               WHERE      `accounts_roles`.`status` IS NULL
                               GROUP BY   `accounts_roles`.`name`
                               ORDER BY   `accounts_roles`.`name`');

        parent::__construct();
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'accounts_roles';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return Role::class;
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
     * Set the new roles for the current parents to the specified list
     *
     * @param array|null $list
     * @return static
     */
    public function setRoles(?array $list): static
    {
        $this->ensureParent('save entries');

        if (is_array($list)) {
            // Convert the list with whatever is specified (id, seo_name, role object) to seo_names
            $roles_list  = [];

            foreach ($list as $role) {
                if ($role) {
                    $roles_list[] = static::getEntryClass()::get($role)->getSeoName();
                }
            }

            // Get a list of what we have to add and remove to get the same list, and apply
            $diff = Arrays::valueDiff(array_keys($this->source), $roles_list);

            foreach ($diff['add'] as $role) {
                $this->addRole($role);
            }

            foreach ($diff['delete'] as $role) {
                $this->deleteKeys($role);
            }
        }

        return $this;
    }


    /**
     * Add the specified role to the data list
     *
     * @param RoleInterface|array|string|int|null $role
     * @param string|null $column
     * @return static
     * @throws OutOfBoundsExceptionInterface
     */
    public function addRole(RoleInterface|array|string|int|null $role, ?string $column = null): static
    {
        $this->ensureParent('add entry to parent');

        if ($role) {
            if (is_array($role)) {
                // Add multiple rights
                foreach ($role as $entry) {
                    $this->addRole($entry, $column);
                }

            } else {
                // Add single right. Since this is a Role object, the entry already exists in the database
                $role = Role::get($role, $column);

                // Role already exists for this parent?
                if ($this->hasRole($role)) {
                    // Ignore and continue
                    return $this;
                }

                // Add entry to parent, User or Right
                if ($this->parent instanceof UserInterface) {
                    Log::action(tr('Adding role ":role" to user ":user"', [
                        ':user' => $this->parent->getLogId(),
                        ':role' => $role->getLogId()
                    ]));

                    sql()->dataEntryInsert('accounts_users_roles', [
                        'users_id' => $this->parent->getId(),
                        'roles_id' => $role->getId()
                    ]);

                    // Add right to internal list
                    $this->addDataEntry($role);

                    // Add rights to the user
                    foreach ($role->getRights() as $right) {
                        $this->parent->getRights()->addRight($right);
                    }

                } elseif ($this->parent instanceof RightInterface) {
                    Log::action(tr('Adding right ":right" to role ":role"', [
                        ':right' => $this->parent->getLogId(),
                        ':role'  => $role->getLogId()
                    ]));

                    sql()->dataEntryInsert('accounts_roles_rights', [
                        'rights_id' => $this->parent->getId(),
                        'roles_id'  => $role->getId()
                    ]);

                    // Add right to internal list
                    $this->addDataEntry($role);

                    // Update all users with this right to get the new right as well!
                    foreach ($this->parent->getUsers() as $user) {
                        User::get($user)->getRights()->addRight($this->parent);
                    }
                }
            }
        }

        return $this;
    }


    /**
     * Remove the specified role from the roles list
     *
     * @param RoleInterface|Stringable|array|string|float|int $role
     * @return static
     */
    public function deleteKeys(RoleInterface|Stringable|array|string|float|int $role): static
    {
        $this->ensureParent('remove entry from parent');

        if ($role) {
            if (is_array($role)) {
                // Add multiple rights
                foreach ($role as $entry) {
                    $this->deleteKeys($entry);
                }

            } else {
                // Add single right. Since this is a Role object, the entry already exists in the database
                $role = Role::get($role);

                if ($this->parent instanceof UserInterface) {
                    Log::action(tr('Removing role ":role" from user ":user"', [
                        ':user' => $this->parent->getLogId(),
                        ':role' => $role->getLogId()
                    ]));

                    sql()->dataEntryDelete('accounts_users_roles', [
                        'users_id' => $this->parent->getId(),
                        'roles_id' => $role->getId()
                    ]);

                    // Delete role from internal list
                    parent::deleteAll($role->getId());

                    // Remove the rights related to this role
                    foreach ($role->getRights() as $right) {
                        // Ensure this right isn't also given by another role
                        foreach ($right->getRoles() as $check_role) {
                            if ($this->hasRole($check_role)) {
                                // Don't remove this right, another role gives it too.
                                continue 2;
                            }
                        }

                        $this->parent->getRights()->deleteKeys($right);
                    }

                } elseif ($this->parent instanceof RightInterface) {
                    Log::action(tr('Removing role ":role" from right ":right"', [
                        ':right' => $this->parent->getLogId(),
                        ':role'  => $role->getLogId()
                    ]));

                    sql()->dataEntryDelete('accounts_roles_rights', [
                        'rights_id' => $this->parent->getId(),
                        'roles_id'  => $role->getId()
                    ]);

                    // Remove right from internal list
                    parent::deleteAll($role->getId());

                    // Update all users with this right to remove the new right as well!
                    foreach ($this->parent->getUsers() as $user) {
                        User::get($user)->getRights()->deleteKeys($this->parent);
                    }
                }
            }
        }

        return $this;
    }


    /**
     * Returns true if the parent has the specified role
     *
     * @param RoleInterface $role
     * @return bool
     */
    public function hasRole(RoleInterface $role): bool
    {
        if (!$this->parent) {
            throw OutOfBoundsException::new('Cannot check if parent has the specified role, this roles list has no parent specified');
        }

        if ($this->parent instanceof UserInterface) {
            return (bool) sql()->get('SELECT `id` 
                                            FROM   `accounts_users_roles` 
                                            WHERE  `users_id`  = :users_id 
                                            AND    `roles_id` = :roles_id', [
                ':users_id' => $this->parent->getId(),
                ':roles_id' => $role->getId()
            ]);
        }

        // No user? Then it must be a right
        return (bool) sql()->get('SELECT `id` 
                                        FROM   `accounts_roles_rights` 
                                        WHERE  `roles_id`  = :roles_id 
                                        AND    `rights_id` = :rights_id', [
            ':rights_id' => $this->parent->getId(),
            ':roles_id'  => $role->getId()
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
            if ($this->parent instanceof UserInterface) {
                $this->source = sql()->list('SELECT `accounts_roles`.`seo_name` AS `key`, 
                                                          `accounts_roles`.*,
                                                          CONCAT(UPPER(LEFT(`accounts_roles`.`name`, 1)), SUBSTRING(`accounts_roles`.`name`, 2)) AS `name`
                                                   FROM   `accounts_users_roles` 
                                                   JOIN   `accounts_roles` 
                                                   ON     `accounts_users_roles`.`roles_id`  = `accounts_roles`.`id`
                                                   WHERE  `accounts_users_roles`.`users_id` = :users_id', [
                    ':users_id' => $this->parent->getId()
                ]);

            } elseif ($this->parent instanceof RightInterface) {
                $this->source = sql()->list('SELECT `accounts_roles`.`seo_name` AS `key`, 
                                                          `accounts_roles`.*, 
                                                          CONCAT(UPPER(LEFT(`accounts_roles`.`name`, 1)), SUBSTRING(`accounts_roles`.`name`, 2)) AS `name`
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
            $builder->addOrderBy('`' . $column . '` ' . ($direction ? 'DESC' : 'ASC'));
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
//        $this->ensureParent('save parent entries');
//
//        if ($this->parent instanceof UserInterface) {
//            // Delete the current list
//            sql()->query('DELETE FROM `accounts_users_roles`
//                                WHERE       `accounts_users_roles`.`users_id` = :users_id', [
//                ':users_id' => $this->parent->getId()
//            ]);
//
//            // Add the new list
//            sql()->query('DELETE FROM `accounts_users_roles`
//                                WHERE       `accounts_users_roles`.`users_id` = :users_id', [
//                ':users_id' => $this->parent->getId()
//            ]);
//
//        } elseif ($this->parent instanceof RightInterface) {
//            // Delete the current list
//            sql()->query('DELETE FROM `accounts_roles_rights`
//                                WHERE       `accounts_roles_rights`.`rights_id` = :rights_id', [
//                ':rights_id' => $this->parent->getId()
//            ]);
//
//            // Add the new list
//            sql()->query('DELETE FROM `accounts_roles_rights`
//                                WHERE       `accounts_roles_rights`.`rights_id` = :rights_id', [
//                ':rights_id' => $this->parent->getId()
//            ]);
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
    public function getHtmlSelect(string $value_column = 'CONCAT(UPPER(LEFT(`name`, 1)), SUBSTRING(`name`, 2)) AS `name`', string $key_column = 'id', ?string $order = '`name` ASC'): InputSelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column, $order)
            ->setName('roles_id')
            ->setNone(tr('Select a role'))
            ->setObjectEmpty(tr('No roles available'));
    }
}
