<?php

namespace Phoundation\Databases\Connectors;

use MongoDB\Exception\UnsupportedException;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryCharacterSet;
use Phoundation\Data\DataEntry\Traits\DataEntryCollate;
use Phoundation\Data\DataEntry\Traits\DataEntryDatabase;
use Phoundation\Data\DataEntry\Traits\DataEntryPassword;
use Phoundation\Data\DataEntry\Traits\DataEntryHostnamePort;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryTimezone;
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
    use DataEntryTimezone;
    use DataEntryCharacterSet;
    use DataEntryCollate;


    /**
     * DataEntry class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null $column
     * @param bool|null $meta_enabled
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, ?string $column = null, ?bool $meta_enabled = null)
    {
        $this->config_path = 'databases.connectors';
        parent::__construct($identifier, $column, $meta_enabled);
    }


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
     * Returns the pdo_attributes for this connector
     *
     * @return string|null
     */
    public function getPdoAttributes(): ?string
    {
        return $this->getSourceFieldValue('string', 'pdo_attributes');
    }


    /**
     * Sets the pdo_attributes for this connector
     *
     * @param string|null $pdo_attributes
     * @return static
     */
    public function setPdoAttributes(?string $pdo_attributes): static
    {
        return $this->setSourceValue('pdo_attributes', $pdo_attributes);
    }


    /**
     * Returns the mode for this connector
     *
     * @return string|null
     */
    public function getMode(): ?string
    {
        return $this->getSourceFieldValue('string', 'mode');
    }


    /**
     * Sets the mode for this connector
     *
     * @param string|null $mode
     * @return static
     */
    public function setMode(?string $mode): static
    {
        return $this->setSourceValue('mode', $mode);
    }


    /**
     * Returns the limit_max for this connector
     *
     * @return int|null
     */
    public function getLimitMax(): ?int
    {
        return $this->getSourceFieldValue('string', 'limit_max');
    }


    /**
     * Sets the limit_max for this connector
     *
     * @param int|null $limit_max
     * @return static
     */
    public function setLimitMax(?int $limit_max): static
    {
        return $this->setSourceValue('limit_max', $limit_max);
    }


    /**
     * Returns the auto_increment for this connector
     *
     * @return int|null
     */
    public function getAutoIncrement(): ?int
    {
        return $this->getSourceFieldValue('string', 'auto_increment');
    }


    /**
     * Sets the auto_increment for this connector
     *
     * @param int|null $auto_increment
     * @return static
     */
    public function setAutoIncrement(?int $auto_increment): static
    {
        return $this->setSourceValue('auto_increment', $auto_increment);
    }


    /**
     * Returns the ssh_tunnels_id for this connector
     *
     * @return int|null
     */
    public function getSshTunnelsId(): ?int
    {
        return $this->getSourceFieldValue('bool', 'ssh_tunnels_id');
    }


    /**
     * Sets the ssh_tunnels_id for this connector
     *
     * @param int|null $ssh_tunnels_id
     * @return static
     */
    public function setSshTunnelsId(int|null $ssh_tunnels_id): static
    {
        return $this->setSourceValue('ssh_tunnels_id', $ssh_tunnels_id);
    }


    /**
     * Returns the log for this connector
     *
     * @return bool|null
     */
    public function getLog(): ?bool
    {
        return $this->getSourceFieldValue('bool', 'log');
    }


    /**
     * Sets the log for this connector
     *
     * @param int|bool|null $log
     * @return static
     */
    public function setLog(int|bool|null $log): static
    {
        return $this->setSourceValue('log', (bool) $log);
    }


    /**
     * Returns the init for this connector
     *
     * @return bool|null
     */
    public function getInit(): ?bool
    {
        return $this->getSourceFieldValue('bool', 'init');
    }


    /**
     * Sets the init for this connector
     *
     * @param int|bool|null $init
     * @return static
     */
    public function setInit(int|bool|null $init): static
    {
        return $this->setSourceValue('init', (bool) $init);
    }


    /**
     * Returns the buffered for this connector
     *
     * @return bool|null
     */
    public function getBuffered(): ?bool
    {
        return $this->getSourceFieldValue('bool', 'buffered');
    }


    /**
     * Sets the buffered for this connector
     *
     * @param int|bool|null $buffered
     * @return static
     */
    public function setBuffered(int|bool|null $buffered): static
    {
        return $this->setSourceValue('buffered', (bool) $buffered);
    }


    /**
     * Returns the statistics for this connector
     *
     * @return bool|null
     */
    public function getStatistics(): ?bool
    {
        return $this->getSourceFieldValue('bool', 'statistics');
    }


    /**
     * Sets the statistics for this connector
     *
     * @param int|bool|null $statistics
     * @return static
     */
    public function setStatistics(int|bool|null $statistics): static
    {
        return $this->setSourceValue('statistics', (bool) $statistics);
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database
     *
     * This method also accepts DataEntry objects of the same class, in which case it will simply return the specified
     * object, as long as it exists in the database.
     *
     * If the DataEntry does not exist in the database, then this method will check if perhaps it exists as a
     * configuration entry. This requires DataEntry::$config_path to be set. DataEntries from configuration will be in
     * readonly mode automatically as they cannot be stored in the database.
     *
     * DataEntries from the database will also have their status checked. If the status is "deleted", then a
     * DataEntryDeletedException will be thrown
     *
     * @note The test to see if a DataEntry object exists in the database can be either DataEntry::isNew() or
     *       DataEntry::getId(), which should return a valid database id
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null $column
     * @param bool $meta_enabled
     * @param bool $force
     * @return static|null
     */
    public static function get(DataEntryInterface|string|int|null $identifier, ?string $column = null, bool $meta_enabled = false, bool $force = false): ?static
    {
        if (($column === 'id') or (($column === null) and is_numeric($identifier))) {
            if ($identifier < 0) {
                // Negative identifier is a configured connector!
                return Connectors::new()->load()->get($identifier);
            }
        }

        return parent::get($identifier, $column, $meta_enabled, $force);
    }


    /**
     * Connects to the database of this connector
     *
     * @param bool $use_database
     * @return $this
     * @throws UnsupportedException
     */
    public function connect(bool $use_database = true): static
    {
        switch ($this->getType()) {
            case 'sql':
                sql($this->getName())->connect($use_database);
                break;

            default:
                throw new UnsupportedException(tr('Non SQL connectors are not yet supported'));
        }

        return $this;
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
                ->setSize(4)
                ->setLabel(tr('Username')))
            ->addDefinition(DefinitionFactory::getPassword($this, 'password')
                ->setSize(4)
                ->setLabel(tr('Password')))
            ->addDefinition(DefinitionFactory::getVariable($this, 'database')
                ->setSize(4)
                ->setLabel(tr('Database')))
            ->addDefinition(Definition::new($this, 'mode')
                ->setLabel(tr('Mode'))
                ->setSize(3))
            ->addDefinition(Definition::new($this, 'pdo_attributes')
                ->setLabel(tr('PDO attributes'))
                ->setSize(3))
            ->addDefinition(Definition::new($this, 'character_set')
                ->setLabel(tr('Character set'))
                ->setSize(3))
            ->addDefinition(Definition::new($this, 'collate')
                ->setLabel(tr('Collate'))
                ->setSize(3))
            ->addDefinition(DefinitionFactory::getTimezonesId($this, 'timezones_id')
                ->setLabel(tr('Timezone'))
                ->setSize(2))
            ->addDefinition(DefinitionFactory::getNumber($this, 'auto_increment')
                ->setLabel(tr('Auto increment'))
                ->setInputType(InputTypeExtended::positiveInteger)
                ->setSize(2))
            ->addDefinition(DefinitionFactory::getNumber($this, 'limit_max')
                ->setLabel(tr('Maximum row limit'))
                ->setDefault(1_000_000)
                ->setInputType(InputTypeExtended::positiveInteger)
                ->setSize(2))
            ->addDefinition(Definition::new($this, 'ssh_tunnels_id')
                ->setLabel(tr('SSL Tunnel'))
                ->setSize(2))
            ->addDefinition(DefinitionFactory::getBoolean($this, 'log')
                ->setLabel(tr('Log'))
                ->setSize(1))
            ->addDefinition(DefinitionFactory::getBoolean($this, 'init')
                ->setLabel(tr('Initializes'))
                ->setSize(1))
            ->addDefinition(DefinitionFactory::getBoolean($this, 'buffered')
                ->setLabel(tr('Buffered'))
                ->setSize(1))
            ->addDefinition(DefinitionFactory::getBoolean($this, 'statistics')
                ->setLabel(tr('Statistics'))
                ->setSize(1))
            ->addDefinition(DefinitionFactory::getDescription($this));
    }
}