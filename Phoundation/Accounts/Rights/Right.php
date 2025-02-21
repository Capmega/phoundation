<?php

/**
 * Class Right
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

namespace Phoundation\Accounts\Rights;

use Phoundation\Accounts\Exception\AccountsException;
use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Roles\Exception\RightNotExistsException;
use Phoundation\Accounts\Roles\Interfaces\RolesInterface;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Accounts\Users\Interfaces\UsersInterface;
use Phoundation\Accounts\Users\Users;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Exception\DataEntryDeletedException;
use Phoundation\Data\DataEntries\Exception\Interfaces\DataEntryNotExistsExceptionInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryNameLowercaseDash;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Web\Html\Enums\EnumInputType;


class Right extends DataEntry implements RightInterface
{
    use TraitDataEntryNameLowercaseDash;
    use TraitDataEntryDescription;

    /**
     * Right class constructor
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     */
    public function __construct(IdentifierInterface|array|string|int|false|null $identifier = null)
    {
        return parent::__construct(static::convertNameIdentifierToLowerCaseDash($identifier));
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
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return tr('Right');
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
     * Returns a Right object matching the specified identifier
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
            throw new RightNotExistsException($e);
        }
    }


    /**
     * Returns the roles that give this right
     *
     * @return RolesInterface
     */
    public function getRolesObject(): RolesInterface
    {
        if ($this->isNew()) {
            throw new AccountsException(tr('Cannot access roles for right ":right", the right has not yet been saved', [
                ':right' => $this->getLogId(),
            ]));
        }

        return Roles::new()
                    ->setParentObject($this)
                    ->load();
    }


    /**
     * Returns the users that are linked to this right
     *
     * @return UsersInterface
     */
    public function getUsersObject(): UsersInterface
    {
        if ($this->isNew()) {
            throw new AccountsException(tr('Cannot access users for right ":right", the right has not yet been saved', [
                ':right' => $this->getLogId(),
            ]));
        }

        return Users::new()
                    ->setParentObject($this);
    }


    /**
     * Delete this right
     *
     *
     * @param string|null $comments
     * @param bool        $auto_save
     *
     * @return static
     */
    public function delete(?string $comments = null, bool $auto_save = true): static
    {
        // Update all accounts_users_rights too
        if ($this->getId(false)) {
            sql()->query('UPDATE `accounts_users_rights` SET status = "deleted" WHERE `rights_id` = :rights_id', [
                ':rights_id' => $this->getId(),
            ]);
        }

        return parent::delete($comments, $auto_save);
    }


    /**
     * Undelete this right
     *
     * @param string|null $comments
     * @param bool        $auto_save
     *
     * @return static
     */
    public function undelete(?string $comments = null, bool $auto_save = true): static
    {
        // Update all accounts_users_rights too
        if ($this->getId(false)) {
            sql()->query('UPDATE `accounts_users_rights` SET status = NULL WHERE `rights_id` = :rights_id', [
                ':rights_id' => $this->getId(),
            ]);

            sql()->query('UPDATE `accounts_roles_rights` SET status = NULL WHERE `rights_id` = :rights_id', [
                ':rights_id' => $this->getId(),
            ]);
        }

        return parent::undelete($comments, $auto_save);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     * @return static
     */
    protected function setDefinitions(DefinitionsInterface $definitions): static
    {
        $definitions->add(DefinitionFactory::newName()
                                           ->setInputType(EnumInputType::name)
                                           ->setSize(12)
                                           ->setMaxlength(64)
                                           ->setHelpText(tr('The name for this right'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isUnique();
                                           }))

                    ->add(DefinitionFactory::newSeoName())

                    ->add(DefinitionFactory::newDescription()
                                           ->setHelpText(tr('The description for this right')));

        return $this;
    }
}
