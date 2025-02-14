<?php

/**
 * Connector class
 *
 * This class represents a single SQL connector coming either from configuration or DB storage
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Connectors;

use PDO;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCharacterSet;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCollate;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryDatabase;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryHostnamePort;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryNameDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPassword;
use Phoundation\Data\DataEntries\Traits\TraitDataEntrySync;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryTimezone;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUsername;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Databases\Connectors\Exception\ConnectorNotExistsException;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Datastores;
use Phoundation\Databases\Interfaces\DatabaseInterface;
use Phoundation\Exception\PhpModuleNotAvailableException;
use Phoundation\Geo\Timezones\Timezone;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Json;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumInputType;


class Connector extends DataEntry implements ConnectorInterface
{
    use TraitDataEntryNameDescription;
    use TraitDataEntryHostnamePort;
    use TraitDataEntryUsername;
    use TraitDataEntryPassword;
    use TraitDataEntryDatabase;
    use TraitDataEntryTimezone;
    use TraitDataEntryCharacterSet;
    use TraitDataEntryCollate;
    use TraitDataEntrySync;


    /**
     * Tracks if this database should be backed up
     *
     * @var bool $backup
     */
    protected bool $backup = true;

    /**
     * The database for this connector
     *
     * @var DatabaseInterface|null $o_database
     */
    protected ?DatabaseInterface $o_database = null;


    /**
     * Connector class constructor
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     */
    public function __construct(IdentifierInterface|array|string|int|false|null $identifier = false)
    {
        $this->supports_seo_name     = false;
        $this->supports_seo_hostname = false;
        $this->connector             = 'system';

        parent::__construct($identifier);
    }


    /**
     * Returns a new DataEntry object
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     *
     * @return static
     */
    public static function new(IdentifierInterface|array|string|int|false|null $identifier = false): static
    {
        return new static($identifier);
    }


    /**
     * Returns the configuration path for this DataEntry object, if it has one, or NULL instead
     *
     * @return string|null
     */
    public static function getConfigurationPath(): ?string
    {
        return 'databases.connectors';
    }


    /**
     * @inheritDoc
     */
    public static function getTable(): ?string
    {
        return 'databases_connectors';
    }


    /**
     * @inheritDoc
     */
    public static function getEntryName(): string
    {
        return tr('SQL connector');
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueColumn(): ?string
    {
        return 'name';
    }


    /**
     * Returns the database for this Connector, if connected. Will return NULL if not connected
     *
     * @return DatabaseInterface|null
     */
    public function getDatabaseObject(): ?DatabaseInterface
    {
        return $this->o_database;
    }


    /**
     * Returns the database for this Connector, if connected. Will return NULL if not connected
     *
     * @param DatabaseInterface|null $o_database
     *
     * @return static
     */
    public function setDatabaseObject(?DatabaseInterface $o_database): static
    {
        $this->o_database = $o_database;
        return $this;
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
     * @param IdentifierInterface|array|string|int|null $identifier
     *
     * @return static
     */
    public function load(IdentifierInterface|array|string|int|null $identifier = null): static
    {
        if (is_numeric($identifier) and ($identifier < 0)) {
            // Negative identifier is a configured connector!
            return Connector::newFromSource(Connectors::new()->load()->get($identifier));
        }

        try {
            // Load connector data and automatically cache it in the Datastores object
            parent::load($identifier);

            // TODO $this->identifier['name'] should always exist for a connector, but what if someone specified $identifier['id'] ???
            Datastores::getConnectorsObject()->add($this, $this->identifier['name'], exception: false);
            return $this;

        } catch (DataEntryNotExistsException $e) {
            throw ConnectorNotExistsException::new(tr('The connector ":connector" does not exist', [
                ':connector' => $identifier,
            ]), $e);
        }
    }


    /**
     * Loads the data for the current identifier
     *
     * This Connector::loadIdentifier() overrides DataEntry::loadIdentifier. It will check if the current database is
     * connected and if not, immediately skip database access and use DataEntry::tryLoadFromConfiguration() instead
     *
     * @return static
     */
    protected function loadIdentifier(): static
    {
        if ($this->getDatabaseObject()?->isConnected()) {
            return parent::loadIdentifier();
        }

        // We don't have a database connection, so don't even try to use the normal database load!
        return $this->tryLoadFromConfiguration($this->identifier);
    }


    /**
     * Returns if the database for this connector should be backed up
     *
     * @return bool
     */
    public function getBackup(): bool
    {
        return $this->backup;
    }


    /**
     * Sets if the database for this connector should be backed up
     *
     * @param bool $backup
     *
     * @return static
     */
    public function setBackup(bool $backup): static
    {
        $this->backup = $backup;

        return $this;
    }


    /**
     * Returns the name for this user that can be displayed
     *
     * @return string|null
     */
    public function getDisplayName(): ?string
    {
        return $this->formatDisplayVariables($this->getLogId());
    }


    /**
     * Returns id for this database entry that can be used in logs
     *
     * @return string
     */
    public function getLogId(): string
    {
        return $this->getType() . ':' . $this->getUsername() . '@' . $this->getHostname() . '/' . $this->getDatabase();
    }

    /**
     * Returns the type for this connector
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->getTypesafe('string', 'type');
    }


    /**
     * Sets the type for this connector
     *
     * @param string|null $type
     *
     * @return static
     */
    public function setType(?string $type): static
    {
        return $this->set($type, 'type');
    }


    /**
     * Returns the driver for this connector
     *
     * @return string|null
     */
    public function getDriver(): ?string
    {
        return $this->getTypesafe('string', 'driver');
    }


    /**
     * Sets the driver for this connector
     *
     * @param string|null $driver
     *
     * @return static
     */
    public function setDriver(?string $driver): static
    {
        return $this->set($driver, 'driver');
    }


    /**
     * Returns the pdo_attributes for this connector
     *
     * @return array|null
     */
    public function getPdoAttributes(): ?array
    {
        return $this->getTypesafe('array', 'pdo_attributes');
    }


    /**
     * Sets the pdo_attributes for this connector
     *
     * @param array|string|null $pdo_attributes
     *
     * @return static
     */
    public function setPdoAttributes(array|string|null $pdo_attributes): static
    {
        if (is_string($pdo_attributes)) {
            if ($pdo_attributes) {
                $pdo_attributes = Json::decode($pdo_attributes);

            } else {
                $pdo_attributes = [];
            }
        }

        return $this->set($pdo_attributes, 'pdo_attributes');
    }


    /**
     * Returns the mode for this connector
     *
     * @return string|null
     */
    public function getMode(): ?string
    {
        return $this->getTypesafe('string', 'mode');
    }


    /**
     * Sets the mode for this connector
     *
     * @param string|null $mode
     *
     * @return static
     */
    public function setMode(?string $mode): static
    {
        return $this->set($mode, 'mode');
    }


    /**
     * Returns the limit_max for this connector
     *
     * @return int|null
     */
    public function getLimitMax(): ?int
    {
        return $this->getTypesafe('string', 'limit_max');
    }


    /**
     * Sets the limit_max for this connector
     *
     * @param int|null $limit_max
     *
     * @return static
     */
    public function setLimitMax(?int $limit_max): static
    {
        return $this->set($limit_max, 'limit_max');
    }


    /**
     * Returns the auto_increment for this connector
     *
     * @return int|null
     */
    public function getAutoIncrement(): ?int
    {
        return $this->getTypesafe('string', 'auto_increment');
    }


    /**
     * Sets the auto_increment for this connector
     *
     * @param int|null $auto_increment
     *
     * @return static
     */
    public function setAutoIncrement(?int $auto_increment): static
    {
        return $this->set($auto_increment, 'auto_increment');
    }


    /**
     * Returns the ssh_tunnels_id for this connector
     *
     * @return int|null
     */
    public function getSshTunnelsId(): ?int
    {
        return $this->getTypesafe('int', 'ssh_tunnels_id');
    }


    /**
     * Sets the ssh_tunnels_id for this connector
     *
     * @param int|null $ssh_tunnels_id
     *
     * @return static
     */
    public function setSshTunnelsId(int|null $ssh_tunnels_id): static
    {
        return $this->set($ssh_tunnels_id, 'ssh_tunnels_id');
    }


    /**
     * Returns the log flag for this connector
     *
     * @return bool|null
     */
    public function getLog(): ?bool
    {
        return $this->getTypesafe('bool', 'log');
    }


    /**
     * Sets the log flag for this connector
     *
     * @param int|bool|null $log
     *
     * @return static
     */
    public function setLog(int|bool|null $log): static
    {
        return $this->set((bool) $log, 'log');
    }


    /**
     * Returns the environment for this connector
     *
     * @return string|null
     */
    public function getEnvironment(): ?string
    {
        return $this->getTypesafe('string', 'environment');
    }


    /**
     * Sets the environment for this connector
     *
     * @param string|null $environment
     *
     * @return static
     */
    public function setEnvironment(string|null $environment): static
    {
        return $this->set($environment, 'environment');
    }


    /**
     * Returns the persist flag for this connector
     *
     * @return bool|null
     */
    public function getPersist(): ?bool
    {
        return $this->getTypesafe('bool', 'persist');
    }


    /**
     * Sets the persist flag for this connector
     *
     * @param int|bool|null $persist
     *
     * @return static
     */
    public function setPersist(int|bool|null $persist): static
    {
        return $this->set((bool) $persist, 'persist');
    }


    /**
     * Returns the init flag for this connector
     *
     * @return bool|null
     */
    public function getInit(): ?bool
    {
        return $this->getTypesafe('bool', 'init');
    }


    /**
     * Sets the init flag for this connector
     *
     * @param int|bool|null $init
     *
     * @return static
     */
    public function setInit(int|bool|null $init): static
    {
        return $this->set((bool) $init, 'init');
    }


    /**
     * Returns the buffered for this connector
     *
     * @return bool|null
     */
    public function getBuffered(): ?bool
    {
        return $this->getTypesafe('bool', 'buffered');
    }


    /**
     * Sets the buffered for this connector
     *
     * @param int|bool|null $buffered
     *
     * @return static
     */
    public function setBuffered(int|bool|null $buffered): static
    {
        return $this->set((bool) $buffered, 'buffered');
    }


    /**
     * Returns the statistics for this connector
     *
     * @return bool|null
     */
    public function getStatistics(): ?bool
    {
        return $this->getTypesafe('bool', 'statistics');
    }


    /**
     * Sets the statistics for this connector
     *
     * @param int|bool|null $statistics
     *
     * @return static
     */
    public function setStatistics(int|bool|null $statistics): static
    {
        return $this->set((bool) $statistics, 'statistics');
    }


    /**
     * Tests this connector by connecting to the database and executing a test query
     *
     * @return static
     */
    public function test(): static
    {
        Datastores::fromConnector($this)
                  ->test();

        return $this;
    }


    /**
     * Returns the connector configuration in an array that can be understood by the MySQL driver
     *
     * @return array
     */
    public function getMysqlConfiguration(): array
    {
        return $this->applyConfigurationTemplate($this->source);
    }


    /**
     * Returns the connector configuration in an array that can be understood by the Redis driver
     *
     * @return array
     */
    public function getRedisConfiguration(): array
    {
        return [
            'host'           => $this->getHostname(),
            'port'           => $this->getPort() ?? 6379,
            'options'        => null,
            'database'       => $this->getDatabase(),
            'timeout'        => 0,
            'persistent_id'  => null,
            'retry_interval' => 0,
            'read_timeout'   => 0,
            'context'        => [],
        ];
    }


    /**
     * Returns the connector configuration in an array that can be understood by the Memcached driver
     *
     * @return array
     */
    public function getMemcachedConfiguration(): array
    {
        return [
            'host'        => $this->getHostname(),
            'port'        => $this->getPort() ?? 11211,
            'options'     => null,
            'database'    => $this->getDatabase(),
            'connections' => []
        ];
    }


    /**
     * Apply configuration template over the specified configuration array
     *
     * @param array $configuration
     *
     * @return array
     */
    protected function applyConfigurationTemplate(array $configuration): array
    {
        // Copy the configuration options over the template
        $configuration = Arrays::mergeFull($this->getConfigurationTemplate(), $configuration);

        switch ($configuration['driver']) {
            case 'mysql':
                // Do we have a MySQL driver available?
                if (!defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
                    // Whelp, MySQL library is not available
                    throw new PhpModuleNotAvailableException('Could not find the "MySQL" library for PDO. To install this on Ubuntu derivatives, please type "sudo apt install php-mysql');
                }

                // Build up ATTR_INIT_COMMAND
                $command = 'SET @@SESSION.TIME_ZONE="+00:00"; ';

                if ($configuration['character_set']) {
                    // Set the default character set to use
                    $command .= 'SET NAMES ' . strtoupper($configuration['character_set'] . '; ');
                }

                // Apply MySQL specific requirements that always apply
                $configuration['pdo_attributes'][PDO::ATTR_ERRMODE]                  = PDO::ERRMODE_EXCEPTION;
                $configuration['pdo_attributes'][PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = !$configuration['buffered'];
                $configuration['pdo_attributes'][PDO::MYSQL_ATTR_INIT_COMMAND]       = $command;
                break;

            default:
                // Here be dragons!
                Log::warning(tr('Driver ":driver" is not supported', [
                    ':driver' => $configuration['driver'],
                ]));
        }

        return $configuration;
    }


    /**
     * Returns an SQL connection configuration template
     *
     * @return array
     */
    protected function getConfigurationTemplate(): array
    {
        return [
            'type'           => 'sql',
            'driver'         => 'mysql',
            'hostname'       => '127.0.0.1',
            'port'           => null,
            'database'       => '',
            'username'       => '',
            'password'       => '',
            'auto_increment' => 1,
            'init'           => false,
            'buffered'       => false,
            'character_set'  => 'utf8mb4',
            'collate'        => 'utf8mb4_general_ci',
            'limit_max'      => 10000,
            'mode'           => 'PIPES_AS_CONCAT,IGNORE_SPACE',
            'log'            => null,
            'statistics'     => null,
            'ssh_tunnel'     => [
                'required'    => false,
                'source_port' => null,
                'hostname'    => '',
                'usleep'      => 1200000,
            ],
            'pdo_attributes' => [],
            'version'        => '0.0.0',
            'timezones_name' => 'UTC',
        ];
    }


    /**
     * @inheritDoc
     */
    protected function setDefinitions(DefinitionsInterface $definitions): static
    {
        $definitions->add(DefinitionFactory::newName()
                                           ->setOptional(false)
                                           ->setSize(4)
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isUnique();
                                           }))

                    ->add(DefinitionFactory::newSeoName())

                    ->add(Definition::new('environment')
                                    ->setSize(4)
                                    ->setOptional(true)
                                    ->setLabel('Restrict to environment')
                                    ->setElement(EnumElement::select)
// TODO This datasource should list all available environments straight from the ROOT/config/environments path
                                    ->setDataSource([
                                        'production' => tr('Production'),
                                        'trial'      => tr('Trial'),
                                        'local'      => tr('Local'),
                                    ]))

                    ->add(DefinitionFactory::newVariable('type')
                                           ->setSize(4)
                                           ->setLabel('Connector type')
                                           ->setInputType(null)
                                           ->setElement(EnumElement::select)
                                           ->setDataSource([
                                               'sql'       => tr('SQL'),
                                               'memcached' => tr('Memcached'),
                                               'mongodb'   => tr('MongoDB'),
                                               'redis'     => tr('Redis'),
                                           ]))

                    ->add(DefinitionFactory::newVariable('driver')
                                           ->setSize(4)
                                           ->setLabel('Driver')
                                           ->setInputType(null)
                                           ->setElement(EnumElement::select)
                                           ->setDataSource([
                                               ''        => tr('Not specified'),
                                               'mysql'   => tr('MySQL'),
                                               'postgre' => tr('PostGRE'),
                                               'oracle'  => tr('Oracle'),
                                               'mssql'   => tr('MSSQL'),
                                           ]))

                    ->add(DefinitionFactory::newHostname('hostname')
                                           ->setInputType(EnumInputType::text)
                                           ->setOptional(true, 'localhost')
                                           ->setLabel(tr('Hostname'))
                                           ->setSize(8)
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isDomain();
                                           }))

                    ->add(DefinitionFactory::newNumber('port')
                                           ->setInputType(EnumInputType::positiveInteger)
                                           ->setOptional(true)
                                           ->setLabel(tr('Port'))
                                           ->setSize(4)
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isInteger()
                                                         ->isBetween(0, 65535);
                                           }))

                    ->add(DefinitionFactory::newVariable('username')
                                           ->setInputType(EnumInputType::text)
                                           ->setSize(4)
                                           ->setLabel(tr('Username'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isUsername();
                                           }))

                    ->add(DefinitionFactory::newPassword('password')
                                           ->setInputType(EnumInputType::password)
                                           ->setSize(4)
                                           ->setLabel(tr('Password'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isUsername();
                                           }))

                    ->add(DefinitionFactory::newVariable('database')
                                           ->setInputType(EnumInputType::variable)
                                           ->setSize(4)
                                           ->setLabel(tr('Database'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->hasMaxCharacters(64);
                                           }))

                    ->add(Definition::new('mode')
                                    ->setInputType(EnumInputType::text)
                                    ->setOptional(true, 'PIPES_AS_CONCAT,IGNORE_SPACE')
                                    ->setLabel(tr('Mode'))
                                    ->setSize(3)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->hasMaxCharacters(2048);
                                    }))

                    ->add(Definition::new('pdo_attributes')
                                    ->setInputType(EnumInputType::text)
                                    ->setOptional(true)
                                    ->setLabel(tr('PDO attributes'))
                                    ->setSize(3)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->hasMaxCharacters(2048);
                                    }))

                    ->add(Definition::new('character_set')
                                    ->setInputType(EnumInputType::text)
                                    ->setLabel(tr('Character set'))
                                    ->setOptional(true, config()->getString('databases.mysql.character-set', 'utf8mb4'))
                                    ->setSize(3)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
// TODO Improve validation of this column
                                        $validator->hasMaxCharacters(64);
                                    }))

                    ->add(Definition::new('collate')
                                    ->setInputType(EnumInputType::text)
                                    ->setLabel(tr('Collate'))
                                    ->setSize(3)
                                    ->setOptional(true, config()->getString('databases.mysql.collate', 'utf8mb4_general_ci'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
// TODO Improve validation of this column
                                        $validator->hasMaxCharacters(64);
                                    }))

                    ->add(DefinitionFactory::newTimezonesId())

                    ->add(DefinitionFactory::newTimezonesName())

                    ->add(DefinitionFactory::newTimezonesCode())

                    ->add(Definition::new('ssh_tunnels_id')
                                    ->setInputType(EnumInputType::dbid)
                                    ->setLabel(tr('SSL Tunnel'))
                                    ->setOptional(true)
                                    ->setDataSource([])
                                    ->setInputType(EnumInputType::select)
                                    ->setSize(2))

                    ->add(DefinitionFactory::newNumber('limit_max')
                                           ->setInputType(EnumInputType::positiveInteger)
                                           ->setOptional(true, 1_000_000)
                                           ->setLabel(tr('Maximum row limit'))
                                           ->setInputType(EnumInputType::positiveInteger)
                                           ->setSize(1)
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isLessThan(1_000_000_000);
                                           }))

                    ->add(DefinitionFactory::newNumber('auto_increment')
                                           ->setLabel(tr('Auto increment'))
                                           ->setInputType(EnumInputType::positiveInteger)
                                           ->setOptional(true, 1)
                                           ->setSize(1))

                    ->add(DefinitionFactory::newBoolean('persist')
                                           ->setLabel(tr('Persist'))
                                           ->setHelpText(tr('If enabled, Phoundation will use persistent connections. This may speed up database connections but may potentially cause your database to be overloaded with open connections'))
                                           ->setOptional(true, false)
                                           ->setSize(1))

                    ->add(DefinitionFactory::newBoolean('sync')
                                           ->setLabel(tr('Sync'))
                                           ->setHelpText(tr('If enabled, Phoundation will sync this database when executing the sync command'))
                                           ->setOptional(true, false)
                                           ->setSize(1))

                    ->add(DefinitionFactory::newBoolean('log')
                                           ->setLabel(tr('Log'))
                                           ->setHelpText(tr('If enabled, Phoundation will log all queries to this database'))
                                           ->setOptional(true, false)
                                           ->setSize(1))

                    ->add(DefinitionFactory::newBoolean('init')
                                           ->setLabel(tr('Initializes'))
                                           ->setHelpText(tr('If enabled, Phoundation will try to initialize this database during the init phase'))
                                           ->setOptional(true, false)
                                           ->setSize(1))

                    ->add(DefinitionFactory::newBoolean('buffered')
                                           ->setLabel(tr('Buffered'))
                                           ->setOptional(true, false)
                                           ->setSize(1))

                    ->add(DefinitionFactory::newBoolean('statistics')
                                           ->setLabel(tr('Statistics'))
                                           ->setOptional(true, false)
                                           ->setSize(1))

                    ->add(DefinitionFactory::newDescription());

        return $this;
    }
}
