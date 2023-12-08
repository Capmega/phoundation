<?php

namespace Phoundation\Databases\Connectors;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryDatabase;
use Phoundation\Data\DataEntry\Traits\DataEntryPassword;
use Phoundation\Data\DataEntry\Traits\DataEntryHostnamePort;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryUsername;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Web\Html\Enums\InputElement;
use Phoundation\Web\Html\Enums\InputTypeExtended;


/**
 * SqlConnector class
 *
 * This class represents a single SQL connector coming either from configuration or DB storage
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Connector extends DataEntry implements ConnectorInterface
{
    use DataEntryNameDescription;
    use DataEntryHostnamePort;
    use DataEntryUsername;
    use DataEntryPassword;
    use DataEntryDatabase;


    /**
     * @inheritDoc
     */
    public static function getTable(): string
    {
        return 'databases_connectors';
    }


    /**
     * @inheritDoc
     */
    public static function getDataEntryName(): string
    {
        return tr('SQL connector');
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueField(): ?string
    {
        return 'name';
    }


    /**
     * Returns the type for this connector
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->getSourceFieldValue('string', 'type');
    }


    /**
     * Sets the type for this connector
     *
     * @param string|null $type
     * @return static
     */
    public function setType(?string $type): static
    {
        return $this->setSourceValue('type', $type);
    }


    /**
     * Returns the ssh_tunnel for this connector
     *
     * @return bool|null
     */
    public function getSshTunnel(): ?bool
    {
        return $this->getSourceFieldValue('bool', 'ssh_tunnel');
    }


    /**
     * Sets the ssh_tunnel for this connector
     *
     * @param int|bool|null $ssh_tunnel
     * @return static
     */
    public function setSshTunnel(int|bool|null $ssh_tunnel): static
    {
        return $this->setSourceValue('ssh_tunnel', (bool) $ssh_tunnel);
    }


    /**
     * @inheritDoc
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(DefinitionFactory::getName($this)
                ->setSize(6)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isUnique();
                }))
            ->addDefinition(DefinitionFactory::getSeoName($this))
            ->addDefinition(DefinitionFactory::getVariable($this, 'type')
                ->setSize(6)
                ->setLabel('Connector type')
                ->setInputType(null)
                ->setElement(InputElement::select)
                ->setSource([
                    'sql'       => tr('SQL'),
                    'memcached' => tr('Memcached'),
                    'mongodb'   => tr('MongoDB'),
                    'redis'     => tr('Redis'),
                ]))
            ->addDefinition(DefinitionFactory::getVariable($this, 'hostname')
                ->setLabel(tr('Hostname'))
                ->setSize(9))
            ->addDefinition(DefinitionFactory::getNumber($this, 'port')
                ->setLabel(tr('Port'))
                ->setSize(3)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isInteger()->isBetween(0, 65535);
                }))
            ->addDefinition(DefinitionFactory::getVariable($this, 'username')
                ->setLabel(tr('Username')))
            ->addDefinition(DefinitionFactory::getPassword($this, 'password')
                ->setLabel(tr('Password')))
            ->addDefinition(DefinitionFactory::getVariable($this, 'database')
                ->setLabel(tr('Database')))
            ->addDefinition(DefinitionFactory::getNumber($this, 'autoincrement')
                ->setLabel(tr('Auto increment'))
                ->setInputType(InputTypeExtended::positiveInteger)
                ->setSize(3))
            ->addDefinition(DefinitionFactory::getNumber($this, 'limit_max')
                ->setLabel(tr('Maximum row limit'))
                ->setDefault(1_000_000)
                ->setInputType(InputTypeExtended::positiveInteger)
                ->setSize(3))
            ->addDefinition(Definition::new($this, 'mode')
                ->setLabel(tr('Mode'))
                ->setSize(6))
            ->addDefinition(Definition::new($this, 'pdo_attributes')
                ->setLabel(tr('PDO attributes'))
                ->setSize(6))
            ->addDefinition(Definition::new($this, 'character_set')
                ->setLabel(tr('Character set'))
                ->setSize(6))
            ->addDefinition(Definition::new($this, 'collate')
                ->setLabel(tr('Collate'))
                ->setSize(6))
            ->addDefinition(Definition::new($this, 'ssh_tunnels_id')
                ->setLabel(tr('SSL Tunnel'))
                ->setSize(4))
            ->addDefinition(DefinitionFactory::getBoolean($this, 'init')
                ->setLabel(tr('Initializes'))
                ->setSize(2))
            ->addDefinition(DefinitionFactory::getBoolean($this, 'buffered')
                ->setLabel(tr('Buffered'))
                ->setSize(2))
            ->addDefinition(DefinitionFactory::getBoolean($this, 'log')
                ->setLabel(tr('Log'))
                ->setSize(2))
            ->addDefinition(DefinitionFactory::getBoolean($this, 'statistics')
                ->setLabel(tr('Statistics'))
                ->setSize(2))
            ->addDefinition(DefinitionFactory::getTimezone($this, 'timezone')
                ->setLabel(tr('Timezone'))
                ->setSize(2))
            ->addDefinition(DefinitionFactory::getDescription($this));
    }
}