<?php

/**
 * Class Rights
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

namespace Phoundation\Accounts\Rights;

use PDOStatement;
use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Roles\Interfaces\RoleInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataIterator;
use Phoundation\Data\DataEntries\Exception\DataEntryInvalidParentException;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataAutoCreate;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\InputSelect;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Stringable;
use Throwable;

class Rights extends DataIterator implements RightsInterface
{
    use TraitDataAutoCreate;


    /**
     * Rights class constructor
     */
    public function __construct(IteratorInterface|array|string|PDOStatement|null $source = null)
    {
        parent::__construct($source);

        $this->getQueryBuilderObject()->addSelect('`accounts_rights`.`id`, 
                                                   `accounts_rights`.`seo_name`, 
                                                   `accounts_rights`.`description`,
                                                   CONCAT(UPPER(LEFT(`accounts_rights`.`name`, 1)), SUBSTRING(`accounts_rights`.`name`, 2)) AS `right`, 
                                                   GROUP_CONCAT(CONCAT(UPPER(LEFT(`accounts_roles`.`name`, 1)), SUBSTRING(`accounts_roles`.`name`, 2)) SEPARATOR ", ") AS `roles`,')
                                      ->addJoin('LEFT JOIN  `accounts_roles_rights`
                                                        ON  `accounts_roles_rights`.`rights_id` = `accounts_rights`.`id`')
                                      ->addJoin('LEFT JOIN  `accounts_roles`
                                                        ON  `accounts_roles`.`id` = `accounts_roles_rights`.`roles_id`
                                                       AND (`accounts_roles`.`status` IS NULL OR `accounts_roles`.`status` != "deleted")')
                                      ->addWhere('(`accounts_rights`.`status` IS NULL OR `accounts_rights`.`status` != "deleted")')
                                      ->addGroupBy('`accounts_rights`.`name`')
                                      ->addOrderBy('`accounts_rights`.`name`');
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
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
        $values = QueryBuilder::in($rights);
        $rights = array_flip($rights);
        $exist  = sql()->query('SELECT `seo_name`
                                FROM   `accounts_rights`
                                WHERE  `seo_name` IN (' . implode(', ', array_keys($values)) . ')', $values);

        while ($right = $exist->fetchColumn()) {
            unset($rights[$right]);
        }

        return array_flip($rights);
    }


    /**
     * Ensure that the specified rights exist
     *
     * @param array|string $rights
     *
     * @todo Make this more efficient by storing up all the rights that failed, and then with one query checking which exists and which do not
     * @return bool
     */
    public function ensureRightsExist(array|string $rights): bool
    {
        if (!$this->auto_create) {
            return false;
        }

        $rights = Arrays::force($rights, null);

        // Save each right in this list if it doesn't exist
        foreach ($rights as $right) {
            if (is_numeric($right)) {
                // This is an ID, not a name. Right names cannot be numeric
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

            if (Right::notExists(['name' => $right])) {
                Right::new(null)
                     ->setName($right)
                     ->save();

                Incident::new()
                        ->setSeverity(EnumSeverity::medium)
                        ->setType('security')
                        ->setTitle(tr('Automatically created right'))
                        ->setBody(tr('The system encountered a request for the right ":right" and created it automatically', [
                            ':right' => $right
                        ]))
                        ->setDetails(['right' => $right])
                        ->setNotifyRoles('security')
                        ->save();
            }
        }

        return true;
    }


    /**
     * Save the data for this rights list in the database
     *
     * @param bool        $force
     * @param bool        $skip_validation
     * @param string|null $comments
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
        $this->checkParent(tr('save entries'));

        if (is_array($list)) {
            // Convert the list with whatever is specified (id, seo_name, role object) to seo_names
            $rights_list = [];

            foreach ($list as $right) {
                if ($right) {
                    $rights_list[] = Right::new()
                                          ->load($right)
                                          ->getSeoName();
                }
            }

            // Get a list of what we have to add and remove to get the same list, and apply
            $diff = Arrays::valueDiff(array_keys($this->source), $rights_list);

            foreach ($diff['add'] as $right) {
                $this->add($right, $column);
            }

            foreach ($diff['delete'] as $right) {
                $this->removeKeys($right);
            }

            // Add meta-information for parent
            $this->_parent->addMetaAction('Updated rights', data: $diff);
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
        return Right::class;
    }


    /**
     * Add the specified data entry to the data list
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return static
     * @throws OutOfBoundsException
     * @todo Move saving part to ->save(). ->add() should NOT immediately save to database!
     */
    public function append(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        $this->checkParent(tr('add Role entry to parent ":parent"', [
            ':parent' => $this->_parent ? get_class($this->_parent) : 'NULL'
        ]));

        if ($value and $this->_parent) {
            // A right with commas is actually a list of multiple rights
            if (is_string($value) and str_contains($value, ',')) {
                $value = Arrays::force($value);
            }

            // Add multiple rights in one go
            if (is_array($value)) {
                // Add multiple rights
                foreach ($value as $entry) {
                    $this->append($entry, $key, $skip_null_values);
                }

                return $this;
            }

            // Add single right. Since this is a Right object, the entry already exists in the database
            try {
                $value = Right::new()->load($value);

            } catch (DataEntryNotExistsException $e) {
                if (!$this->ensureRightsExist($value)) {
                    // The specified right doesn't exist
                    throw $e;
                }

                // The specified right didn't exist, but was automatically created
                $value = Right::new()->load($value);
            }

            // Right already exists for this parent?
            if ($this->hasRight($value)) {
                // Ignore and continue
                return $this;
            }

            // Add entry to parent, User or Role
            if ($this->_parent instanceof UserInterface) {
                Log::action(ts('Adding right ":right" to user ":user"', [
                    ':user'  => $this->_parent->getLogId(),
                    ':right' => $value->getLogId(),
                ]), 3);

                sql()->insert('accounts_users_rights', [
                    'users_id'  => $this->_parent->getId(),
                    'rights_id' => $value->getId(),
                    'name'      => $value->getName(),
                    'seo_name'  => $value->getSeoName(),
                ]);

            } elseif ($this->_parent instanceof RoleInterface) {
                Log::action(ts('Adding right ":right" to role ":role"', [
                    ':role'  => $this->_parent->getLogId(),
                    ':right' => $value->getLogId(),
                ]), 3);

                sql()->insert('accounts_roles_rights', [
                    'roles_id'  => $this->_parent->getId(),
                    'rights_id' => $value->getId(),
                ]);

                // Update all roles with this right to get the new right as well!
                foreach ($this->_parent->getUsersObject() as $user) {
                    User::new()->load($user)
                        ->getRightsObject()
                        ->add($value);
                }
            }
        }

        // Add right to the internal list
        return parent::append($value, $key, $skip_null_values, $exception);
    }


    /**
     * Returns true if the parent has the specified right
     *
     * @param RightInterface $_right
     *
     * @return bool
     */
    public function hasRight(RightInterface $_right): bool
    {
        if (!$this->_parent) {
            throw OutOfBoundsException::new('Cannot check if parent has the specified right, this rights list has no parent specified');
        }

        if ($this->_parent instanceof UserInterface) {
            return (bool) sql()->getRow('SELECT `id` 
                                         FROM   `accounts_users_rights` 
                                         WHERE  `users_id`  = :users_id 
                                         AND    `rights_id` = :rights_id', [
                ':users_id'  => $this->_parent->getId(),
                ':rights_id' => $_right->getId(),
            ]);
        }

        if ($this->_parent instanceof RoleInterface) {
            // No user? Then it must be a role
            return (bool) sql()->getRow('SELECT `id` 
                                         FROM   `accounts_roles_rights` 
                                         WHERE  `roles_id`  = :roles_id 
                                         AND    `rights_id` = :rights_id', [
                ':roles_id'  => $this->_parent->getId(),
                ':rights_id' => $_right->getId(),
            ]);
        }

        //
        return $this->hasKey($_right->getName());
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
        if ($this->_parent and (($this->_parent instanceof UserInterface) or ($this->_parent instanceof RoleInterface))) {
            return $this->removeValues($keys, strict: $strict);
        }

        return parent::removeKeys($keys, $strict);
    }


    /**
     * Removes the specified Right from the parent's Rights list
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
            // Add multiple rights
            foreach ($needles as $needle) {
                $this->removeValues($needle, $column, $strict);
            }

        } else {
            // Add single right. Since this is a Right object, the entry already exists in the database
            $_right = Right::new($needles);

            if ($this->_parent instanceof UserInterface) {
                Log::action(ts('Removing right ":right" from user ":user"', [
                    ':user'  => $this->_parent->getLogId(),
                    ':right' => $_right->getLogId(),
                ]), 3);

                sql()->delete('accounts_users_rights', [
                    'users_id'  => $this->_parent->getId(),
                    'rights_id' => $_right->getId(),
                ]);

                // Delete the right from the internal list
                parent::removeKeys($_right->getUniqueColumnValue(), strict: $strict);

            } elseif ($this->_parent instanceof RoleInterface) {
                Log::action(ts('Removing right ":right" from role ":role"', [
                    ':role'  => $this->_parent->getLogId(),
                    ':right' => $_right->getLogId(),
                ]), 3);

                sql()->delete('accounts_roles_rights', [
                    'roles_id'  => $this->_parent->getId(),
                    'rights_id' => $_right->getId(),
                ]);

                // Update all users with this role to get the new right as well!
                foreach ($this->_parent->getUsersObject() as $_user) {
                    $_user->getRightsObject()
                           ->removeKeys($_right, $strict);
                }

                // Delete the right from the internal list
                parent::removeValues($_right->getUniqueColumnValue(), strict: $strict);

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

        Log::action(ts('Removing all rights from ":class" parent ":id"', [
            ':class' => Strings::fromReverse($this->_parent::class, '\\'),
            ':id'    => $this->_parent->getLogId(),
        ]));

        foreach ($this as $_right) {
            $this->removeValues($_right);
        }

        return $this;
    }


    /**
     * Load the data for this rights list into the object
     *
     * @param IdentifierInterface|array|string|int|null $identifiers
     * @param bool                                                                     $like
     *
     * @return static
     */
    public function load(IdentifierInterface|array|string|int|null $identifiers = null, bool $like = false): static
    {
        if ($this->_parent) {
            // Load only rights for specified parent
            if ($this->_parent instanceof UserInterface) {
                $this->_query_builder->addJoin('JOIN  `accounts_users_rights` 
                                                 ON    `accounts_users_rights`.`users_id`  = :users_id
                                                 AND   `accounts_users_rights`.`rights_id` = `accounts_rights`.`id`', [
                    ':users_id' => $this->_parent->getId(),
                ]);

                // Load the rights so that we can add "everybody" after it
                parent::load();

                // Added the "everybody" right
                if ($this->source) {
                    $first = array_value_first($this->source);

                    if (is_object($first)) {
                        $this->source['everybody'] = Right::new()
                            ->setName('everybody')
                            ->setDescription(tr('This is a default right that applies to all users'));

                    } elseif (is_array($first)) {
                        $first                = Arrays::setValues($first);
                        $first['id']          = null;
                        $first['right']       = 'everybody';
                        $first['description'] = tr('This is a default right that applies to all users');

                        $this->source['everybody'] = $first;

                    } else {
                        $this->source['everybody'] = 'everybody';
                    }

                } else {
                    $this->source = [
                        'everybody' => [
                            'id'          => null,
                            'right'       => 'everybody',
                            'description' => tr('This is a default right that applies to all users'),
                        ]
                    ];
                }

                return $this;
            }

            if ($this->_parent instanceof RoleInterface) {
                $this->_query_builder->addWhere('`accounts_roles_rights`.`roles_id` = :roles_id', [
                    ':roles_id' => $this->_parent->getId(),
                ]);
            }
        }

        return parent::load($identifiers, $like);
    }


    /**
     * @inheritDoc
     */
    public function setParentObject(DataEntryInterface|RenderInterface|UrlInterface|null $_parent): static
    {
        // TODO This is a mess, redo parent management. Use the same allowed datatypes method as used for Iterator values!
        if (!$_parent instanceof UserInterface) {
            if (!$_parent instanceof RoleInterface) {
                if (!$_parent instanceof RenderInterface) {
                    if (!$_parent instanceof UrlInterface) {
                        throw new DataEntryInvalidParentException(tr('Cannot attach parent ":parent" with id ":id" to ":class" class object, must be of type "UserInterface" or "RoleInterface"', [
                            ':id'     => $_parent->getLogId(),
                            ':parent' => $_parent::class,
                            ':class'  => $this::class,
                        ]));
                    }
                }
            }
        }

        return parent::setParentObject($_parent);
    }


    /**
     * Returns a "select" with the available rights
     *
     * @return InputSelect
     */
    public function getHtmlSelectOld(string $value_column = 'CONCAT(UPPER(LEFT(`name`, 1)), SUBSTRING(`name`, 2)) AS `name`', ?string $key_column = 'id', ?string $order = '`name` ASC', ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface
    {
        return parent::getHtmlSelectOld($value_column, $key_column, $order, $joins, $filters)
                     ->setName('rights_id')
                     ->setNotSelectedLabel(tr('Select a right'))
                     ->setComponentEmptyLabel(tr('No rights available'));
    }


    /**
     * Returns true if the user has SOME of the specified rights
     *
     * @param array|string $rights             The required rights
     * @param string|null  $always_match [god] If specified, if the user has this right, this method will always return true, even if the user does not have the
     *                                         required rights
     *
     * @return bool
     */
    public function hasSome(array|string $rights, ?string $always_match = 'god'): bool
    {
        if (!$rights) {
            return true;
        }

        if (empty($this->rights) and ($this->getParentObject() instanceof DataEntryInterface) and $this->getParentObject()->isNew()) {
            return false;
        }

        $contains = $this->containsKeys($rights, false, $always_match);

        if (!$contains) {
            if ($this->getCount()) {
                $this->ensureRightsExist($this->getMissing($rights));

            } elseif (PLATFORM_CLI and ($this->getParentObject() instanceof UserInterface) and $this->getParentObject()->isSystem()) {
                // System user has all rights
                return true;
            }
        }

        return $contains;
    }


    /**
     * Returns true if the user has ALL the specified rights
     *
     * @param array|string $rights             The required rights
     * @param string|null  $always_match [god] If specified, if the user has this right, this method will always return true, even if the user does not have the
     *                                         required rights
     *
     * @return bool
     */
    public function hasAll(array|string $rights, ?string $always_match = 'god'): bool
    {
        if (!$rights) {
            return true;
        }

        if (empty($this->source) and ($this->getParentObject() instanceof DataEntryInterface) and $this->getParentObject()->isNew()) {
            return false;
        }

        $contains = $this->containsKeys($rights, true, $always_match);

        if (!$contains) {
            if ($this->getCount()) {
                $this->setAutoCreate(true)
                     ->ensureRightsExist($this->getMissing($rights));

            } elseif (PLATFORM_CLI and ($this->getParentObject() instanceof UserInterface) and $this->getParentObject()->isSystem()) {
                // System user has all rights
                return true;
            }
        }

        return $contains;
    }


    /**
     * Returns an array of what rights this user misses
     *
     * @param array|string $rights
     *
     * @return array
     */
    public function getMissing(array|string $rights): array
    {
        if (!$rights) {
            return [];
        }

        return $this->getMissingKeys($rights, 'god');
    }
}
