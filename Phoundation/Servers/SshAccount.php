<?php

declare(strict_types=1);

namespace Phoundation\Servers;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryFile;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryUsername;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Filesystem\Traits\TraitDataRestrictions;
use Phoundation\Servers\Interfaces\SshAccountInterface;
use Phoundation\Web\Html\Enums\EnumElementInputType;

/**
 * SshAccount class
 *
 *
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Servers
 */
class SshAccount extends DataEntry implements SshAccountInterface
{
    use TraitDataRestrictions;
    use TraitDataEntryNameDescription;
    use TraitDataEntryUsername;
    use TraitDataEntryFile;

    /**
     * SshAccount class constructor
     *
     * @param int|string|DataEntryInterface|null $identifier
     * @param string|null                        $column
     * @param bool|null                          $meta_enabled
     */
    public function __construct(int|string|DataEntryInterface|null $identifier = null, ?string $column = null, ?bool $meta_enabled = null)
    {
        $this->config_path = 'ssh.accounts.';
        parent::__construct($identifier, $column, $meta_enabled);
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
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('SSH account');
    }


    /**
     * Returns a unique log identifier that is both unique but also human readable
     *
     * @return string
     */
    public function getLogId(): string
    {
        return $this->getValueTypesafe('int', 'id') . ' / ' . (static::getUniqueColumn() ? $this->getValueTypesafe('string', static::getUniqueColumn()) : '-') . '(' . $this->getUsername() . ')';
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
     * Returns the ssh_key for this object
     *
     * @return string|null
     */
    public function getSshKey(): ?string
    {
        return $this->getValueTypesafe('string', 'ssh_key');
    }


    /**
     * Sets the ssh_key for this object
     *
     * @param string|null $ssh_key
     *
     * @return static
     */
    public function setSshKey(?string $ssh_key): static
    {
        return $this->setValue('ssh_key', $ssh_key);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::getName($this)
                                           ->setSize(6)
                                           ->setHelpGroup(tr('Identification'))
                                           ->setHelpText(tr('The name for this account')))
                    ->add(Definition::new($this, 'seo_name')
                                    ->setRender(false)
                                    ->setReadonly(true))
                    ->add(Definition::new($this, 'username')
                                    ->setLabel(tr('Username'))
                                    ->setInputType(EnumElementInputType::username)
                                    ->setCliColumn(tr('-u,--username NAME'))
                                    ->setCliAutoComplete(true)
                                    ->setSize(6)
                                    ->setMaxlength(64)
                                    ->setHelpText(tr('The username on the server for this account')))
                    ->add(DefinitionFactory::getDescription($this)
                                           ->setHelpText(tr('The description for this account')))
                    ->add(DefinitionFactory::getFile($this)
                                           ->setLabel(tr('SSH key file'))
                                           ->setCliColumn(tr('-i,--ssh-key-file FILE'))
                                           ->setHelpText(tr('The SSH key file for this account'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isFile('/');
                                           }))
                    ->add(Definition::new($this, 'ssh_key')
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
