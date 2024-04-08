<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Roles;

use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Roles\Interfaces\RoleInterface;
use Phoundation\Accounts\Roles\Interfaces\RolesInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Exception\Interfaces\OutOfBoundsExceptionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Stringable;

/**
 * Class Roles
 *
 *
 *
 * @see       DataList
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */
class Roles extends DataList implements RolesInterface
{
    /**
     * Roles class constructor
     */
    public function __construct()
    {
        $this->setKeysareUniqueColumn(true)
             ->setQuery('SELECT     `accounts_roles`.`id`, 
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
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'seo_name';
    }


    /**
     * Set the new roles for the current parents to the specified list
     *
     * @param array|null  $list
     * @param string|null $column
     *
     * @return static
     */
    public function setRoles(?array $list, ?string $column = null): static
    {
        $this->ensureParent(tr('save entries'));
        if (is_array($list)) {
            // Convert the list with whatever is specified (id, seo_name, role object) to seo_names
            $roles_list = [];
            foreach ($list as $role) {
                if ($role) {
                    $roles_list[] = static::getEntryClass()::get($role)
                                          ->getSeoName();
                }
            }
            // Get a list of what we have to add and remove to get the same list, and apply
            $diff = Arrays::valueDiff(array_keys($this->source), $roles_list);
            foreach ($diff['add'] as $role) {
                $this->add($role, $column);
            }
            foreach ($diff['delete'] as $role) {
                $this->deleteKeys($role);
            }
        }

        return $this;
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
     * Add the specified role to the data list
     *
     * @param RoleInterface|array|string|int|null $value
     * @param Stringable|string|float|int|null    $key
     * @param bool                                $skip_null
     * @param bool                                $exception
     *
     * @return static
     * @throws OutOfBoundsExceptionInterface
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true, bool $exception = true): static
    {
        $this->ensureParent(tr('add Role entry to parent'));
        if ($value) {
            if (is_array($value)) {
                // Add multiple rights
                foreach ($value as $entry) {
                    $this->add($entry, $key, $skip_null);
                }

            } else {
                // Add single right. Since this is a Role object, the entry already exists in the database
                $value = Role::get($value);
                // Role already exists for this parent?
                if ($this->hasRole($value)) {
                    // Ignore and continue
                    return $this;
                }
                // Add entry to parent, User or Right
                if ($this->parent instanceof UserInterface) {
                    Log::action(tr('Adding role ":role" to user ":user"', [
                        ':user' => $this->parent->getLogId(),
                        ':role' => $value->getLogId(),
                    ]), 3);
                    sql()->insert('accounts_users_roles', [
                        'users_id' => $this->parent->getId(),
                        'roles_id' => $value->getId(),
                    ]);
                    // Add right to the internal list
                    $this->add($value);
                    // Add rights to the user
                    foreach ($value->getRights() as $right) {
                        $this->parent->getRights()
                                     ->add($right);
                    }

                } elseif ($this->parent instanceof RightInterface) {
                    Log::action(tr('Adding right ":right" to role ":role"', [
                        ':right' => $this->parent->getLogId(),
                        ':role'  => $value->getLogId(),
                    ]), 3);
                    sql()->insert('accounts_roles_rights', [
                        'rights_id' => $this->parent->getId(),
                        'roles_id'  => $value->getId(),
                    ]);
                    // Add right to the internal list
                    $this->add($value);
                    // Update all users with this right to get the new right as well!
                    foreach ($this->parent->getUsers() as $user) {
                        User::get($user)
                            ->getRights()
                            ->add($this->parent);
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
     *
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
                ':roles_id' => $role->getId(),
            ]);
        }

        // No user? Then it must be a right
        return (bool) sql()->get('SELECT `id` 
                                        FROM   `accounts_roles_rights` 
                                        WHERE  `roles_id`  = :roles_id 
                                        AND    `rights_id` = :rights_id', [
            ':rights_id' => $this->parent->getId(),
            ':roles_id'  => $role->getId(),
        ]);
    }


    /**
     * Remove the specified role from the roles list
     *
     * @param RoleInterface|Stringable|array|string|float|int $keys
     *
     * @return static
     */
    public function deleteKeys(RoleInterface|Stringable|array|string|float|int $keys): static
    {
        $this->ensureParent(tr('remove entry from parent'));
        if (!$keys) {
            // Nothing to do
            return $this;
        }
        if (is_array($keys)) {
            // Add multiple rights
            foreach ($keys as $key) {
                $this->deleteKeys($key);
            }

        } else {
            // Delete a single role. Since this is a Role object, the entry already exists in the database
            $right = Role::get($keys);
            if ($this->parent instanceof UserInterface) {
                Log::action(tr('Removing role ":role" from user ":user"', [
                    ':user' => $this->parent->getLogId(),
                    ':role' => $right->getLogId(),
                ]));
                sql()->delete('accounts_users_roles', [
                    'users_id' => $this->parent->getId(),
                    'roles_id' => $right->getId(),
                ]);
                // Delete this role from the internal list
                $this->removeKeys($right->getId());
                // Remove the rights related to this role
                foreach ($right->getRights() as $right) {
                    // Ensure this right isn't also given by another role
                    foreach ($right->getRoles() as $check_role) {
                        if ($this->hasRole($check_role)) {
                            // Don't remove this right, another role gives it too.
                            continue 2;
                        }
                    }
                    $this->parent->getRights()
                                 ->deleteKeys($right);
                }

            } elseif ($this->parent instanceof RightInterface) {
                Log::action(tr('Removing role ":role" from right ":right"', [
                    ':right' => $this->parent->getLogId(),
                    ':role'  => $right->getLogId(),
                ]), 3);
                sql()->delete('accounts_roles_rights', [
                    'rights_id' => $this->parent->getId(),
                    'roles_id'  => $right->getId(),
                ]);
                // Remove right from the internal list
                $this->removeKeys($right->getId());
                // Update all users with this right to remove the new right as well!
                foreach ($this->parent->getUsers() as $user) {
                    User::get($user, null)
                        ->getRights()
                        ->deleteKeys($this->parent);
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
        $this->ensureParent(tr('clear all entries from parent'));
        if ($this->parent instanceof UserInterface) {
            Log::action(tr('Removing all roles from user ":user"', [
                ':user' => $this->parent->getLogId(),
            ]));
            sql()->query('DELETE FROM `accounts_users_roles` WHERE `users_id` = :users_id', [
                'users_id' => $this->parent->getId(),
            ]);

        } elseif ($this->parent instanceof RightInterface) {
            Log::action(tr('Removing right ":right" from all roles', [
                ':right' => $this->parent->getLogId(),
            ]), 3);
            sql()->query('DELETE FROM `accounts_roles_rights` WHERE `rights_id` = :rights_id', [
                'rights_id' => $this->parent->getId(),
            ]);
        }

        return parent::clear();
    }


    /**
     * Load the data for this roles list into the object
     *
     * @param bool $clear
     *
     * @return static
     */
    public function load(bool $clear = true, bool $only_if_empty = false): static
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
                    ':users_id' => $this->parent->getId(),
                ]);

            } elseif ($this->parent instanceof RightInterface) {
                $this->source = sql()->list('SELECT `accounts_roles`.`seo_name` AS `key`, 
                                                          `accounts_roles`.*, 
                                                          CONCAT(UPPER(LEFT(`accounts_roles`.`name`, 1)), SUBSTRING(`accounts_roles`.`name`, 2)) AS `name`
                                                   FROM   `accounts_roles_rights` 
                                                   JOIN   `accounts_roles` 
                                                   ON     `accounts_roles_rights`.`roles_id`  = `accounts_roles`.`id`
                                                   WHERE  `accounts_roles_rights`.`rights_id` = :rights_id', [
                    ':rights_id' => $this->parent->getId(),
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
     * @param array             $filters
     *
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
        $users   = Arrays::replaceIfExists($columns, 'users', '1 AS `users`');
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
        foreach ($filters as $key => $value) {
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
                    ':roles_id' => $id,
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
                    ':roles_id' => $id,
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
//        $this->ensureParent(tr('save parent entries'));
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
     * @param string      $value_column
     * @param string|null $key_column
     * @param string|null $order
     * @param array|null  $joins
     * @param array|null  $filters
     *
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'CONCAT(UPPER(LEFT(`name`, 1)), SUBSTRING(`name`, 2)) AS `name`', ?string $key_column = 'id', ?string $order = '`name` ASC', ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column, $order, $joins, $filters)
                     ->setName('roles_id')
                     ->setNone(tr('Select a role'))
                     ->setObjectEmpty(tr('No roles available'));
    }
}
