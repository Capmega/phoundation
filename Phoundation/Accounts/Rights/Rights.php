<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Rights;

use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Roles\Interfaces\RoleInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Exception\Interfaces\OutOfBoundsExceptionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Security\Incidents\Severity;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\InputSelect;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Stringable;

/**
 * Class Rights
 *
 *
 *
 * @see       DataList
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */
class Rights extends DataList implements RightsInterface
{
    /**
     * Roles class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT     `accounts_rights`.`id`, 
                                          CONCAT(UPPER(LEFT(`accounts_rights`.`name`, 1)), SUBSTRING(`accounts_rights`.`name`, 2)) AS `role`, 
                                          GROUP_CONCAT(CONCAT(UPPER(LEFT(`accounts_roles`.`name`, 1)), SUBSTRING(`accounts_roles`.`name`, 2)) SEPARATOR ", ") AS `Linked roles`, 
                                          `accounts_rights`.`description` 
                               FROM       `accounts_rights` 
                               LEFT JOIN  `accounts_roles_rights`
                               ON         `accounts_roles_rights`.`rights_id` = `accounts_rights`.`id` 
                               LEFT JOIN  `accounts_roles`
                               ON         `accounts_roles`.`id` = `accounts_roles_rights`.`roles_id` 
                                 AND      `accounts_roles`.`status` IS NULL 
                               WHERE      `accounts_rights`.`status` IS NULL
                               GROUP BY   `accounts_rights`.`name`
                               ORDER BY   `accounts_rights`.`name`');
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
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'seo_name';
    }


    /**
     * Checks the list of specified rights if they exist and returns those rights that do not exist.
     *
     * @param array|string $rights
     *
     * @return array
     */
    public static function getNotExist(array|string $rights): array
    {
        $rights = Arrays::force($rights);
        $values = SqlQueries::in($rights);
        $rights = array_flip($rights);
        $exist = sql()->query('SELECT `seo_name` 
                                      FROM   `accounts_rights` 
                                      WHERE  `seo_name` IN (' . implode(', ', array_keys($values)) . ')', $values);
        while ($right = $exist->fetchColumn(0)) {
            unset($rights[$right]);
        }

        return array_flip($rights);
    }


    /**
     * Ensure that the specified rights exist
     *
     * @param array $rights
     *
     * @return void
     */
    public static function ensure(array $rights): void
    {
        // Save each right in this list if it doesn't exist
        foreach ($rights as $right) {
            if (is_numeric($right)) {
                // This is an ID, not a name. Right names can NOT be numeric
                throw new OutOfBoundsException(tr('Cannot add right ":right", it is numeric. Right names must not be numeric', [
                    ':right' => $right,
                ]));
            }
            if (!is_string($right)) {
                // Who dis?
                throw new OutOfBoundsException(tr('Cannot add right ":right", it is not a string. Right names must be a string', [
                    ':right' => $right,
                ]));
            }
            if (Right::notExists($right, 'name')) {
                Right::new()
                     ->setName($right)
                     ->save();
                Incident::new()
                        ->setSeverity(Severity::medium)
                        ->setType('Right created automatically')
                        ->setTitle(tr('Automatically created new right ":right"', [':right' => $right]))
                        ->setDetails(['right' => $right])
                        ->notifyRoles('accounts')
                        ->save();
            }
        }
    }


    /**
     * Save the data for this rights list in the database
     *
     * @return static
     */
    public function save(): static
    {
//        $this->ensureParent(tr('save parent entries'));
//
//        if ($this->parent instanceof UserInterface) {
//            // Delete the current list
//            sql()->query('DELETE FROM `accounts_users_rights`
//                                WHERE       `accounts_users_rights`.`users_id` = :users_id', [
//                ':users_id' => $this->parent->getId()
//            ]);
//
//            // Add the new list
//            foreach ($this->source as $id) {
//                $right = new Right($id);
//
//                sql()->insert('accounts_users_rights', [
//                    'users_id'  => $this->parent->getId(),
//                    'rights_id' => $id,
//                    'name'      => $right->getName(),
//                    'seo_name'  => $right->getSeoName()
//                ]);
//            }
//
//        } elseif ($this->parent instanceof RoleInterface) {
//            // Delete the current list
//            sql()->query('DELETE FROM `accounts_roles_rights`
//                                WHERE       `accounts_roles_rights`.`roles_id` = :roles_id', [
//                ':roles_id' => $this->parent->getId()
//            ]);
//
//            // Add the new list
//            foreach ($this->source as $id) {
//                sql()->insert('accounts_roles_rights', [
//                    'roles_id'  => $this->parent->getId(),
//                    'rights_id' => $id
//                ]);
//            }
//        }
        return $this;
    }


    /**
     * Set the new rights for the current parents to the specified list
     *
     * @param array|null  $list
     * @param string|null $column
     *
     * @return static
     */
    public function setRights(?array $list, ?string $column = null): static
    {
        $this->ensureParent(tr('save entries'));
        if (is_array($list)) {
            // Convert the list with whatever is specified (id, seo_name, role object) to seo_names
            $rights_list = [];
            foreach ($list as $right) {
                if ($right) {
                    $rights_list[] = static::getEntryClass()::get($right)
                                           ->getSeoName();
                }
            }
            // Get a list of what we have to add and remove to get the same list, and apply
            $diff = Arrays::valueDiff(array_keys($this->source), $rights_list);
            foreach ($diff['add'] as $right) {
                $this->add($right, $column);
            }
            foreach ($diff['delete'] as $right) {
                $this->deleteKeys($right);
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
        return Right::class;
    }


    /**
     * Add the specified data entry to the data list
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param bool                             $skip_null
     * @param bool                             $exception
     *
     * @return static
     * @throws OutOfBoundsExceptionInterface
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true, bool $exception = true): static
    {
        $this->ensureParent(tr('add Right entry to parent'));
        if ($value) {
            if (is_array($value)) {
                // Add multiple rights
                foreach ($value as $entry) {
                    $this->add($entry, $key, $skip_null);
                }

            } else {
                // Add single right. Since this is a Right object, the entry already exists in the database
                $value = Right::get($value);
                // Right already exists for this parent?
                if ($this->hasRight($value)) {
                    // Ignore and continue
                    return $this;
                }
                // Add entry to parent, User or Role
                if ($this->parent instanceof UserInterface) {
                    Log::action(tr('Adding right ":right" to user ":user"', [
                        ':user'  => $this->parent->getLogId(),
                        ':right' => $value->getLogId(),
                    ]), 3);
                    sql()->insert('accounts_users_rights', [
                        'users_id'  => $this->parent->getId(),
                        'rights_id' => $value->getId(),
                        'name'      => $value->getName(),
                        'seo_name'  => $value->getSeoName(),
                    ]);
                    // Add right to the internal list
                    $this->add($value);

                } elseif ($this->parent instanceof RoleInterface) {
                    Log::action(tr('Adding right ":right" to role ":role"', [
                        ':role'  => $this->parent->getLogId(),
                        ':right' => $value->getLogId(),
                    ]), 3);
                    sql()->insert('accounts_roles_rights', [
                        'roles_id'  => $this->parent->getId(),
                        'rights_id' => $value->getId(),
                    ]);
                    // Add right to the internal list
                    $this->add($value);
                    // Update all users with this role to get the new right as well!
                    foreach ($this->parent->getUsers() as $user) {
                        User::get($user)
                            ->getRights()
                            ->add($value);
                    }
                }
            }
        }

        return $this;
    }


    /**
     * Returns true if the parent has the specified right
     *
     * @param RightInterface $right
     *
     * @return bool
     */
    public function hasRight(RightInterface $right): bool
    {
        if (!$this->parent) {
            throw OutOfBoundsException::new('Cannot check if parent has the specified right, this rights list has no parent specified');
        }
        if ($this->parent instanceof UserInterface) {
            return (bool) sql()->get('SELECT `id` 
                                            FROM   `accounts_users_rights` 
                                            WHERE  `users_id`  = :users_id 
                                            AND    `rights_id` = :rights_id', [
                ':users_id'  => $this->parent->getId(),
                ':rights_id' => $right->getId(),
            ]);
        }

        // No user? Then it must be a role
        return (bool) sql()->get('SELECT `id` 
                                        FROM   `accounts_roles_rights` 
                                        WHERE  `roles_id`  = :roles_id 
                                        AND    `rights_id` = :rights_id', [
            ':roles_id'  => $this->parent->getId(),
            ':rights_id' => $right->getId(),
        ]);
    }


    /**
     * Remove the specified data entry from the data list
     *
     * @param RightInterface|Stringable|array|string|float|int $keys
     *
     * @return static
     */
    public function deleteKeys(RightInterface|Stringable|array|string|float|int $keys): static
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
            // Add single right. Since this is a Right object, the entry already exists in the database
            $right = Right::get($keys);
            if ($this->parent instanceof UserInterface) {
                Log::action(tr('Removing right ":right" from user ":user"', [
                    ':user'  => $this->parent->getLogId(),
                    ':right' => $right->getLogId(),
                ]), 3);
                sql()->delete('accounts_users_rights', [
                    'users_id'  => $this->parent->getId(),
                    'rights_id' => $right->getId(),
                ]);
                // Delete right from the internal list
                $this->removeKeys($right->getId());

            } elseif ($this->parent instanceof RoleInterface) {
                Log::action(tr('Removing right ":right" from role ":role"', [
                    ':role'  => $this->parent->getLogId(),
                    ':right' => $right->getLogId(),
                ]), 3);
                sql()->delete('accounts_roles_rights', [
                    'roles_id'  => $this->parent->getId(),
                    'rights_id' => $right->getId(),
                ]);
                // Update all users with this role to get the new right as well!
                foreach ($this->parent->getUsers() as $user) {
                    User::get($user, null)
                        ->getRights()
                        ->deleteKeys($right);
                }
                // Delete right from the internal list
                $this->removeKeys($right->getId());
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
        $this->ensureParent(tr('clear all entries from parent'));
        if ($this->parent instanceof UserInterface) {
            Log::action(tr('Removing all rights from user ":user"', [
                ':user' => $this->parent->getLogId(),
            ]));
            sql()->query('DELETE FROM `accounts_users_rights` WHERE `users_id` = :users_id', [
                'users_id' => $this->parent->getId(),
            ]);

        } elseif ($this->parent instanceof RoleInterface) {
            Log::action(tr('Removing all rights from role ":role"', [
                ':role' => $this->parent->getLogId(),
            ]));
            sql()->query('DELETE FROM `accounts_roles_rights` WHERE `roles_id` = :roles_id', [
                'roles_id' => $this->parent->getId(),
            ]);
        }

        return parent::clear();
    }


    /**
     * Load the data for this rights list into the object
     *
     * @param bool $clear
     *
     * @return static
     */
    public function load(bool $clear = true, bool $only_if_empty = false): static
    {
        if ($this->parent) {
            // Load only rights for specified parent
            if ($this->parent instanceof UserInterface) {
                $this->source = sql()->list('SELECT   `accounts_rights`.`seo_name` AS `key`, 
                                                            `accounts_rights`.*,
                                                            CONCAT(UPPER(LEFT(`accounts_rights`.`name`, 1)), SUBSTRING(`accounts_rights`.`name`, 2)) AS `name`
                                                   FROM     `accounts_users_rights` 
                                                   JOIN     `accounts_rights` 
                                                   ON       `accounts_users_rights`.`rights_id` = `accounts_rights`.`id`
                                                   WHERE    `accounts_users_rights`.`users_id`  = :users_id
                                                   ORDER BY `accounts_rights`.`name` ASC', [
                    ':users_id' => $this->parent->getId(),
                ]);
                // Added the "everybody" right
                $this->source['everybody'] = [
                    'id'          => 0,
                    'seo_name'    => 'everybody',
                    'name'        => 'everybody',
                    'description' => tr('This is a default right that applies to all users'),
                ];

            } elseif ($this->parent instanceof RoleInterface) {
                $this->source = sql()->list('SELECT   `accounts_rights`.`seo_name` AS `key`, 
                                                            `accounts_rights`.*,
                                                            CONCAT(UPPER(LEFT(`accounts_rights`.`name`, 1)), SUBSTRING(`accounts_rights`.`name`, 2)) AS `name`
                                                   FROM     `accounts_roles_rights` 
                                                   JOIN     `accounts_rights` 
                                                   ON       `accounts_roles_rights`.`rights_id` = `accounts_rights`.`id`
                                                   WHERE    `accounts_roles_rights`.`roles_id`  = :roles_id
                                                   ORDER BY `accounts_rights`.`name` ASC', [
                    ':roles_id' => $this->parent->getId(),
                ]);

            }

        } else {
            // Load all
            $this->source = sql()->list('SELECT `id` FROM `accounts_rights`');
        }

        return $this;
    }


    /**
     * Load the data for this right list
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
        foreach ($filters as $key => $value) {
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
                    ':rights_id' => $id,
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
                    ':rights_id' => $id,
                ]);
                $item['roles'] = implode(', ', $item['roles']);
            }
            unset($item);
        }

        return $return;
    }


    /**
     * Returns a select with the available rights
     *
     * @return InputSelect
     */
    public function getHtmlSelect(string $value_column = 'CONCAT(UPPER(LEFT(`name`, 1)), SUBSTRING(`name`, 2)) AS `name`', ?string $key_column = 'id', ?string $order = '`name` ASC', ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column, $order, $joins, $filters)
                     ->setName('rights_id')
                     ->setNone(tr('Select a right'))
                     ->setObjectEmpty(tr('No rights available'));
    }
}
