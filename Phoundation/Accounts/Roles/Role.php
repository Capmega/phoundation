<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Roles;

use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Rights\Rights;
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
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryNameLowercaseDash;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\Interfaces\OutOfBoundsExceptionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\DataEntryForm;
use Phoundation\Web\Html\Components\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Html\Enums\InputTypeExtended;


/**
 * Class Role
 *
 *
 *
 * @see DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Role extends DataEntry implements RoleInterface
{
    use DataEntryNameLowercaseDash;
    use DataEntryDescription;


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
    public static function getDataEntryName(): string
    {
        return tr('Role');
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
     * Add the specified rights to this role
     *
     * @return RightsInterface
     */
    public function getRights(): RightsInterface
    {
        if (!$this->list) {
            $this->list = Rights::new()->setParent($this)->load();
        }

        return $this->list;
    }


    /**
     * Returns the users that are linked to this role
     *
     * @return UsersInterface
     */
    public function getUsers(): UsersInterface
    {
        return Users::new()->setParent($this)->load();
    }


    /**
     * Creates and returns an HTML data entry form
     *
     * @param string $name
     * @return DataEntryFormInterface
     */
    public function getRightsHtmlDataEntryForm(string $name = 'rights_id[]'): DataEntryFormInterface
    {
        $entry  = DataEntryForm::new()->setRenderContentsOnly(true);
        $rights = Rights::new();
        $select = $rights->getHtmlSelect()->setCache(true)->setName($name);

        // Add extra entry with nothing selected
        $select->clearSelected();
        $entry->appendContent($select->render() . '<br>');

        foreach ($this->getRights() as $right) {
            $select->setSelected($right->getId());
            $entry->appendContent($select->render() . '<br>');
        }

        return $entry;
    }


    /**
     * Returns a DataEntry object matching the specified identifier
     *
     * @note This method also accepts DataEntry objects, in which case it will simply return this object. This is to
     *       simplify "if this is not DataEntry object then this is new DataEntry object" into
     *       "PossibleDataEntryVariable is DataEntry::new(PossibleDataEntryVariable)"
     * @param DataEntryInterface|string|int|null $identifier
     * @param string $column
     * @param bool $meta_enabled
     * @return static|null
     */
    public static function get(DataEntryInterface|string|int|null $identifier, ?string $column = null, bool $meta_enabled = false): ?static
    {
        try {
            return parent::get($identifier, $column, $meta_enabled);

        } catch (DataEntryNotExistsExceptionInterface|DataEntryDeletedException $e) {
            throw new RoleNotExistsException($e);
        }
    }


    /**
     * Merge this role with the rights from the specified role
     *
     * @param RoleInterface|string|int|null $from_identifier
     * @param string|null $column
     * @return $this
     * @throws OutOfBoundsExceptionInterface|RoleNotExistsExceptionInterface
     */
    public function mergeFrom(RoleInterface|string|int|null $from_identifier = null, ?string $column = null): static
    {
        $from = Role::get($from_identifier, $column);

        if (!$this->getId()) {
            throw new OutOfBoundsException(tr('Cannot merge role ":from" to this role ":this" because this role does not yet exist in the database', [
                ':from' => $from->getLogId(),
                ':this' => $this->getLogId()
            ]));
        }

        // This role must get all rights from the $FROM role
        foreach ($from->getRights() as $right) {
            $this->getRights()->addRight($right);
        }

        // All users that have the $FROM role must get this role too
        foreach ($from->getUsers() as $user) {
            $user->getRoles()->addRole($this);
        }

        // Remove the "from" role
        $from->erase();

        return $this;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(DefinitionFactory::getName($this)
                ->setInputType(InputTypeExtended::name)
                ->setSize(12)
                ->setMaxlength(64)
                ->setHelpText(tr('The name for this role'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isUnique(tr('value ":name" already exists', [':name' => $validator->getSelectedValue()]));
                }))
            ->addDefinition(DefinitionFactory::getSeoName($this))
            ->addDefinition(DefinitionFactory::getDescription($this)
                ->setHelpText(tr('The description for this role')));
    }
}
