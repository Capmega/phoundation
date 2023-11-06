<?php

declare(strict_types=1);

namespace Phoundation\Servers;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryUsername;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Filesystem\Traits\DataRestrictions;
use Phoundation\Web\Html\Enums\InputTypeExtended;


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
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'ssh_accounts';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('SSH account');
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
     * Returns the ssh_key for this object
     *
     * @return string|null
     */
    public function getSshKey(): ?string
    {
        return $this->getSourceFieldValue('string', 'ssh_key');
    }


    /**
     * Sets the ssh_key for this object
     *
     * @param string|null $ssh_key
     * @return static
     */
    public function setSshKey(?string $ssh_key): static
    {
        return $this->setSourceValue('ssh_key', $ssh_key);
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
                ->setSize(6)
                ->setHelpGroup(tr('Identification'))
                ->setHelpText(tr('The name for this account')))
            ->addDefinition(Definition::new($this, 'seo_name')
                ->setVisible(false)
                ->setReadonly(true))
            ->addDefinition(Definition::new($this, 'username')
                ->setLabel(tr('Username'))
                ->setInputType(InputTypeExtended::username)
                ->setCliField(tr('-u,--username NAME'))
                ->setCliAutoComplete(true)
                ->setSize(6)
                ->setMaxlength(64)
                ->setHelpText(tr('The username on the server for this account')))
            ->addDefinition(DefinitionFactory::getDescription($this)
                ->setHelpText(tr('The description for this account')))
            ->addDefinition(Definition::new($this, 'ssh_key')
                ->setLabel(tr('SSH key'))
                ->setCliField(tr('-i,--ssh-key-file FILE'))
                ->setCliAutoComplete(true)
                ->setSize(12)
                ->setMaxlength(65_535)
                ->setHelpText(tr('The SSH private key associated with this username'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->matchesRegex('-----BEGIN .+? PRIVATE KEY-----.+?-----END .+? PRIVATE KEY-----');
                }));
    }
}
