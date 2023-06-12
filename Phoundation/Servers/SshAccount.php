<?php

declare(strict_types=1);

namespace Phoundation\Servers;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryFieldDefinition;
use Phoundation\Data\DataEntry\DataEntryFieldDefinitions;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryUsername;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Filesystem\Traits\DataRestrictions;
use Phoundation\Web\Http\Html\Enums\InputElement;
use Phoundation\Web\Http\Html\Enums\InputTypeExtended;

/**
 * SshAccount class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Servers
 */
class SshAccount extends DataEntry
{
    use DataRestrictions;
    use DataEntryNameDescription;
    use DataEntryUsername;

    /**
     * User class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name = 'SSH account';

        parent::__construct($identifier);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'ssh_accounts';
    }


    /**
     * Returns the ssh_key for this object
     *
     * @return string|null
     */
    public function getSshKey(): ?string
    {
        return $this->getDataValue('string', 'ssh_key');
    }


    /**
     * Sets the ssh_key for this object
     *
     * @param string|null $ssh_key
     * @return static
     */
    public function setSshKey(?string $ssh_key): static
    {
        return $this->setDataValue('ssh_key', $ssh_key);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DataEntryFieldDefinitionsInterface $field_definitions
     */
    protected function initFieldDefinitions(DataEntryFieldDefinitionsInterface $field_definitions): void
    {
        $field_definitions
            ->add(DataEntryFieldDefinition::new('name')
                ->setLabel(tr('Name'))
                ->setInputType(InputTypeExtended::name)
                ->setCliField(tr('-n,--name NAME'))
                ->setAutoComplete(true)
                ->setSize(6)
                ->setMaxlength(64)
                ->setHelpGroup(tr('Identification'))
                ->setHelpText(tr('The name for this account')))
            ->add(DataEntryFieldDefinition::new('seo_name')
                ->setVisible(false)
                ->setReadonly(true))
            ->add(DataEntryFieldDefinition::new('username')
                ->setLabel(tr('Username'))
                ->setInputType(InputTypeExtended::username)
                ->setCliField(tr('-u,--username NAME'))
                ->setAutoComplete(true)
                ->setSize(6)
                ->setMaxlength(64)
                ->setHelpText(tr('The username on the server for this account')))
            ->add(DataEntryFieldDefinition::new('description')
                ->setLabel(tr('Description'))
                ->setCliField(tr('-d,--description DESCRIPTION'))
                ->setElement(InputElement::textarea)
                ->setSize(12)
                ->setMaxlength(65_535)
                ->setHelpText(tr('The description for this account')))
            ->add(DataEntryFieldDefinition::new('ssh_key')
                ->setLabel(tr('SSH key'))
                ->setCliField(tr('-i,--ssh-key-file FILE'))
                ->setAutoComplete(true)
                ->setSize(12)
                ->setMaxlength(65_535)
                ->setHelpText(tr('The SSH private key associated with this username'))
                ->addValidationFunction(function ($validator) {
                    $validator->matchesRegex('-----BEGIN .+? PRIVATE KEY-----.+?-----END .+? PRIVATE KEY-----');
                }));
    }
}