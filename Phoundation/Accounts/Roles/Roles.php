<?php

/**
 * Class Roles
 *
 *
 *
 * @see       DataIterator
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Roles;

use PDOStatement;
use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Roles\Interfaces\RoleInterface;
use Phoundation\Accounts\Roles\Interfaces\RolesInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataIterator;
use Phoundation\Data\DataEntries\Exception\DataEntryInvalidParentException;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Stringable;


class Roles extends DataIterator implements RolesInterface
{
    /**
     * Roles class constructor
     */
    public function __construct(IteratorInterface|array|string|PDOStatement|null $source = null)
    {
        parent::__construct($source);

        $this->getQueryBuilderObject()->addSelect('`accounts_roles`.`id`, 
                                                   `accounts_roles`.`seo_name`, 
                                                   `accounts_roles`.`description`,
                                                   CONCAT(
                                                      UPPER(LEFT(`accounts_roles`.`name`, 1)), 
                                                      SUBSTRING(`accounts_roles`.`name`, 2)
                                                   ) AS `role`, 
                                                   GROUP_CONCAT(
                                                      CONCAT(UPPER(LEFT(`accounts_rights`.`name`, 1)), 
                                                      SUBSTRING(`accounts_rights`.`name`, 2)) 
                                                      ORDER BY `accounts_rights`.`name` ASC
                                                      SEPARATOR ", " 
                                                   ) AS `rights`')
                                      ->addJoin('JOIN `accounts_roles_rights`
                                                 ON   `accounts_roles_rights`.`roles_id` = `accounts_roles`.`id`')
                                      ->addJoin('JOIN `accounts_rights`
                                                 ON   `accounts_rights`.`id`             = `accounts_roles_rights`.`rights_id`')
                                      ->addWhere('(`accounts_roles`.`status` IS NULL OR `accounts_roles`.`status` != "deleted")')
                                      ->addGroupBy('`accounts_roles`.`id`')
                                      ->addOrderBy('`accounts_roles`.`name`');
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
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
     * Returns the class for a single DataEntry in this Iterator object
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
    {
        return Role::class;
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
        $this->checkParent(tr('save entries'));

        if (is_array($list)) {
            // Convert the list with whatever is specified (id, seo_name, role object) to seo_names
            $roles_list = [];

            foreach ($list as $role) {
                if ($role) {
                    $roles_list[] = Role::new()
                                        ->load($role)
                                        ->getSeoName();
                }
            }

            // Get a list of what we have to add and remove to get the same list, and apply
            $diff = Arrays::valueDiff(array_keys($this->source), $roles_list);

            foreach ($diff['add'] as $role) {
                $this->add($role, $column);
            }

            foreach ($diff['delete'] as $role) {
                $this->removeKeys($role);
            }

            // Add meta-information for parent
            $this->o_parent->addMetaAction('Updated rights', data: $diff);
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
        if (!$this->o_parent) {
            throw OutOfBoundsException::new('Cannot check if parent has the specified role, this roles list has no parent specified');
        }

        if ($this->o_parent instanceof UserInterface) {
            return (bool) sql()->getRow('SELECT `id`
                                      FROM   `accounts_users_roles`
                                      WHERE  `users_id`  = :users_id
                                      AND    `roles_id` = :roles_id', [
                ':users_id' => $this->o_parent->getId(),
                ':roles_id' => $role->getId(),
            ]);
        }

        // No user? Then it must be a right
        return (bool) sql()->getRow('SELECT `id`
                                  FROM   `accounts_roles_rights`
                                  WHERE  `roles_id`  = :roles_id
                                  AND    `rights_id` = :rights_id', [
            ':rights_id' => $this->o_parent->getId(),
            ':roles_id'  => $role->getId(),
        ]);
    }


    /**
     * Add the specified role to the data list
     *
     * @param RoleInterface|array|string|int|null $value
     * @param Stringable|string|float|int|null    $key
     * @param bool                                $skip_null_values
     * @param bool                                $exception
     *
     * @return static
     *
     * @throws OutOfBoundsException
     * @todo Move saving part to ->save(). ->add() should NOT immediately save to database!
     */
    public function append(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        $this->checkParent(tr('add Role entry to parent ":parent"', [
            ':parent' => $this->o_parent ? get_class($this->o_parent) : 'NULL'
        ]));

        if (empty($value)) {
            // Ignore empty values
            return $this;
        }

        if ($value instanceof IteratorInterface) {
            // Get source array
            $value = $value->getSource();
        }

        if (is_array($value)) {
            // Add multiple roles
            foreach ($value as $role) {
                $this->append($role, $key, $skip_null_values);
            }

            return $this;
        }

        // Make sure we have a Role object
        $value = Role::new($value);

        // Role already exists for this parent?
        if ($this->hasRole($value)) {
            // Ignore and continue
            return $this;
        }

        // Add the specified role to this roles list
        parent::append($value, $key, $skip_null_values, $exception);

        if ($this->o_parent) {
            // Add entry to parent, User or Right
            if ($this->o_parent instanceof UserInterface) {
                Log::action(ts('Adding role ":role" to user ":user"', [
                    ':user' => $this->o_parent->getLogId(),
                    ':role' => $value->getLogId(),
                ]), 3);

                sql()->insert('accounts_users_roles', [
                    'users_id' => $this->o_parent->getId(),
                    'roles_id' => $value->getId(),
                ]);

                // This role has rights, so add the rights for this role to the user
                foreach ($value->getRightsObject() as $right) {
                    $this->o_parent->getRightsObject()
                                   ->add($right);
                }

                return $this;
            }

            // Since only UserInterface and RightInterface objects are allowed, this parent MUST have RightInterface
            Log::action(ts('Adding right ":right" to role ":role"', [
                ':right' => $this->o_parent->getLogId(),
                ':role'  => $value->getLogId(),
            ]), 3);

            sql()->insert('accounts_roles_rights', [
                'rights_id' => $this->o_parent->getId(),
                'roles_id'  => $value->getId(),
            ]);

            // Update all users with this role to get the new right as well!
            foreach ($this->o_parent->getUsersObject() as $user) {
                User::new()->load($user)
                    ->getRightsObject()
                    ->add($this->o_parent);
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
        if ($this->o_parent and (($this->o_parent instanceof UserInterface) or ($this->o_parent instanceof RightInterface))) {
            return $this->removeValues($keys, strict: $strict);
        }

        return parent::removeKeys($keys, $strict);
    }


    /**
     * Removes the specified Role from the parent's Roles list
     *
     * @param ArrayableInterface|int|Stringable|array|string|null $needles
     * @param string|null                                         $column
     * @param bool                                                $strict
     *
     * @return static
     */
    public function removeValues(ArrayableInterface|int|Stringable|array|string|null $needles, ?string $column = null, bool $strict = false): static
    {
        $this->checkParent(tr('remove entry from parent'));

        if (!$needles) {
            // Nothing to do
            return $this;
        }

        if (is_array($needles)) {
            // Remove multiple rights
            foreach ($needles as $needle) {
                $this->removeKeys($needle, $strict);
            }

        } else {
            // Delete a single role. Since this is a Role object, the entry already exists in the database
            $o_role = Role::new($needles);

            if ($this->o_parent instanceof UserInterface) {
                Log::action(ts('Removing role ":role" from user ":user"', [
                    ':user' => $this->o_parent->getLogId(),
                    ':role' => $o_role->getLogId(),
                ]), 3);

                sql()->delete('accounts_users_roles', [
                    'users_id' => $this->o_parent->getId(),
                    'roles_id' => $o_role->getId(),
                ]);

                $o_rights = $o_role->getRightsObject();

                // Delete this role from the internal list
                parent::removeKeys($o_role->getUniqueColumnValue(), $strict);

                // Remove the rights related to this role
                foreach ($o_rights as $o_right) {

                    // Ensure this right isn't also given by another role
                    foreach ($o_right->getRolesObject() as $check_role) {
                        if ($this->hasRole($check_role)) {
                            // Don't remove this right, another role gives it too.
                            continue 2;
                        }
                    }

                    $this->o_parent->getRightsObject()->removeKeys($o_right, $strict);
                }

            } elseif ($this->o_parent instanceof RightInterface) {
                Log::action(ts('Removing role ":role" from right ":right"', [
                    ':right' => $this->o_parent->getLogId(),
                    ':role'  => $o_role->getLogId(),
                ]), 3);

                sql()->delete('accounts_roles_rights', [
                    'rights_id' => $this->o_parent->getId(),
                    'roles_id'  => $o_role->getId(),
                ]);

                // Update all users with this right to remove the new right as well!
                foreach ($this->o_parent->getUsersObject() as $o_user) {
                    User::new()
                        ->load($o_user)
                        ->getRightsObject()
                        ->removeKeys($this->o_parent, $strict);
                }

                // Remove right from the internal list
                parent::removeKeys($o_role->getUniqueColumnValue(), $strict);

            } else {
                parent::removeValues($needles, $column, $strict);
            }
        }

        return $this;
    }


    /**
     * Remove all rights for this role
     *
     * @return static
     * @todo Move saving part to ->save(). ->clear() should NOT immediately save to database!
     */
    public function clear(): static
    {
        $this->checkParent(tr('clear all entries from parent'));

        if ($this->o_parent instanceof UserInterface) {
            Log::action(ts('Removing all roles from user ":user"', [
                ':user' => $this->o_parent->getLogId(),
            ]));

            sql()->query('DELETE FROM `accounts_users_roles` WHERE `users_id` = :users_id', [
                'users_id' => $this->o_parent->getId(),
            ]);

        } elseif ($this->o_parent instanceof RightInterface) {
            Log::action(ts('Removing right ":right" from all roles', [
                ':right' => $this->o_parent->getLogId(),
            ]), 3);

            sql()->query('DELETE FROM `accounts_roles_rights` WHERE `rights_id` = :rights_id', [
                'rights_id' => $this->o_parent->getId(),
            ]);
        }

        return parent::clear();
    }


    /**
     * Load the data for this "roles" list into the object
     *
     * @param IdentifierInterface|array|string|int|null $identifiers
     * @param bool                                      $like
     *
     * @return static
     */
    public function load(IdentifierInterface|array|string|int|null $identifiers = null, bool $like = false): static
    {
        if ($this->o_parent) {
            if ($this->o_parent instanceof UserInterface) {
                $this->o_query_builder->addJoin('JOIN  `accounts_users_roles` 
                                                   ON  `accounts_users_roles`.`users_id` = :users_id
                                                  AND  `accounts_users_roles`.`roles_id` = `accounts_roles`.`id`', [
                    ':users_id' => $this->o_parent->getId(),
                ]);

            } elseif ($this->o_parent instanceof RightInterface) {
                $this->o_query_builder->addWhere('`accounts_roles_rights`.`rights_id` = :rights_id', [
                    ':rights_id' => $this->o_parent->getId(),
                ]);
            }
        }

        return parent::load($identifiers, $like);
    }


//    /**
//     * Load the data for this roles list
//     *
//     * @param array|string|null $columns
//     * @param array             $filters
//     * @param array             $order_by
//     *
//     * @return array
//     */
//    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
//    {
//        // Default columns
//        if (!$columns) {
//            $columns = 'id,name,rights';
//        }
//
//        // Default ordering
//        if (!$order_by) {
//            $order_by = ['name' => false];
//        }
//
//        // Get column information
//        $columns = Arrays::force($columns);
//        $users   = Arrays::replaceIfExists($columns, 'users', '1 AS `users`');
//        $rights  = Arrays::replaceIfExists($columns, 'rights', '1 AS `rights`');
//        $columns = Strings::force($columns);
//
//        // Build query
//        $builder = new QueryBuilder();
//        $builder->addSelect($columns);
//        $builder->addFrom('`accounts_roles`');
//
//        // Add ordering
//        foreach ($order_by as $column => $direction) {
//            $builder->addOrderBy('`' . $column . '` ' . ($direction ? 'DESC' : 'ASC'));
//        }
//
//        // Build filters
//        foreach ($filters as $key => $value) {
//            switch ($key) {
//                case 'users':
//                    $builder->addJoin('JOIN `accounts_users`
//                                       ON   `accounts_users`.`email` ' . $builder->compareQuery('email', $value) . '
//                                       JOIN `accounts_users_roles`
//                                       ON   `accounts_users_roles`.`users_id` = `accounts_users`.`id`
//                                       AND  `accounts_users_roles`.`roles_id` = `accounts_roles`.`id`');
//                    break;
//                case 'rights':
//                    $builder->addJoin('JOIN `accounts_rights`
//                                       ON   `accounts_rights`.`name` ' . $builder->compareQuery('right', $value) . '
//                                       JOIN `accounts_roles_rights`
//                                       ON   `accounts_roles_rights`.`rights_id` = `accounts_rights`.`id`
//                                       AND  `accounts_users_rights`.`roles_id`  = `accounts_roles`.`id`');
//                    break;
//            }
//        }
//
//        $return = sql()->list($builder->getQuery(), $builder->getExecute());
//
//        if ($users) {
//            // Add roles information to each user
//            foreach ($return as $id => &$item) {
//                $item['users'] = sql()->list('SELECT `email`
//                                              FROM   `accounts_users`
//                                              JOIN   `accounts_users_roles`
//                                              ON     `accounts_users_roles`.`roles_id` = :roles_id
//                                              AND    `accounts_users_roles`.`users_id` = `accounts_users`.`id`', [
//                    ':roles_id' => $id,
//                ]);
//                $item['users'] = implode(', ', $item['users']);
//            }
//
//            unset($item);
//        }
//
//        if ($rights) {
//            // Add rights information to each user
//            // Add roles information to each user
//            foreach ($return as $id => &$item) {
//                $item['rights'] = sql()->list('SELECT `name`
//                                               FROM   `accounts_rights`
//                                               JOIN   `accounts_roles_rights`
//                                               ON     `accounts_roles_rights`.`roles_id`  = :roles_id
//                                               AND    `accounts_roles_rights`.`rights_id` = `accounts_rights`.`id`', [
//                    ':roles_id' => $id,
//                ]);
//
//                $item['rights'] = implode(', ', $item['rights']);
//            }
//
//            unset($item);
//        }
//
//        return $return;
//    }


    /**
     * Save the data for this roles list in the database
     *
     * @param bool        $force
     * @param bool        $skip_validation
     * @param string|null $comments *
      *
     * @return static
     * @todo Implement this. ->add(), ->removeKeys(), ->clear() should NOT immediately save to database!
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static
    {
//        $this->checkParent(tr('save parent entries'));
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
     * @inheritDoc
     */
    public function setParentObject(DataEntryInterface|RenderInterface|UrlInterface|null $o_parent): static
    {
        if (!$o_parent instanceof UserInterface) {
            if (!$o_parent instanceof RightInterface) {
                throw new DataEntryInvalidParentException(tr('Cannot attach parent ":parent" with id ":id" to ":class" class object, must be of type "UserInterface" or "RightInterface"', [
                    ':id'     => $o_parent->getLogId(),
                    ':parent' => $o_parent::class,
                    ':class'  => $this::class,
                ]));
            }
        }

        return parent::setParentObject($o_parent);
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
    public function getHtmlSelectOld(string $value_column = 'CONCAT(UPPER(LEFT(`name`, 1)), SUBSTRING(`name`, 2)) AS `name`', ?string $key_column = 'id', ?string $order = '`name` ASC', ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface
    {
        return parent::getHtmlSelectOld($value_column, $key_column, $order, $joins, $filters)
                     ->setName('roles_id')
                     ->setNotSelectedLabel(tr('Select a role'))
                     ->setComponentEmptyLabel(tr('No roles available'));
    }
}
