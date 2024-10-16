<?php

/**
 * Class Users
 *
 *
 *
 * @see       DataIterator
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Roles\Interfaces\RoleInterface;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Interfaces\UsersInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataIterator;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Exception\Interfaces\OutOfBoundsExceptionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Img;
use Phoundation\Web\Html\Components\Input\InputSelect;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlDataTableInterface;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlTableInterface;
use Phoundation\Web\Html\Components\Widgets\Badge;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Http\Url;
use Plugins\Medinet\Packages\Package;
use Stringable;


class Users extends DataIterator implements UsersInterface
{
    /**
     * Users class constructor
     */
    public function __construct()
    {
//        $this->setQuery('SELECT    `accounts_users`.`id`,
//                                         TRIM(CONCAT(`first_names`, " ", `last_names`)) AS `name`,
//                                         GROUP_CONCAT(CONCAT(UPPER(LEFT(`accounts_roles`.`name`, 1)), SUBSTRING(`accounts_roles`.`name`, 2)) SEPARATOR ", ") AS `roles`,
//                                         `accounts_users`.`email`,
//                                         `accounts_users`.`status`,
//                                         `accounts_users`.`sign_in_count`,
//                                         `accounts_users`.`created_on`
//                               FROM      `accounts_users`
//                               LEFT JOIN `accounts_users_roles`
//                               ON        `accounts_users_roles`.`users_id` = `accounts_users`.`id`
//                               LEFT JOIN `accounts_roles`
//                               ON        `accounts_roles`.`id` = `accounts_users_roles`.`roles_id`
//                               WHERE     `accounts_users`.`status` IS NULL AND `email` != "guest"
//                               GROUP BY  `accounts_users`.`id`
//                               ORDER BY  `name`');

        parent::__construct();

        $this->columns = [
            'id'            => tr('Id'),
            'profile_image' => tr('Profile image'),
            'email'         => tr('Email'),
            'name'          => tr('Name'),
            'roles'         => tr('Roles'),
            'status'        => tr('Status'),
            'sign_in_count' => tr('Sign-in count'),
            'created_on'    => tr('Created on'),
        ];
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'accounts_users';
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'email';
    }


    /**
     * Set the new users for the current parents to the specified list
     *
     * @param array|null  $list
     * @param string|null $column
     *
     * @return static
     * @throws OutOfBoundsExceptionInterface
     */
    public function setUsers(?array $list, ?string $column = null): static
    {
        $this->ensureParent(tr('save entries'));

        if (is_array($list)) {
            // Convert the list to id's
            $users_list = [];

            foreach ($list as $user) {
                if ($user) {
                    $users_list[] = static::getDefaultContentDataType()::load($user)
                                          ->getId();
                }
            }

            // Get a list of what we have to add and remove to get the same list, and apply
            $diff = Arrays::valueDiff(array_keys($this->source), $users_list);

            foreach ($diff['add'] as $user) {
                $this->add($user, $column);
            }

            foreach ($diff['delete'] as $user) {
                $this->removeKeys($user);
            }
        }

        return $this;
    }


    /**
     * Returns the class for a single DataEntry in this Iterator object
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
    {
        return User::class;
    }


    /**
     * Returns true if the parent has the specified user
     *
     * @param UserInterface $user
     *
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
                ':users_id' => $user->getId(),
            ]);
        }

        // No user? Then it must be a right
        return (bool) sql()->get('SELECT `id` 
                                        FROM   `accounts_users_rights` 
                                        WHERE  `users_id`  = :users_id 
                                        AND    `rights_id` = :rights_id', [
            ':rights_id' => $this->parent->getId(),
            ':users_id'  => $user->getId(),
        ]);
    }


    /**
     * Add the specified user to the data list
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return static
     * @todo Move saving part to ->save(). ->add() should NOT immediately save to database!
     */
    public function append(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        $this->ensureParent(tr('add Role entry to parent ":parent"', [
            ':parent' => $this->parent ? get_class($this->parent) : 'NULL'
        ]));

        if ($value and $this->parent) {
            if (is_array($value)) {
                // Add multiple rights
                foreach ($value as $entry) {
                    $this->append($entry, $key, $skip_null_values);
                }

                return $this;

            } else {
                // Add single right. Since this is a User object, the entry already exists in the database
                $value = User::load($value);

                // User already exists for this parent?
                if ($this->hasUser($value)) {
                    // Ignore and continue
                    return $this;
                }

                // Add entry to parent, Role or Right
                if ($this->parent instanceof RoleInterface) {
                    Log::action(tr('Adding role ":role" to user ":user"', [
                        ':role' => $this->parent->getLogId(),
                        ':user' => $value->getLogId(),
                    ]), 3);

                    sql()->insert('accounts_users_roles', [
                        'roles_id' => $this->parent->getId(),
                        'users_id' => $value->getId(),
                    ]);

                } elseif ($this->parent instanceof RightInterface) {
                    Log::action(tr('Adding right ":right" to user ":user"', [
                        ':right' => $this->parent->getLogId(),
                        ':user'  => $value->getLogId(),
                    ]), 3);

                    sql()->insert('accounts_users_rights', [
                        'rights_id' => $this->parent->getId(),
                        'users_id'  => $value->getId(),
                        'name'      => $this->parent->getName(),
                        'seo_name'  => $this->parent->getSeoName(),
                    ]);
                }
            }
        }

        // Add right to the internal list
        return parent::append($value, $key, $skip_null_values, $exception);
    }


    /**
     * Remove the specified data entry from the data list
     *
     * @param Stringable|array|string|int $keys
     * @param bool                        $strict
     *
     * @return static
     */
    public function removeKeys(Stringable|array|string|int $keys, bool $strict = false): static
    {
        $this->ensureParent(tr('remove entry from parent'));

        if (!$keys) {
            // Nothing to do
            return $this;
        }

        if (is_array($keys)) {
            // Add multiple rights
            foreach ($keys as $key) {
                $this->removeKeys($key);
            }

        } else {
            // Add a single user. Since this is a User object, the entry already exists in the database
            $user = User::load($keys);

            if ($this->parent instanceof RoleInterface) {
                Log::action(tr('Removing user ":user" from role ":role"', [
                    ':role' => $this->parent->getLogId(),
                    ':user' => $user->getLogId(),
                ]), 3);

                sql()->delete('accounts_users_rights', [
                    'roles_id' => $this->parent->getId(),
                    'users_id' => $user->getId(),
                ]);

                // Remove user from the internal list
                parent::removeKeys($user->getUniqueColumnValue());

            } elseif ($this->parent instanceof RightInterface) {
                Log::action(tr('Removing user ":user" from right ":right"', [
                    ':right' => $this->parent->getLogId(),
                    ':user'  => $user->getLogId(),
                ]), 3);

                sql()->delete('accounts_users_rights', [
                    'rights_id' => $this->parent->getId(),
                    'users_id'  => $user->getId(),
                ]);

                // Remove user from the internal list
                parent::removeKeys($user->getUniqueColumnValue());
            }
        }

        return $this;
    }


    /**
     * Remove all rights for this right
     *
     * @return static
     * @todo Move saving part to ->save(). ->clear() should NOT immediately save to database!
     */
    public function clear(): static
    {
        $this->ensureParent(tr('clear all entries from parent'));

        if ($this->parent instanceof RoleInterface) {
            Log::action(tr('Removing role ":role" from all users', [
                ':right' => $this->parent->getLogId(),
            ]));

            sql()->query('DELETE FROM `accounts_users_roles` WHERE `roles_id` = :roles_id', [
                'roles_id' => $this->parent->getId(),
            ]);

        } elseif ($this->parent instanceof RightInterface) {
            Log::action(tr('Removing right ":right" from all users', [
                ':right' => $this->parent->getLogId(),
            ]), 3);

            sql()->query('DELETE FROM `accounts_users_rights` WHERE `rights_id` = :rights_id', [
                'rights_id' => $this->parent->getId(),
            ]);
        }

        return parent::clear();
    }


    /**
     * Save the data for this users list in the database
     *
     * @return static
     * @todo Implement this. ->add(), ->removeKeys(), ->clear() should NOT immediately save to database!
     */
    public function save(): static
    {
//        $this->ensureParent(tr('save parent entries'));
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
//                sql()->insert('accounts_users_roles', [
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
//                sql()->insert('accounts_users_rights', [
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
     * Load the data for this users list
     *
     * @param array|string|null $columns
     * @param array             $filters
     * @param array             $order_by
     *
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
                    ':users_id' => $id,
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
                    ':users_id' => $id,
                ]);

                $item['rights'] = implode(', ', $item['rights']);
            }
        }

        return $return;
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
    public function getHtmlSelect(string $value_column = '', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface
    {
        $select = InputSelect::new()
                             ->setName('users_id')
                             ->setNotSelectedLabel(tr('Select a user'))
                             ->setComponentEmptyLabel(tr('No users available'));

        if ($this->source) {
            return $select->setSource($this->source)
                          ->setValueColumn('name');
        }

        if (!$value_column) {
            $value_column = 'COALESCE(NULLIF(TRIM(CONCAT_WS(" ", `first_names`, `last_names`)), ""), `nickname`, `username`, `email`, "' . tr('System') . '") AS `name`';
        }

        return $select->setConnectorObject($this->getConnectorObject())
                      ->setSourceQuery('SELECT `' . $key_column . '`, ' . $value_column . ' 
                                        FROM  `accounts_users`
                                        WHERE `status` IS NULL ORDER BY ' . Strings::ensureSurroundedWith(Strings::fromReverse($value_column, ' '), '`'));
    }


    /**
     * Returns Users list object with users for the specified role.
     *
     * Will throw an NotEx
     *
     * @param RoleInterface|Stringable|string $role
     *
     * @return static
     * @throws SqlMultipleResultsException, NotExistsException
     */
    public function loadForRole(RoleInterface|Stringable|string $role): static
    {
        $role = Role::load($role);
        $this->getQueryBuilder()
             ->addSelect('`accounts_users`.*')
             ->addJoin('JOIN `accounts_users_roles` ON `accounts_users_roles`.`users_id` = `accounts_users`.`id`')
             ->addWhere('`accounts_users_roles`.`roles_id` = :roles_id', [':roles_id' => $role->getId()])
             ->addWhere('`accounts_users`.`status`   IS NULL');

        return $this->load();
    }


    /**
     * Load the data for this users list into the object
     *
     * @param array|string|int|null $identifiers
     * @param bool                  $clear
     * @param bool                  $only_if_empty
     *
     * @return static
     */
    public function load(array|string|int|null $identifiers = null, bool $clear = true, bool $only_if_empty = false): static
    {
        if (empty($this->query) and empty($this->query_builder)) {
            if ($this->parent) {
                if ($this->parent instanceof RoleInterface) {
                    $this->query   = 'SELECT `accounts_users`.`email` AS `key`, `accounts_users`.* 
                                      FROM   `accounts_users_roles` 
                                      JOIN   `accounts_users` 
                                      ON     `accounts_users_roles`.`users_id` = `accounts_users`.`id`
                                      WHERE  `accounts_users_roles`.`roles_id` = :roles_id';

                    $this->execute = [
                        ':roles_id' => $this->parent->getId(),
                    ];

                } elseif ($this->parent instanceof RightInterface) {
                    $this->query = 'SELECT `accounts_users`.`email` AS `key`, `accounts_users`.* 
                                    FROM   `accounts_users_rights` 
                                    JOIN   `accounts_users` 
                                    ON     `accounts_users_rights`.`users_id`  = `accounts_users`.`id`
                                    WHERE  `accounts_users_rights`.`rights_id` = :rights_id';

                    $this->execute = [
                        ':rights_id' => $this->parent->getId(),
                    ];
                }

            } elseif ($identifiers) {
                $identifiers   = Arrays::force($identifiers);
                $this->execute = SqlQueries::in($identifiers, ':identifiers', true);

                $this->query = 'SELECT `accounts_users`.`email` AS `key`, `accounts_users`.*
                                FROM   `accounts_users`
                                WHERE  `accounts_users`.`id` IN (' . SqlQueries::inColumns($this->execute) . ')';

            } else {
                $this->query = 'SELECT `accounts_users`.`email` AS `key`, `accounts_users`.*
                                FROM   `accounts_users`
                                WHERE  `accounts_users`.`status` IS NULL
                                  AND  `accounts_users`.`email`    != "guest"';
            }
        }

        return parent::load();
    }


    /**
     * Update the status of ALL users in this Users object
     *
     * @param string|null $current_status
     * @return static
     */
    public function lock(?string $current_status = null): static
    {
        foreach ($this->source as $user) {
            if ($user->getStatus() === $current_status) {
                $user->lock();
            }
        }

        return $this;
    }


    /**
     * @inheritDoc
     */
    public function getHtmlTableObject(array|string|null $columns = null): HtmlTableInterface
    {
        return parent::getHtmlTableObject($columns)->addCellCallback($this->getCellCallback());
    }


    /**
     * @inheritDoc
     */
    public function getHtmlDataTableObject(array|string|null $columns = null): HtmlDataTableInterface
    {
        return parent::getHtmlDataTableObject($columns)
                     ->addCellCallback($this->getCellCallback())
                     ->setColumnsOrderable([
                         1 => false,
                         2 => true,
                         3 => true,
                         4 => true,
                         5 => true,
                         6 => true,
                         7 => true
                     ])
                     ->setJsDateFormat('YYYY-MM-DD HH:mm:ss')
                     ->setOrder([2 => 'asc']);
    }


    /**
     * Returns the function used for cell callbacks in the Users table
     *
     * @return callable
     */
    protected function getCellCallback(): callable
    {
        return function (string|float|int|null $row_id, string|float|int|null $column, Stringable|string|float|int|bool|null &$value, IteratorInterface|array &$row, array &$params) {
            switch ($column) {
                case 'status':
                    $params['htmlentities'] = false;

                    switch ($value) {
                        case '':
                            $value = Badge::new()->setLabel(Html::safe('Active'))->setMode(EnumDisplayMode::success);
                            break;

                        case 'locked':
                            $value = Badge::new()->setLabel(Html::safe($value))->setMode(EnumDisplayMode::warning);
                            break;

                        case 'deleted':
                            // no break
                        default:
                            $value = Badge::new()->setLabel(Html::safe($value))->setMode(EnumDisplayMode::danger);
                            break;
                    }

                    break;

                case 'profile_image':
                    $params['htmlentities'] = false;

                    if ($value) {
                        $image = Img::new($value);

                    } else {
                        $image = Img::new('img/profiles/default.png');
                    }

                    // Convert the value into a user profile image tag
                    $value = $image->setClass('img-circle')
                                   ->setWidth(48)
                                   ->setHeight(48)
                                   ->setAlt(tr('Profile picture'));
            }
        };
    }
}
