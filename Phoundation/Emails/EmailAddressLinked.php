<?php

/**
 * Class EmailAddressLinked
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Plugins\Emails
 */


declare(strict_types=1);

namespace Phoundation\Emails;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryEmail;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryName;
use Phoundation\Emails\Enums\EnumEmailAddressType;


class EmailAddressLinked extends DataEntry
{
    use TraitDataEntryName;
    use TraitDataEntryEmail;

    /**
     * The type of email address for this email, to, from, cc, or bcc
     *
     * @var EnumEmailAddressType|null $type
     */
    protected ?EnumEmailAddressType $type = null;


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'emails_addresses_linked';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('Email to');
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return null;
    }


    /**
     * Sets and returns the field definitions for the data fields in this DataEntry object
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::newDatabaseId($this, 'emails_id')
                                           ->setRender(false))
                    ->add(DefinitionFactory::newDatabaseId($this, 'address_id')
                                           ->setRender(false))
                    ->add(Definition::new($this, 'type')
                                    ->setRender(false)
                                    ->setDataSource([
                                        'from' => tr('From'),
                                        'to'   => tr('To'),
                                        'cc'   => tr('Cc'),
                                        'bcc'  => tr('Bcc'),
                                    ])
                                    ->setMaxlength(4))
                    ->add(DefinitionFactory::newEmail($this))
                    ->add(DefinitionFactory::newName($this));
    }
}
