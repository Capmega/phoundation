<?php

/**
 * Class Role
 *
 *
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Roles;

use Phoundation\Accounts\Exception\AccountsException;
use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Rights\RightsBySeoName;
use Phoundation\Accounts\Roles\Exception\Interfaces\RoleNotExistsExceptionInterface;
use Phoundation\Accounts\Roles\Exception\RoleNotExistsException;
use Phoundation\Accounts\Roles\Interfaces\RoleInterface;
use Phoundation\Accounts\Users\Interfaces\UsersInterface;
use Phoundation\Accounts\Users\Users;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Exception\DataEntryDeletedException;
use Phoundation\Data\DataEntry\Exception\Interfaces\DataEntryNotExistsExceptionInterface;
use Phoundation\Data\DataEntry\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryNameLowercaseDash;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Exception\Interfaces\OutOfBoundsExceptionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Forms\DataEntryForm;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Html\Enums\EnumInputType;


class Role extends DataEntry implements RoleInterface
{
    use TraitDataEntryNameLowercaseDash;
    use TraitDataEntryDescription;


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
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return tr('Role');
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
     * Sets if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     *
     * @return static
     */
    public function setIdentifier(IdentifierInterface|array|int|string|null $identifier): static {
        return parent::setIdentifier(static::convertNameIdentifierToLowerCaseDash($identifier));
    }


    /**
     * Creates and returns an HTML data entry form
     *
     * @param string $name
     *
     * @return DataEntryFormInterface
     */
    public function getRightsHtmlDataEntryForm(string $name = 'rights_id[]'): DataEntryFormInterface
    {
        // Get a list of all rights for this role
        $selected = [];

        if (!$this->isNew()) {
            foreach ($this->getRightsObject() as $right) {
                $selected[] = $right->getId();
            }
        }

        // Build up the rights select object
        $rights = Rights::new();
        $rights->setQueryBuilder(QueryBuilder::new($rights)
                                             ->setSelect('`accounts_rights`.`id`, 
                                                          CONCAT(
                                                            UPPER(LEFT(`accounts_rights`.`name`, 1)), 
                                                            SUBSTRING(`accounts_rights`.`name`, 2)
                                                          ) AS `name`')
                                             ->setWhere('`accounts_rights`.`status` IS NULL')
                                             ->setOrderBy('`name`'))
               ->load();

        $entry  = DataEntryForm::new()->setRenderContentsOnly(true);
        $select = $rights->getHtmlSelect()->setCache(true)
                                          ->setNotSelectedLabel(null)
                                          ->setMultiple(true)
                                          ->setName($name)
                                          ->setSize($rights->getCount())
                                          ->setSelected($selected);

        return $entry->appendContent($select->render());
    }


    /**
     * Add the specified rights to this role
     *
     * @return RightsInterface
     */
    public function getRightsObject(): RightsInterface
    {
        if ($this->isNew()) {
            throw new AccountsException(tr('Cannot access rights for role ":role", the role has not yet been saved and so has no identifier', [
                ':role' => $this->getLogId(),
            ]));
        }

        if (!$this->list) {
            $this->list = RightsBySeoName::new()
                                         ->setParentObject($this)
                                         ->load();
        }

        return $this->list;
    }


    /**
     * Merge this role with the rights from the specified role
     *
     * @param RoleInterface|string|int|null $from_identifier
     *
     * @return static
     * @throws OutOfBoundsExceptionInterface|RoleNotExistsExceptionInterface
     */
    public function mergeFrom(RoleInterface|string|int|null $from_identifier = null): static
    {
        $from = Role::new()->load($from_identifier);

        if (!$this->getId()) {
            throw new OutOfBoundsException(tr('Cannot merge role ":from" to this role ":this" because this role does not yet exist in the database', [
                ':from' => $from->getLogId(),
                ':this' => $this->getLogId(),
            ]));
        }

        // This role must get all rights from the $FROM role
        foreach ($from->getRightsObject() as $right) {
            $this->getRightsObject()
                 ->add($right);
        }

        // All users that have the $FROM role must get this role too
        foreach ($from->getUsersObject() as $user) {
            $user->getRolesObject()->add($this);
        }

        // Remove the "from" role
        $from->erase();

        return $this;
    }


    /**
     * Returns a Role object matching the specified identifier
     *
     * @note This method also accepts DataEntry objects, in which case it will simply return this object. This is to
     *       simplify "if this is not DataEntry object then this is new DataEntry object" into
     *       "PossibleDataEntryVariable is DataEntry::new(PossibleDataEntryVariable)"
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     *
     * @return static
     */
    public function load(IdentifierInterface|array|string|int|null $identifier = null): static
    {
        try {
            return parent::load($identifier);

        } catch (DataEntryNotExistsExceptionInterface|DataEntryDeletedException $e) {
            throw new RoleNotExistsException($e);
        }
    }


    /**
     * Returns the users that are linked to this role
     *
     * @return UsersInterface
     */
    public function getUsersObject(): UsersInterface
    {
        if ($this->isNew()) {
            throw new AccountsException(tr('Cannot access users for role ":role", the role has not yet been saved', [
                ':role' => $this->getLogId(),
            ]));
        }

        return Users::new()
                    ->setParentObject($this)
                    ->load();
    }


    /**
     * Delete this role
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function delete(?string $comments = null): static
    {
        // Update all accounts_users_roles and accounts_roles_rights too
        sql()->query('UPDATE `accounts_users_roles` SET status = "deleted" WHERE `roles_id` = :roles_id', [
            ':roles_id' => $this->getId(),
        ]);

        sql()->query('UPDATE `accounts_roles_rights` SET status = "deleted" WHERE `roles_id` = :roles_id', [
            ':roles_id' => $this->getId(),
        ]);


        return parent::delete($comments);
    }


    /**
     * Undelete this role
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function undelete(?string $comments = null): static
    {
        // Update all accounts_users_roles and accounts_roles_rights too
        sql()->query('UPDATE `accounts_users_roles` SET status = NULL WHERE `roles_id` = :roles_id', [
            ':roles_id' => $this->getId(),
        ]);

        sql()->query('UPDATE `accounts_roles_rights` SET status = NULL WHERE `roles_id` = :roles_id', [
            ':roles_id' => $this->getId(),
        ]);


        return parent::undelete($comments);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     *
     * @return static
     */
    protected function setDefinitions(DefinitionsInterface $definitions): static
    {
        $definitions->add(DefinitionFactory::newName()
                                           ->setOptional(false)
                                           ->setInputType(EnumInputType::name)
                                           ->setSize(12)
                                           ->setMaxlength(64)
                                           ->setHelpText(tr('The name for this role'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isUnique();
                                           }))

                    ->add(DefinitionFactory::newSeoName())

                    ->add(DefinitionFactory::newDescription()
                                           ->setHelpText(tr('The description for this role')));

        return $this;
    }
}
