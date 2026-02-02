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
use Phoundation\Data\DataEntries\Exception\DataEntryColumnsNotDefinedException;
use Phoundation\Data\DataEntries\Exception\DataEntryNoIdentifierSpecifiedException;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryArrayServers;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCharacterSet;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCollate;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryDatabase;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryHostnamePort;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryNameDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPassword;
use Phoundation\Data\DataEntries\Traits\TraitDataEntrySync;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUsername;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Databases\Connectors\Exception\ConnectorNotExistsException;
use Phoundation\Databases\Connectors\Exception\InvalidConnectorTypeException;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Databases;
use Phoundation\Databases\Interfaces\DatabaseInterface;
use Phoundation\Date\Enums\EnumDateFormat;
use Phoundation\Exception\PhpModuleNotAvailableException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumInputType;


class Connector extends DataEntry implements ConnectorInterface
{
    use TraitDataEntryNameDescription;
    use TraitDataEntryHostnamePort {
        getHostname as protected __getHostname;
    }
    use TraitDataEntryUsername;
    use TraitDataEntryPassword;
    use TraitDataEntryDatabase;
    use TraitDataEntryCharacterSet;
    use TraitDataEntryCollate;
    use TraitDataEntrySync;
    use TraitDataEntryArrayServers;


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
     * @param string|null                                     $database
     */
    public function __construct(IdentifierInterface|array|string|int|false|null $identifier = false, ?string $database = null)
    {
        if ($identifier === 'system') {
            $this->connector = 'system';

            $this->initializeVirtualConfiguration(['timezones' => ['name']])
                 ->setPermittedColumns('pdo_attributes')
                 ->connector = 'system';

            parent::__construct($identifier);

            $source = $this->loadFromConfiguration(static::getConfigurationPath(), 'system');

            $this->setSource($source)
                 ->setReadonly(true);

        } else {
            $this->initializeVirtualConfiguration(['timezones' => ['name']])
                 ->setPermittedColumns('pdo_attributes')
                ->connector = 'system';

            parent::__construct($identifier);

            if ($database) {
                $this->setDatabase($database);
            }
        }
    }


