<?php

/**
 * Class Right
 *
 *
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Rights;

use Phoundation\Accounts\Exception\AccountsException;
use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Roles\Exception\RightNotExistsException;
use Phoundation\Accounts\Roles\Interfaces\RolesInterface;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Exception\DataEntryDeletedException;
use Phoundation\Data\DataEntry\Exception\Interfaces\DataEntryNotExistsExceptionInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryNameLowercaseDash;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Web\Html\Enums\EnumInputType;


class Right extends DataEntry implements RightInterface
{
    use TraitDataEntryNameLowercaseDash;
    use TraitDataEntryDescription;

    /**
     * Right class constructor
     *
     * @param array|DataEntryInterface|string|int|null $identifier
     * @param bool|null                                $meta_enabled
     * @param bool                                     $init
     */
    public function __construct(array|DataEntryInterface|string|int|null $identifier = null, ?bool $meta_enabled = null, bool $init = true)
    {
        return parent::__construct(static::convertToLowerCaseDash($identifier), $meta_enabled, $init);
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
    public static function getDataEntryName(): string
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
     * @param array|DataEntryInterface|string|int|null $identifier
     * @param bool                                     $meta_enabled
     * @param bool                                     $ignore_deleted
     *
     * @return Right
     */
    public static function load(array|DataEntryInterface|string|int|null $identifier, bool $meta_enabled = false, bool $ignore_deleted = false): static
    {
        try {
            return parent::load(static::convertToLowerCaseDash($identifier), $meta_enabled, $ignore_deleted);

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
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::newName($this)
                                           ->setInputType(EnumInputType::name)
                                           ->setSize(12)
                                           ->setMaxlength(64)
                                           ->setHelpText(tr('The name for this right'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isUnique();
                                           }))
                    ->add(DefinitionFactory::newSeoName($this))
                    ->add(DefinitionFactory::newDescription($this)
                                           ->setHelpText(tr('The description for this right')));
    }
}
