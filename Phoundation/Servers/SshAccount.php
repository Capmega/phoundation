<?php

declare(strict_types=1);

namespace Phoundation\Servers;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryFile;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryUsername;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Filesystem\Traits\DataRestrictions;
use Phoundation\Servers\Interfaces\SshAccountInterface;
use Phoundation\Web\Html\Enums\InputTypeExtended;


/**
 * SshAccount class
 *
 *
 *
 * @see DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Servers
 */
class SshAccount extends DataEntry implements SshAccountInterface
{
    use DataRestrictions;
    use DataEntryNameDescription;
    use DataEntryUsername;
    use DataEntryFile;


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
    public static function getUniqueColumn(): ?string
    {
        return 'name';
    }


    /**
     * SshAccount class constructor
     *
     * @param int|string|DataEntryInterface|null $identifier
     * @param string|null $column
     * @param bool|null $meta_enabled
     */
    public function __construct(int|string|DataEntryInterface|null $identifier = null, ?string $column = null, ?bool $meta_enabled = null)
    {
        $this->config_path = 'ssh.accounts.';
        parent::__construct($identifier, $column, $meta_enabled);
    }


    /**
     * Returns a unique log identifier that is both unique but also human readable
     *
     * @return string
     */
    public function getLogId(): string
    {
        return $this->getSourceColumnValue('int', 'id') . ' / ' . (static::getUniqueColumn() ? $this->getSourceColumnValue('string', static::getUniqueColumn()) : '-') . '(' . $this->getUsername() . ')';
    }


    /**
     * Returns the ssh_key for this object
     *
     * @return string|null
     */
    public function getSshKey(): ?string
    {
        return $this->getSourceColumnValue('string', 'ssh_key');
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
                ->setCliColumn(tr('-u,--username NAME'))
                ->setCliAutoComplete(true)
                ->setSize(6)
                ->setMaxlength(64)
                ->setHelpText(tr('The username on the server for this account')))
            ->addDefinition(DefinitionFactory::getDescription($this)
                ->setHelpText(tr('The description for this account')))
            ->addDefinition(DefinitionFactory::getFile($this)
                ->setLabel(tr('SSH key file'))
                ->setCliColumn(tr('-i,--ssh-key-file FILE'))
                ->setHelpText(tr('The SSH key file for this account'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isFile('/');
                }))
            ->addDefinition(Definition::new($this, 'ssh_key')
                ->setLabel(tr('SSH key'))
                ->setCliColumn(tr('-k,--ssh-key "KEY"'))
                ->setCliAutoComplete(true)
                ->setSize(12)
                ->setMaxlength(65_535)
                ->setHelpText(tr('The SSH private key associated with this username'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->matchesRegex('-----BEGIN .+? PRIVATE KEY-----.+?-----END .+? PRIVATE KEY-----');
                }));
    }
}