    /**
     * Returns a new DataEntry object
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     * @param string|null                                     $database
     *
     * @return static
     */
    public static function new(IdentifierInterface|array|string|int|false|null $identifier = false, ?string $database = null): static
    {
        return new static($identifier, $database);
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
     * @inheritDoc
     */
    public function getConnector(): string
    {
        return 'system';
    }


    /**
     * @inheritDoc
     */
    public function getConnectorObject(): static
    {
        return $this;
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
     * @param EnumLoadParameters|null                   $on_null_identifier
     * @param EnumLoadParameters|null                   $on_not_exists
     *
     * @return static|null
     */
    public function load(IdentifierInterface|array|string|int|null $identifier = null, ?EnumLoadParameters $on_null_identifier = null, ?EnumLoadParameters $on_not_exists = null): ?static
    {
        if (is_numeric($identifier) and ($identifier < 0)) {
            // Negative identifier is a configured connector!
            $this->setSource(Connectors::new()->load()->get($identifier));

        } else {
            try {
                // Load connector data and automatically and cache it in the Databases::Connectors object
                parent::load($identifier, $on_null_identifier, $on_not_exists);

            } catch (DataEntryNotExistsException $e) {
                throw ConnectorNotExistsException::new(tr('The connector ":connector" does not exist', [
                    ':connector' => $this->identifier,
                ]), $e);
            }
        }

        Databases::getConnectorsObject()->add($this, $this->getUniqueColumnValue(), exception: false);
        return $this;
    }


    /**
     * Loads the data for the current identifier
     *
     * This Connector::loadIdentifier() overrides DataEntry::loadIdentifier. It will check if the current database is
     * connected and if not, immediately skip database access and use DataEntry::tryLoadFromConfiguration() instead
     *
     * @param string $action
     *
     * @return static
     * @throws DataEntryNoIdentifierSpecifiedException
     */
    protected function loadIdentifier(string $action): static
    {
        if ($this->getDatabaseObject()?->isConnected()) {
            return parent::loadIdentifier($action);
        }

        // We do not have a database connected, so do not even try to use the normal database load!
        return $this->tryLoadFromConfiguration();
    }


    /**
     * Returns the hostname for this object
     *
     * @return string|null
     */
    public function getHostname(): ?string
    {
        $hostname = $this->__getHostname();

        if ($hostname === 'localhost') {
            // PDO has a weird quirk where it will ignore port settings when the host is localhost. 127.0.0.1 doesn't
            // seem to have this so force the use of that instead. This should also skip hostname lookups, as fast as
            // that would be
            $hostname = '127.0.0.1';
        }

        return $hostname;
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
     * Returns the unique identifier for this database entry, which will be the ID column if it doesn't have any
     *
     * @param bool $exception
     *
     * @return string|float|int|null
     */
    public function getUniqueColumnValue(bool $exception = true): string|float|int|null
    {
        return $this->getDisplayName();
    }


    /**
     * Returns id for this database entry that can be used in logs
     *
     * @return string
     */
    public function getLogId(): string
    {
        return $this->getName() . '[' . $this->getDriver() . ']:' . $this->getUsername() . '@' . $this->getHostname() . '/' . $this->getDatabase();
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
     * Returns true if the specified type is equal to the type of this connector
     *
     * @param string $driver
     *
     * @return bool
     */
    public function isDriver(string $driver): bool
    {
        return $this->getDriver() === $driver;
    }


    /**
     * Returns true if the specified type is equal to the type of this connector
     *
     * @param string $driver
     *
     * @return Connector
     */
    public function checkDriver(string $driver): static
    {
        if ($this->isDriver($driver)) {
            return $this;
        }

        throw new InvalidConnectorTypeException(tr('The ":type" drive of this connector does not match the required connector driver ":required"', [
            ':type'     => $this->getType(),
            ':required' => $driver,
        ]));
    }


    /**
     * Returns the attributes for this connector
     *
     * @return array|null
     */
    public function getAttributes(): ?array
    {
        return $this->getTypesafe('array', 'attributes');
    }


    /**
     * Sets the attributes for this connector
     *
     * @param array|string|null $attributes
     *
     * @return static
     */
    public function setAttributes(array|string|null $attributes): static
    {
        if (is_string($attributes)) {
            if ($attributes) {
                $attributes = Json::decode($attributes);

            } else {
                $attributes = [];
            }
        }

        return $this->set($attributes, 'attributes');
    }


    /**
     * Returns the connect timeout
     *
     * @return int|null
     */
    public function getConnectTimeout(): ?int
    {
        return $this->getTypesafe('int', 'connect_timeout', config()->getInteger('databases.mysql.timeouts.connect', 3));
    }


    /**
     * Sets the connect timeout
     *
     * @param int|null $connect_timeout
     *
     * @return static
     */
    public function setConnectTimeout(?int $connect_timeout): static
    {
        return $this->set($connect_timeout, 'connect_timeout');
    }


    /**
     * Returns the query timeout
     *
     * @return int|null
     */
    public function getQueryTimeout(): ?int
    {
        return $this->getTypesafe('int', 'query_timeout', config()->getInteger('databases.mysql.timeouts.query', 0));
    }


    /**
     * Sets the query timeout
     *
     * @param int|null $query_timeout
     *
     * @return static
     */
    public function setQueryTimeout(?int $query_timeout): static
    {
        return $this->set($query_timeout, 'query_timeout');
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
    public function getPersistent(): ?bool
    {
        return $this->getTypesafe('bool', 'persistent');
    }


    /**
     * Sets the persist flag for this connector
     *
     * @param int|bool|null $persist
     *
     * @return static
     */
    public function setPersistent(int|bool|null $persist): static
    {
        return $this->set((bool) $persist, 'persistent');
    }


    /**
     * Returns the timezones_name column
     *
     * @return string|null
     */
    public function getTimezonesName(): ?string
    {
        return $this->getTypesafe('string', 'timezones_name');
    }


    /**
     * Sets the timezones_name column
     *
     * @param string|null $timezones_name
     * @return static
     */
    public function setTimezonesName(?string $timezones_name): static
    {
        return $this->set($timezones_name, 'timezones_name');
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
        return $this->getTypesafe('bool', 'buffered', config()->getBoolean('databases.mysql.buffered', true));
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
        Databases::fromConnector($this)
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
            'expires'     => 86400,
            'servers'     => $this->getServers(),
        ];
    }


    /**
     * Apply configuration template over the specified configuration array
     *
     * @param array $configuration
     *
     * @return array
     *
     * @see https://www.php.net/manual/en/pdo.constants.php#pdo.constants.attr-persistent
     */
    protected function applyConfigurationTemplate(array $configuration): array
    {
        // Copy the configuration options over the template
        $configuration             = Arrays::mergeFull($this->getConfigurationTemplate(), $configuration);
        $configuration['hostname'] = $this->getHostname();

        switch ($configuration['driver']) {
            case 'mysql':
                // Is the MySQL driver available?
                if (!defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
                    // Whelp, MySQL library is not available
                    throw new PhpModuleNotAvailableException('Could not find the "MySQL" library for PDO. To install this on Ubuntu derivatives, please type "sudo apt install php-mysql');
                }

                // Build up ATTR_INIT_COMMAND
                $command = 'SET @@SESSION.TIME_ZONE="+00:00"; ';

                if ($configuration['character_set']) {
                    // Set the default character set to use
                    $command .= 'SET NAMES ' . strtoupper($configuration['character_set'] . '; ');

                    if ($this->getQueryTimeout()) {
                        $command .= 'SET SESSION wait_timeout=' . $this->getQueryTimeout() . ';';
                    }
                }

                // Ensure that all configured attributes are uppercase
                $configuration['attributes'] = Arrays::convertKeysToUppercase(array_get_safe($configuration, 'attributes', []));

                // Apply MySQL specific requirements that always apply
                $configuration['attributes']['PDO::MYSQL_ATTR_USE_BUFFERED_QUERY'] = (bool) $this->getBuffered();
                $configuration['attributes']['PDO::ATTR_PERSISTENT']               = (array_get_safe($configuration['attributes'], 'PDO::ATTR_PERSISTENT', false) or $configuration['persistent']);
                $configuration['attributes']['PDO::MYSQL_ATTR_INIT_COMMAND']       = $command;
                $configuration['attributes']['PDO::ATTR_ERRMODE']                  = PDO::ERRMODE_EXCEPTION;
                $configuration['attributes']['PDO::ATTR_CASE']                     = PDO::CASE_LOWER;

                if ($this->getConnectTimeout()) {
                    $configuration['attributes']['PDO::ATTR_TIMEOUT'] = $this->getConnectTimeout();
                }

                // Translate PDO attributes to their constants
                $configuration['attributes_translated'] = Arrays::convertKeysToConstants($configuration['attributes']);
                break;

            default:
                // Here be dragons!
                Log::warning(ts('Database driver ":driver" is not yet supported', [
                    ':driver' => $configuration['driver'],
                ]));
        }

        return $configuration;
    }


    /**
     * Returns an SQL connect configuration template
     *
     * @return array
     * @todo Get rid of this, the definitions should take care of all of this. SSH_TUNNEL should be an object
     */
    protected function getConfigurationTemplate(): array
    {
        return [
            'type'            => 'sql',
            'driver'          => EnumDateFormat::mysql_datetime,
            'hostname'        => '127.0.0.1',
            'port'            => null,
            'database'        => '',
            'username'        => '',
            'password'        => '',
            'auto_increment'  => 1,
            'connect_timeout' => null,
            'query_timeout'   => null,
            'init'            => false,
            'buffered'        => null,
            'persistent'      => false,
            'character_set'   => 'utf8mb4',
            'collate'         => 'utf8mb4_general_ci',
            'limit_max'       => 10000,
            'mode'            => 'PIPES_AS_CONCAT,IGNORE_SPACE',
            'log'             => null,
            'statistics'      => null,
            'ssh_tunnel'      => [
                'required'    => false,
                'source_port' => null,
                'hostname'    => '',
                'usleep'      => 1200000,
            ],
            'attributes'      => [],
            'version'         => '0.0.0',
            'timezones_name'  => 'UTC',
        ];
    }


    /**
     * Wrapper around DataEntryCore::copyValuesToSource() to clarify configuration exceptions
     *
     * @param array $source
     * @param bool  $modify
     * @param bool  $directly
     * @param bool  $force
     *
     * @return static
     */
    public function copyValuesToSource(array $source, bool $modify, bool $directly = false, bool $force = false): static
    {
        try {
            return parent::copyValuesToSource($source, $modify, $directly, $force); // TODO: Change the autogenerated stub

        } catch (DataEntryColumnsNotDefinedException $e) {
            if ($this->getIdentifier() === false) {
                throw ConnectorNotExistsException::new(tr('Cannot read specified connector source data, please check the configuration for this connector'), $e)
                                                 ->setData([
                                                     'source_data' => $source
                                                 ]);
            }

            throw new ConnectorNotExistsException(tr('Cannot read data for connector ":connector", please check the configuration for this connector', [
                ':connector' => $this->getIdentifier(),
            ]), $e);
        }
    }


    /**
     * @inheritDoc
     */
    protected function setDefinitionsObject(DefinitionsInterface $o_definitions): static
    {
        $o_definitions->add(DefinitionFactory::newName()
                                             ->setOptional(false)
                                             ->setSize(4)
                                             ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                               $o_validator->isUnique();
                                           }))

                      ->add(DefinitionFactory::newSeoName())

                      ->add(Definition::new('environment')
                                      ->setSize(4)
                                      ->setOptional(true)
                                      ->setLabel('Restrict to environment')
                                      ->setElement(EnumElement::select)
// TODO This datasource should list all available environments straight from the ROOT/config/environments path
                                      ->setSource([
                                          'production' => tr('Production'),
                                          'trial'      => tr('Trial'),
                                          'local'      => tr('Local'),
                                      ]))

                      ->add(DefinitionFactory::newVariable('type')
                                             ->setSize(4)
                                             ->setLabel('Connector type')
                                             ->setInputType(null)
                                             ->setElement(EnumElement::select)
                                             ->setSource([
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
                                             ->setSource([
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
                                             ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                                 $o_validator->isDomain();
                                             }))

                      ->add(DefinitionFactory::newNumber('port')
                                             ->setInputType(EnumInputType::positiveInteger)
                                             ->setOptional(true)
                                             ->setLabel(tr('Port'))
                                             ->setSize(4)
                                             ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                                 $o_validator->isInteger()
                                                             ->isBetween(0, 65535);
                                             }))

                      ->add(DefinitionFactory::newVariable('username')
                                             ->setInputType(EnumInputType::text)
                                             ->setSize(4)
                                             ->setLabel(tr('Username'))
                                             ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                                 $o_validator->isUsername();
                                             }))

                      ->add(DefinitionFactory::newPassword('password')
                                             ->setInputType(EnumInputType::password)
                                             ->setSize(4)
                                             ->setLabel(tr('Password'))
                                             ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                                 $o_validator->isUsername();
                                             }))

                      ->add(DefinitionFactory::newVariable('database')
                                             ->setInputType(EnumInputType::variable)
                                             ->setSize(4)
                                             ->setLabel(tr('Database'))
                                             ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                                 $o_validator->hasMaxCharacters(64);
                                             }))

                      ->add(DefinitionFactory::newNumber('connect_timeout')
                                             ->setInputType(EnumInputType::positiveInteger)
                                             ->setSize(2)
                                             ->setLabel(tr('Connect timeout'))
                                             ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                                 $o_validator->isInteger()->isBetween(0, 120);
                                             }))

                      ->add(DefinitionFactory::newNumber('query_timeout')
                                             ->setInputType(EnumInputType::positiveInteger)
                                             ->setSize(2)
                                             ->setLabel(tr('Query timeout'))
                                             ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                                 $o_validator->isInteger()->isBetween(0, 3600);
                                             }))

                      ->add(Definition::new('mode')
                                      ->setInputType(EnumInputType::text)
                                      ->setOptional(true, 'PIPES_AS_CONCAT,IGNORE_SPACE')
                                      ->setLabel(tr('Mode'))
                                      ->setSize(3)
                                      ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                          $o_validator->hasMaxCharacters(2048);
                                      }))

                      ->add(Definition::new('attributes')
                                      ->setInputType(EnumInputType::text)
                                      ->setOptional(true)
                                      ->setLabel(tr('Attributes'))
                                      ->setSize(3)
                                      ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                          $o_validator->hasMaxCharacters(2048);
                                      }))

                      ->add(Definition::new('character_set')
                                      ->setInputType(EnumInputType::text)
                                      ->setLabel(tr('Character set'))
                                      ->setOptional(true, config()->getString('databases.mysql.character-set', 'utf8mb4'))
                                      ->setSize(3)
                                      ->addValidationFunction(function (ValidatorInterface $o_validator) {
// TODO Improve validation of this column
                                          $o_validator->hasMaxCharacters(64);
                                      }))

                      ->add(Definition::new('collate')
                                      ->setInputType(EnumInputType::text)
                                      ->setLabel(tr('Collate'))
                                      ->setSize(3)
                                      ->setOptional(true, config()->getString('databases.mysql.collate', 'utf8mb4_general_ci'))
                                      ->addValidationFunction(function (ValidatorInterface $o_validator) {
// TODO Improve validation of this column
                                          $o_validator->hasMaxCharacters(64);
                                      }))

                      ->add(DefinitionFactory::newTimezonesName()
                                             ->setVirtual(false))

                      ->add(DefinitionFactory::newArray('servers')
                                             ->setLabel(tr('Servers'))
                                             ->setSize(12))

                      ->add(Definition::new('ssh_tunnels_id')
                                      ->setInputType(EnumInputType::dbid)
                                      ->setLabel(tr('SSL Tunnel'))
                                      ->setOptional(true)
                                      ->setSource([])
                                      ->setInputType(EnumInputType::select)
                                      ->setSize(2))

                      ->add(DefinitionFactory::newNumber('limit_max')
                                             ->setInputType(EnumInputType::positiveInteger)
                                             ->setOptional(true, 1_000_000)
                                             ->setLabel(tr('Maximum row limit'))
                                             ->setInputType(EnumInputType::positiveInteger)
                                             ->setSize(1)
                                             ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                                 $o_validator->isLessThan(1_000_000_000);
                                             }))

                      ->add(DefinitionFactory::newNumber('auto_increment')
                                             ->setLabel(tr('Auto increment'))
                                             ->setInputType(EnumInputType::positiveInteger)
                                             ->setOptional(true, 1)
                                             ->setSize(1))

                      ->add(DefinitionFactory::newBoolean('persistent')
                                             ->setLabel(tr('Persistent'))
                                             ->setHelpText(tr('If enabled, Phoundation will use persistent connects. This may speed up database connects but may potentially cause your database to be overloaded with open connects'))
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
                                             ->setOptional(true, true)
                                             ->setSize(1))

                      ->add(DefinitionFactory::newBoolean('statistics')
                                             ->setLabel(tr('Statistics'))
                                             ->setOptional(true, false)
                                             ->setSize(1))

                      ->add(DefinitionFactory::newDescription());

        return $this;
    }
}
