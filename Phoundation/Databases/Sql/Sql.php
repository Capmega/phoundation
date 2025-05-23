<?php

/**
 * Class Sql
 *
 * This class is the main SQL database access class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql;

use JetBrains\PhpStorm\NoReturn;
use PDO;
use PDOStatement;
use Phoundation\Cli\Cli;
use Phoundation\Cli\CliCommand;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\CoreReadonlyException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Meta;
use Phoundation\Core\Timers;
use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Databases\Connectors\Connectors;
use Phoundation\Databases\Connectors\Exception\ConnectorException;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Connectors\Interfaces\ConnectorsInterface;
use Phoundation\Databases\Exception\DatabaseTestException;
use Phoundation\Databases\Sql\Exception\SqlAccessDeniedException;
use Phoundation\Databases\Sql\Exception\SqlColumnDoesNotExistsException;
use Phoundation\Databases\Sql\Exception\SqlConnectException;
use Phoundation\Databases\Sql\Exception\SqlConnectionRefusedException;
use Phoundation\Databases\Sql\Exception\SqlContstraintDuplicateEntryException;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Exception\SqlInvalidConfigurationException;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;
use Phoundation\Databases\Sql\Exception\SqlNoDatabaseSelectedException;
use Phoundation\Databases\Sql\Exception\SqlNoTimezonesException;
use Phoundation\Databases\Sql\Exception\SqlConnectionTimedOutException;
use Phoundation\Databases\Sql\Exception\SqlSyntaxErrorException;
use Phoundation\Databases\Sql\Exception\SqlTableDoesNotExistException;
use Phoundation\Databases\Sql\Exception\SqlUnknownDatabaseException;
use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Databases\Sql\Interfaces\SqlQueryInterface;
use Phoundation\Databases\Sql\Schema\Interfaces\SchemaInterface;
use Phoundation\Databases\Sql\Schema\Schema;
use Phoundation\Date\PhoTime;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpModuleNotAvailableException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Servers\Servers;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Throwable;

class Sql implements SqlInterface
{
    use TraitDataConnector {
        setConnectorObject as protected __setConnectorObject;
    }


    /**
     * Dynamic database configurations
     *
     * @var array $configurations
     */
    protected static array $configurations = [];

    /**
     * SqlConnectors list
     *
     * @var ConnectorsInterface
     */
    protected static ConnectorsInterface $connectors;

    /**
     * All SQL database configuration
     *
     * @var array $configuration
     */
    protected array $configuration = [];

    /**
     * Registers what database is in use
     *
     * @var string|null $database
     */
    protected ?string $database = null;

    /**
     * The PDO database interface
     *
     * @var PDO|null $pdo
     */
    protected ?PDO $pdo = null;

    /**
     * Schema object to access SQL database schema
     *
     * @var Schema
     */
    protected Schema $schema;

    /**
     * Unique ID for this SQL connection
     *
     * @var string
     */
    protected string $uniqueid;

    /**
     * Sets if query logging enabled or disabled
     *
     * @var bool $debug
     */
    protected bool $debug = false;

    /**
     * Sets if statistics are enabled or disabled
     *
     * @var bool $statistics
     */
    protected bool $statistics = false;

    /**
     * Query counter
     *
     * @var int $counter
     */
    protected int $counter = 0;


    /**
     * Sql constructor
     *
     * @param ConnectorInterface $o_connector
     * @param bool               $use_database
     * @param bool               $connect
     *
     * @throws Throwable
     */
    public function __construct(ConnectorInterface $o_connector, bool $use_database = true, bool $connect = true)
    {
        $this->uniqueid = Strings::getRandom();

        // Connector specified directly. Take configuration from connector and connect
        $this->setConnectorObject($o_connector);

        // Some options can be configured separately as well
        $this->configuration['log']        = $this->configuration['log']        ?? config()->getBoolean('databases.sql.log'       , false);
        $this->configuration['statistics'] = $this->configuration['statistics'] ?? config()->getBoolean('databases.sql.statistics', false);

        $this->debug      = $this->configuration['log'] or config()->getBoolean('databases.sql.log', false);
        $this->statistics = $this->configuration['statistics'];

        if ($connect) {
            // Auto connect to the database
            $this->connect($use_database);
        }
    }


    /**
     * Returns true if this database interface is connected to a database server
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return (bool) $this->pdo;
    }


    /**
     * Returns the log prefix for this SQL connector
     *
     * @return string
     */
    public function getConnectorLogPrefix(): string
    {
        return '[SQL ' . ($this->connector ?? 'N/A') . ' / ' . $this->uniqueid . ' / ' . $this->getHostname() . ' / ' . ($this->getDatabase() ?? 'N/A') . ' / ' . $this->counter . '] ';
    }


    /**
     * Returns the name of the database that currently is in use by this database object
     *
     * @return string|null
     */
    public function getDatabase(): ?string
    {
        return $this->database;
    }


    /**
     * Returns the hostname of the server that this object is connected to
     *
     * @return string|null
     */
    public function getHostname(): ?string
    {
        return $this->configuration['hostname'];
    }


    /**
     * Sets the name of the database that currently is in use by this database object
     *
     * @param string|null $database
     * @param bool        $use
     *
     * @return Sql
     */
    public function setDatabase(?string $database, bool $use = false): static
    {
        $this->database = $database;

        return $this;
    }


    /**
     * Execute query and return only the first row
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     * @param bool                $meta_enabled
     *
     * @return array|null
     */
    public function getRow(string|PDOStatement $query, ?array $execute = null, bool $meta_enabled = true): ?array
    {
        $result = $this->query($query, $execute);

        switch ($result->rowCount()) {
            case 0:
                // No results. This is probably okay, but do check if the query was a select or show query, just to
                // be sure
                SqlQueries::checkShowSelect($query, $execute);

                return null;

            case 1:
                $return = $this->fetch($result);

                if ($meta_enabled) {
                    // Register this user reading the entry
                    if (isset($return['meta_id'])) {
                        if ($return['meta_id']) {
                            Meta::get($return['meta_id'])->action('read');
                        }
                    }
                }

                return $return;

            default:
                // Multiple results, this is always bad for a function that should only return one result!
                SqlQueries::checkShowSelect($query, $execute);
                throw SqlMultipleResultsException::new(tr('Failed for query ":query" to fetch single row, specified query result contains not 1 but ":count" results', [
                    ':count' => $result->rowCount(),
                    ':query' => SqlQueries::renderQueryString($result->queryString, $execute),
                ]))->setData([
                    'connector' => $this->connector,
                ]);
        }
    }


    /**
     * Returns the value for the specified key from the cache table
     *
     * @param string|float|int|null $key
     * @param callable|null         $cache_callback An optional callback function for read-through caching
     *
     * @return mixed
     */
    public function get(string|float|int|null $key, ?callable $cache_callback = null): mixed
    {
        return $this->getColumn('SELECT `value` FROM `core_cache` WHERE `key` = :key`');
    }


    /**
     * Sets the value for the specified key in the cache table
     *
     * @param mixed                 $value
     * @param string|float|int|null $key
     *
     * @return mixed
     * @see https://www.php.net/manual/en/memcached.set.php
     */
    public function set(mixed $value, string|float|int|null $key): static
    {
        return $this->insert('core_cache', ['key' => $key, 'value' => $value], ['value' => $value]);
    }


    /**
     * Parses and returns the query with the execute variables
     *
     * @param PDOStatement|SqlQueryInterface|string|null $query
     * @param array|null                                 $execute
     *
     * @return string|null
     */
    public function parseQuery(PDOStatement|SqlQueryInterface|string|null $query, ?array $execute = null): ?string
    {
        if (empty($query)) {
            return 'EMPTY QUERY';
        }

        if ($execute) {
            foreach ($execute as $key => $value) {
                $value = Strings::fromDatatype($value, '"');
                $query = str_replace($key, $value, $query);
            }
        }

        return Strings::ensureEndsWith($query, ';');
    }


    /**
     * Executes specified query and returns a PDOStatement object
     *
     * @param PDOStatement|SqlQueryInterface|string $query
     * @param array|null                            $execute
     *
     * @return PDOStatement
     * @throws SqlException
     */
    public function query(PDOStatement|SqlQueryInterface|string $query, ?array $execute = null): PDOStatement
    {
        $log = false;

        try {
            if (!trim($query)) {
                throw new SqlException(tr('Cannot execute empty query'));
            }

            if (!$this->pdo) {
                throw new SqlException(tr('Cannot execute query ":query", on instance ":instance", it is not connected to a server', [
                    ':query'    => $query,
                    ':instance' => $this->connector,
                ]));
            }

            $this->counter++;

            // PDO statement can be specified instead of a query?
            if (is_object($query)) {
                if ($this->debug or ($query->queryString[0] === ' ')) {
                    $log = true;
                }

                $timer = Timers::new('sql', static::getConnectorLogPrefix() . $query->queryString);

                // Are we going to write?
                SqlQueries::checkWriteAllowed($query->queryString);
                $query->execute($execute);

            } else {
                // Log query?
                if ($this->debug or ($query[0] === ' ')) {
                    $log = true;
                }

                $query = trim($query);

                if (empty($query)) {
                    throw new SqlException(tr('No query specified'));
                }

                $timer = Timers::new('sql', static::getConnectorLogPrefix() . $query);

                // Are we going to write?
                SqlQueries::checkWriteAllowed($query);

                if (empty($execute)) {
                    // Execute plain SQL query string. Only return ASSOC data.
                    $query = $this->pdo->query($query);
                    $query->setFetchMode(PDO::FETCH_ASSOC);

                } else {
                    // Execute the query with the specified $execute variables. Only return ASSOC data.
                    $query = $this->pdo->prepare($query);
                    $query->setFetchMode(PDO::FETCH_ASSOC);
                    $query->execute($execute);
                }
            }

            $timer->stop();

            // Log query
            if ($log) {
                Log::sql(static::getConnectorLogPrefix() . '[' . number_format($timer->getTotal() * 1000, 4) . ' ms] ' . $query->queryString, $execute);
            }

            if ($this->statistics) {
                // Get current function / file@line. If current function is actually an include then assume this is the
                // actual script that was executed by route()
                Debug::addStatistic()
                     ->setQuery(SqlQueries::show($query, $execute, true))
                     ->setTime($timer->getTotal());
            }

            return $query;

        } catch (Throwable $e) {
            // Failure is probably that one of the $execute array values is not scalar
            if (isset($timer)) {
                $timer->stop(true);
            }

            // Get exception message and SQL state
            if (str_starts_with($e->getMessage(), 'SQLSTATE')) {
                $state   = Strings::cut($e->getMessage(), 'SQLSTATE[', ']');
                $message = Strings::from($e->getMessage(), ':');
                $message = trim($message);

            } else {
                $state   = null;
                $message = $e->getMessage();
            }

            if ($e instanceof CoreReadonlyException) {
                throw $e;
            }

            // Get error data from PDO
            $error = $this->pdo?->errorInfo();

            $this->processQueryException(SqlException::new($e)
                                                     ->setHost($this->getHostname())
                                                     ->setDatabase($this->getDatabase())
                                                     ->setQuery($query)
                                                     ->setExecute($execute)
                                                     ->setMessage($message)
                                                     ->setSqlState(get_null($state ?? array_get_safe($error, 0)))
                                                     ->setDriverState(array_get_safe($error, 1)));
        }
    }


    /**
     * Prepare specified query
     *
     * @param string $query
     *
     * @return PDOStatement
     */
    public function prepare(string $query): PDOStatement
    {
        return $this->pdo->prepare($query);
    }


    /**
     * Process the specified query exception
     *
     * @param SqlException $e
     *
     * @return never
     */
    #[NoReturn] protected function processQueryException(SqlException $e): never
    {
        $query   = $e->getQuery();
        $execute = $e->getExecute();

        // Check the execution array for issues
        if ($query) {
            if ($execute) {
                foreach ($execute as $key => $value) {
                    if (!is_scalar($value) and !is_null($value)) {
                        // This is automatically a problem!
                        throw new SqlException(tr('The specified $execute array contains key ":key" with ":type" type value ":value"', [
                            ':key'   => $key,
                            ':type'  => gettype($value),
                            ':value' => $value,
                        ]), $e);
                    }
                }
            }

            if ($query instanceof PDOStatement) {
                $query = $query->queryString;
            }
        }
//            throw new SqlException(tr('(:uniqueid-:database) Sql query ":query" failed with ":e"', [
//                ':uniqueid' => $this->uniqueid,
//                ':database' => static::getDatabase(),
//                ':query'    => $query,
//                ':e'        => $e->getMessage()
//            ]), $e);

        // Check SQL state
        switch ($e->getSqlState()) {
            case 'denied':
                // no break

            case 'invalidforce':
                // Some database operation has failed
                foreach ($e->getMessages() as $message) {
                    Log::error(static::getConnectorLogPrefix() . $message);
                }
                exit(1);

            case '42S02':
                preg_match_all('/^Base table or view not found: 1146 Table \'(.+?)\' doesn\'t exist$/', $e->getMessage(), $matches);

                throw SqlTableDoesNotExistException::new(Strings::from($e->getMessage(), '1146'), $e)
                                                   ->addData(['table' => isset_get($matches[1][0])]);

            case '3D000':
                throw SqlNoDatabaseSelectedException::new(Strings::from($e->getMessage(), '1146'), $e);

            case 'HY093':
                // Invalid parameter number: number of bound variables does not match number of tokens
                // Get tokens from query
// TODO Check here what tokens do not match to make debugging easier
                preg_match_all('/:\w+/imus', $query, $matches);

                throw $e->addData(Arrays::renameKeys(Arrays::valueDiff($matches[0], array_keys($execute)), [
                    'add'    => 'variables missing in query',
                    'delete' => 'variables missing in execute',
                ]));

            case 23000:
                switch ($e->getSqlSecondaryState()) {
                    case 1062:
                        throw new SqlContstraintDuplicateEntryException($e);
                }

            case 42000:
                switch ($e->getDriverState()) {
                    case 1072:
                        $column = Strings::cut($e->getMessage(), "'", "'");

                        throw SqlColumnDoesNotExistsException::new(static::getConnectorLogPrefix() . tr('Key column ":column" does not exist in table', [
                            ':column' => $column
                        ]), $e)->addData([
                            'column'    => $column,
                            'database'  => $this->configuration['database']
                        ]);

                    case 1049:
                        throw SqlUnknownDatabaseException::new(static::getConnectorLogPrefix() . tr('Unknown database ":database"', [
                            ':database' => $this->configuration['database']
                        ]), $e)->addData([
                            'database'  => $this->configuration['database']
                        ]);
                }

                throw SqlSyntaxErrorException::new(static::getConnectorLogPrefix() . tr('Syntax error in query ":query" with connector ":connector"', [
                    ':query'     => $query,
                    ':connector' => $this->connector,
                ]), $e)->addData([
                    'query' => isset_get($matches[1][0])
                ]);

            default:
                throw $e->setCode($e->getSqlState());
//                switch (isset_get($error[1])) {
//                    case 1052:
//                        // Integrity constraint violation
//                        throw new SqlException(tr('Query ":query" contains an abiguous column', [
//                            ':query' => SqlQueries::buildQueryString($query, $execute, true)
//                        ]), $e);
//
//                    case 1054:
//                        $column = $e->getMessage();
//                        $column = Strings::from($column, "Unknown column");
//                        $column = Strings::from($column, "'");
//                        $column = Strings::until($column, "'");
//
//                        // Column not found
//                        throw SqlException::new(tr('Query ":query" refers to non existing column ":column"', [
//                            ':query'  => SqlQueries::buildQueryString($query, $execute, true),
//                            ':column' => $column
//                        ]), $e)->addData([':column' => $column]);
//
//                    case 1064:
//                        // Syntax error or access violation
//                        if (str_contains(strtoupper($query), 'DELIMITER')) {
//                            throw new SqlException(tr('Query ":query" contains the "DELIMITER" keyword. This keyword ONLY works in the MySQL console, and can NOT be used over MySQL drivers in PHP. Please remove this keword from the query', [
//                                ':query' => SqlQueries::buildQueryString($query, $execute, true)
//                            ]), $e);
//                        }
//
//                        throw SqlException::new(tr('Query ":query" has a syntax error: ":error"', [
//                            ':query' => SqlQueries::buildQueryString($query, $execute),
//                            ':error' => Strings::from($error[2], 'syntax; ')
//                        ], false), $e)->addData(['query' => $query, 'execute' => $execute]);
//
//                    case 1072:
//                        // Adding index error, index probably does not exist
//                        throw new SqlException(tr('Query ":query" failed with error 1072 with the message ":message"', [
//                            ':query'   => SqlQueries::buildQueryString($query, $execute, true),
//                            ':message' => isset_get($error[2])
//                        ]), $e);
//
//                    case 1005:
//                        // no break
//                    case 1217:
//                        // no break
//                    case 1452:
//                        // Foreign key error, get the FK error data from mysql
//                        try {
//                            $fk = $this->get('SHOW ENGINE INNODB STATUS');
//                            $fk = Strings::from($fk['Status'], 'LATEST FOREIGN KEY ERROR');
//                            $fk = Strings::from($fk, 'Foreign key constraint fails for');
//                            $fk = Strings::from($fk, ',');
//                            $fk = Strings::until($fk, 'DATA TUPLE');
//                            $fk = Strings::until($fk, 'Trying to');
//                            $fk = str_replace("\n", ' ', $fk);
//                            $fk = trim($fk);
//
//                        }catch (Exception $e) {
//                            throw new SqlException(tr('Query ":query" failed with error 1005, but another error was encountered while trying to obtain FK error data', [
//                                ':query' => SqlQueries::buildQueryString($query, $execute, true)
//                            ]), $e);
//                        }
//
//                        throw new SqlException(tr('Query ":query" failed with error 1005 with the message "Foreign key error on :message"', [
//                            ':query'   => SqlQueries::buildQueryString($query, $execute, true),
//                            ':message' => $fk
//                        ]), $e);
//
//                    case 1146:
//                        // Base table or view not found
//                        throw new SqlException(tr('Query ":query" refers to a base table or view that does not exist: :message', [
//                            ':query'   => SqlQueries::buildQueryString($query, $execute, true),
//                            ':message' => $e->getMessage()
//                        ]), $e);
//                }
        }
//        // Okay wut? Something went badly wrong
//        global $argv;
//
//        Notification::new()
//            ->setUrl(Url::new('security/incidents.html')->makeWww())
//            ->setMode(EnumDisplayMode::exception)
//            ->setCode('SQL_QUERY_ERROR')->setRoles('developer')->setTitle('SQL Query error')->setMessage('
//                SQL STATE ERROR : "' . $error[0] . '"
//                DRIVER ERROR    : "' . $error[1] . '"
//                ERROR MESSAGE   : "' . $error[2] . '"
//                query           : "' . Strings::Log(SqlQueries::buildQueryString($query, $execute, true)) . '"
//                date            : "' . date('Y-m-d H:i:s'))
//            ->setDetails([
//                '$argv'     => $argv,
//                '$_GET'     => $_GET,
//                '$_POST'    => $_POST,
//                '$_SERVER'  => $_SERVER,
//                '$query'    => [$query],
//                '$_SESSION' => $_SESSION
//            ])
//            ->log()
//            ->send();
//
//        throw SqlException::new(static::getLogPrefix() . tr('Query ":query" failed with ":messages"', [
//            ':query'    => SqlQueries::buildQueryString($query, $execute),
//            ':messages' => $e->getMessage(),
//        ]), $e)->setCode(isset_get($error[1]));
    }


    /**
     * Fetch data with default PDO::FETCH_ASSOC instead of PDO::FETCH_BOTH
     *
     * @param PDOStatement $resource
     * @param int          $fetch_style
     *
     * @return array|null
     */
    public function fetch(PDOStatement $resource, int $fetch_style = PDO::FETCH_ASSOC): ?array
    {
        $result = $resource->fetch($fetch_style);

        if ($result === false) {
            // There are no entries
            return null;
        }

        // Return data
        return $result;
    }


    /**
     * Returns a float variable from the SQL database
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     * @param string|null         $column
     *
     * @return bool|null
     */
    public function getBoolean(string|PDOStatement $query, ?array $execute = null, ?string $column = null): bool|null
    {
        $result = static::getColumn($query, $execute, $column);

        if ($result === null) {
            // Not found
            return null;
        }

        return Strings::toBoolean($result);
    }


    /**
     * Get the value of a single column from a single row for the specified query
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     * @param string|null         $column
     *
     * @return string|float|int|bool|null
     */
    public function getColumn(string|PDOStatement $query, ?array $execute = null, ?string $column = null): string|float|int|bool|null
    {
        $result = $this->getRow($query, $execute);

        if (!$result) {
            // No results
            return null;
        }

        if ($column) {
            // Column was specified, so we can process multiple columns in the results
            if (array_key_exists($column, $result)) {
                return $result[$column];
            }

            // Specified column doesn't exist
            throw new SqlColumnDoesNotExistsException(tr('Cannot return column ":column", it does not exist in the result set for query ":query"', [
                ':query'  => $query,
                ':column' => $column,
            ]));

        } else {
            // No column was specified, so we MUST have received only one column!
            if (count($result) > 1) {
                // The query returned multiple columns
                throw SqlException::new(tr('The query ":query" returned ":count" columns while Sql::\getColumn() without $column specification can only select and return one single column', [
                    ':query' => $query,
                    ':count' => count($result),
                ]))->addData([
                    'result'  => $result,
                    'columns' => array_keys($result)
                ]);
            }

            return Arrays::firstValue($result);
        }
    }


    /**
     * Connect to the database and do a DB version check.
     * If the database was already connected, then ignore and continue.
     * If the database version check fails, then exception
     *
     * @param bool $use_database
     *
     * @return static
     */
    protected function connect(bool $use_database = true): static
    {
        try {
            if ($this->pdo) {
                // Already connected to requested DB
                return $this;
            }

            // Does this connector require an SSH tunnel?
            if (isset_get($this->configuration['ssh_tunnel']['required'])) {
                $this->sshTunnel();
            }

            try {
                // Ensure the required fields are all available
                Arrays::ensureKeys($this->configuration, 'driver,hostname,username,password', true);

            } catch (OutOfBoundsException $e) {
                throw SqlInvalidConfigurationException::new(static::getConnectorLogPrefix() . tr('Cannot connect to SQL database, the connector configuration is missing required fields. See data for more information'), $e);
            }

            // Connect!
            $retries = 7;
            $start   = microtime(true);

            while (--$retries >= 0) {
                try {
                    $connect_string  = $this->configuration['driver'] . ':host=' . $this->configuration['hostname'] . (empty($this->configuration['port']) ? '' : ';port=' . $this->configuration['port']) . (($use_database and $this->configuration['database']) ? ';dbname=' . $this->configuration['database'] : '');
                    $this->pdo       = new PDO($connect_string, $this->configuration['username'], $this->configuration['password'], Arrays::force($this->configuration['attributes_translated']));

                    // Add this database object to the connector so that it can always be accessed through the connector
                    $this->o_connector->setDatabaseObject($this);
                    break;

                } catch (Throwable $e) {
                    if (!$this->configuration['hostname']) {
                        throw new SqlInvalidConfigurationException(static::getConnectorLogPrefix() . tr('Failed to connect to database connector ":connector" with connection string ":string" and user ":user", the database configuration is invalid', [
                                ':connector' => $this->connector,
                                ':string'    => isset_get($connect_string),
                                ':user'      => $this->configuration['username'],
                            ]));
                    }

                    switch ($e->getCode()) {
                        case 1045:
                            // Access denied!
                            throw SqlAccessDeniedException::new(static::getConnectorLogPrefix() . tr('Failed to connect to database connector ":connector" with connection string ":string" and user ":user", access was denied by the database server. Please check server username and password', [
                                ':connector' => $this->connector,
                                ':string'    => isset_get($connect_string),
                                ':user'      => $this->configuration['username'],
                            ]));

                        case 1049:
                            // The currently selected database doesn't exist!
                            preg_match_all('/^SQLSTATE\[HY000] \[1049] Unknown database \'(.+?)\'$/', $e->getMessage(), $matches);

                            throw SqlUnknownDatabaseException::new(static::getConnectorLogPrefix() . tr('Unknown database ":database" while connecting with connector ":connector" with connection string ":string" and user ":user"', [
                                ':connector' => $this->connector,
                                ':string'    => isset_get($connect_string),
                                ':database'  => $this->configuration['database'],
                                ':user'      => $this->configuration['username'],
                            ]))->addData(['database' => isset_get($matches[1][0])]);

                        case 2002:
                            if (str_contains(strtolower($e->getMessage()), 'connection timed out')) {
                                // Database service not available, connection refused!
                                throw SqlConnectionTimedOutException::new(static::getConnectorLogPrefix() . tr('Failed to connect to database connector ":connector" with connection string ":string" and user ":user" because the connection timed out after ":time". The database server may be down, the port might be blocked by a firewall, or the port configuration may be incorrect', [
                                                                              ':time'      => PhoTime::difference($start, microtime(true), 'auto', 5),
                                                                              ':connector' => $this->connector,
                                                                              ':string'    => isset_get($connect_string),
                                                                              ':user'      => $this->configuration['username'],
                                                                          ]))->addData([
                                                                              'configuration' => $this->configuration
                                                                          ]);
                            }

                            // Database service not available, connection refused!
                            throw SqlConnectionRefusedException::new(static::getConnectorLogPrefix() . tr('Failed to connect to database connector ":connector" with connection string ":string" and user ":user" because the connection was refused. The database server may be down, or the configuration may be incorrect', [
                                                                         ':connector' => $this->connector,
                                                                         ':string'    => isset_get($connect_string),
                                                                         ':user'      => $this->configuration['username'],
                                                                     ]))->addData([
                                                                         'configuration' => $this->configuration
                                                                     ]);
                    }

                    if ($e->getMessage() == 'could not find driver') {
                        if ($this->configuration['driver']) {
                            throw new PhpModuleNotAvailableException(tr('Failed to connect with ":driver" driver from connector ":connector", it looks like its not available', [
                                ':connector' => $this->connector,
                                ':driver'    => $this->configuration['driver'],
                            ]));
                        }

                        throw new PhpModuleNotAvailableException(tr('Failed to connect connector ":connector", it has no SQL driver specified', [
                            ':connector' => $this->connector,
                        ]));
                    }

                    Log::error(static::getConnectorLogPrefix() . tr('Failed to connect to instance ":connector" with PDO connect string ":string", error follows below', [
                            ':connector' => $this->connector,
                            ':string'    => isset_get($connect_string),
                    ]));

                    Log::error($e);

                    $message = $e->getMessage();

                    if (!str_contains($message, 'errno=32')) {
                        if ($e->getMessage() == 'ERROR 2013 (HY000): Lost connection to MySQL server at \'reading initial communication packet\', system error: 0') {
                            if (isset_get($this->configuration['ssh_tunnel']['required'])) {
                                // The tunneling server has "AllowTcpForwarding" set to "no" in the sshd_config, attempt
                                // auto fix
                                CliCommand::new($this->configuration['server'])
                                          ->enableTcpForwarding($this->configuration['ssh_tunnel']['server']);
                                continue;
                            }
                        }

                        // Continue throwing the exception as normal, we'll retry to connect!
                        throw new SqlConnectException(tr('Failed to connect to SQL connector ":connector"', [
                            ':connector' => $this->connector,
                        ]), $e);
                    }

                    /*
                     * This is a workaround for the weird PHP MySQL error "PDO::__construct(): send of 5 bytes failed
                     * with errno=32 Broken pipe". So far we have not been able to find a fix for this, but we have
                     * noted that you always have to connect 3 times, and the 3rd time the bug magically disappears. The
                     * workaround will detect the error and retry up to 3 times to work around this issue for now.
                     *
                     * Over time, it has appeared that the cause of this issue may be that MySQL is chewing on a huge
                     * and slow query which prevents it from accepting new connections. This is not confirmed yet, but
                     * very likely. Either way, this "fix" still fixes the issue..
                     *
                     * This error seems to happen when MySQL is VERY busy processing queries. Wait a little before
                     * trying again
                     */
                    usleep(100000);
                }
            }

            Log::success(static::getConnectorLogPrefix() . ts('Connected to database with PDO connect string ":connect" in ":time"', [
                ':connect' => $connect_string,
                ':time'    => PhoTime::difference($start, microtime(true), 'auto', 5),
            ]), 4);

            Log::printr($this->configuration['attributes'], 2, echo_header: false);

            // Yay, we're using the database!
            $this->database = $this->configuration['database'];

            if ($this->configuration['timezones_name']) {
                // Try to set MySQL timezone
                try {
                    $this->pdo->query('SET TIME_ZONE="' . $this->configuration['timezones_name'] . '";');

                } catch (Throwable $e) {
                    Log::warning(static::getConnectorLogPrefix() . tr('Failed to set timezone ":timezone" for database connector ":connector" with error ":e"', [
                        ':timezone'  => $this->configuration['timezones_name'],
                        ':connector' => $this->connector,
                        ':e'         => $e->getMessage(),
                    ]));

                    if (!Core::readRegister('no_time_zone') and (Core::isExecutedPath('system/init'))) {
                        throw $e;
                    }

                    // Indicate that time_zone settings failed (this will subsequently be used by the init system to
                    // automatically initialize that as well)
                    // TODO Write somewhere else than Core "system" register as that will be readonly
                    throw SqlNoTimezonesException::new(tr('Failed to set timezone ":timezone" on connector ":connector", MySQL has not yet loaded any timezones', [
                        ':connector' => $this->connector,
                        ':timezone'  => $this->configuration['timezones_name'],
                    ]), $e)->addData([
                        'connector' => $this->connector,
                    ]);
                }
            }

            if (!empty($this->configuration['mode'])) {
                $this->pdo->query('SET sql_mode="' . $this->configuration['mode'] . '";');
            }

        } catch (SqlAccessDeniedException $e) {
            throw $e;

        } catch (Throwable $e) {
            if (PLATFORM_CLI) {
                switch (CliCommand::getExecutedPath()) {
                    case 'system/init/drop':
                        // no break

                    case 'system/init/init':
                        // This is not an issue, we're either dropping DB or initializing it.
                        $this->connect(false);

                        return $this;
                }
            }

            // We failed to use the specified database, oh noes!
            switch ($e->getCode()) {
                case 1044:
                    // Access to database denied
                    throw new SqlException(tr('Cannot access database ":db", this user has no access to it', [
                        ':db' => $this->configuration['database'],
                    ]), $e);

                case 1049:
                    throw new SqlException(tr('Cannot use database ":db", it does not exist', [
                        ':db' => $this->configuration['database'],
                    ]), $e);

                case 2002:
                    // Connection refused
                    if (empty($this->configuration['ssh_tunnel']['required'])) {
                        throw new SqlException(tr('Connection refused for host ":hostname::port"', [
                            ':hostname' => $this->configuration['hostname'],
                            ':port'     => $this->configuration['port'],
                        ]), $e);
                    }

                    // This connection requires an SSH tunnel. Check if the tunnel process still exists
                    if (!Cli::PidGrep($tunnel['pid'])) {
                        $restrictions = servers_get($this->configuration['ssh_tunnel']['domain']);
                        $registered   = ssh_host_is_known($restrictions['hostname'], $restrictions['port']);

                        if ($registered === false) {
                            throw new SqlException(tr('Connection refused for host ":hostname" because the tunnel process was canceled due to missing server fingerprints in the DIRECTORY_ROOT/data/ssh/known_hosts file and `ssh_fingerprints` table. Please register the server first', [
                                ':hostname' => $this->configuration['ssh_tunnel']['domain'],
                            ]), $e);
                        }

                        if ($registered === true) {
                            throw new SqlException(tr('Connection refused for host ":hostname" on local port ":port" because the tunnel process either started too late or already died. The server has its SSH fingerprints registered in the DIRECTORY_ROOT/data/ssh/known_hosts file.', [
                                ':hostname' => $this->configuration['ssh_tunnel']['domain'],
                                ':port'     => $this->configuration['port'],
                            ]), $e);
                        }

                        // The server was not registerd in the DIRECTORY_ROOT/data/ssh/known_hosts file, but was registered in the
                        // ssh_fingerprints table, and automatically updated. Retry to connect
                        $this->connect();

                        return $this;
                    }

//:TODO: SSH to the server and check if the msyql process is up!
                    throw new SqlException(tr('sql_connect(): Connection refused for SSH tunnel requiring host ":hostname::port". The tunnel process is available, maybe the MySQL on the target server is down?', [
                        ':hostname' => $this->configuration['hostname'],
                        ':port'     => $this->configuration['port'],
                    ]), $e);

                case 2006:
                    /*
                     * MySQL server went away
                     *
                     * Check if tunnel PID is still there
                     * Check if target server supports TCP forwarding.
                     * Check if the tunnel is still responding to TCP requests
                     */
                    if (empty($this->configuration['ssh_tunnel']['required'])) {
                        /*
                         * No SSH tunnel was required for this connector
                         */
                        throw $e;
                    }

                    $restrictions = Servers::get($this->configuration['ssh_tunnel']['domain']);
                    $allowed      = Cli::getSshTcpForwarding($restrictions);

                    if (!$allowed) {
                        /*
                         * SSH tunnel is required for this connector, but tcp fowarding
                         * is not allowed. Allow it and retry
                         */
                        if (!$restrictions['allow_sshd_modification']) {
                            throw new SqlException(tr('Connector ":connector" requires SSH tunnel to server, but that server does not allow TCP fowarding, nor does it allow auto modification of its SSH server configuration', [':connector' => $this->configuration]));
                        }

                        Log::warning(static::getConnectorLogPrefix() . tr('Connector ":connector" requires SSH tunnel to server ":server", but that server does not allow TCP fowarding. Server allows SSH server configuration modification, attempting to resolve issue', [':server' => $this->configuration['ssh_tunnel']['domain']]));

                        // Now enable TCP forwarding on the server, and retry connection
                        linux_set_ssh_tcp_forwarding($restrictions, true);
                        Log::warning(static::getConnectorLogPrefix() . tr('Enabled TCP fowarding for server ":server", trying to reconnect to MySQL database', [':server' => $this->configuration['ssh_tunnel']['domain']]));

                        if ($this->configuration['ssh_tunnel']['pid']) {
                            Log::warning(static::getConnectorLogPrefix() . tr('Closing previously opened SSH tunnel to server ":server"', [':server' => $this->configuration['ssh_tunnel']['domain']]));
                            Ssh::closeTunnel($this->configuration['ssh_tunnel']['pid']);
                        }

                        $this->connect();
                    }

                    // Check if the tunnel process is still up and about
                    if (!Cli::Pid($this->configuration['ssh_tunnel']['pid'])) {
                        throw new SqlException(tr('SSH tunnel process ":pid" is gone', [':pid' => $this->configuration['ssh_tunnel']['pid']]));
                    }

                    // Check if we can connect over the tunnel to the remote SSH
                    $results = Inet::telnet([
                        'host' => '127.0.0.1',
                        'port' => $this->configuration['ssh_tunnel']['source_port'],
                    ]);
// :TODO: Implement further error handling.. From here on, appearently inet_telnet() did NOT cause an exception, so we have a result.. We can check the result for mysql server data and with that confirm that it is working, but what would.. well, cause a problem, because if everything worked we would not be here...
                default:
                    throw $e;
            }
        }

        return $this;
    }


    /**
     * @return void
     */
    protected function sshTunnel(): void {}


    /**
     * Returns the SqlConnectors instance
     *
     * @return ConnectorsInterface
     */
    public static function getConnectors(): ConnectorsInterface
    {
        if (empty(static::$connectors)) {
            static::$connectors = Connectors::new()
                                            ->load();
        }

        return static::$connectors;
    }


    /**
     * Add the configuration for the specified instance name
     *
     * @param string $connector
     * @param array  $configuration
     *
     * @return void
     */
    public static function addConnector(string $connector, array $configuration): void
    {
        static::$configurations[$connector] = $configuration;
    }


    /**
     * Returns the configuration for this SQL object
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }


    /**
     * Returns if query printing is enabled for this instance or not
     *
     * @return bool
     */
    public function getDebug(): bool
    {
        return $this->debug;
    }


    /**
     * Sets if query printing is enabled for this instance or not
     *
     * @param bool $debug
     *
     * @return static
     */
    public function setDebug(bool $debug): static
    {
        $this->debug = $debug;

        return $this;
    }


    /**
     * Returns if query statistics are enabled for this instance or not
     *
     * @return bool
     */
    public function getStatistics(): bool
    {
        return $this->statistics;
    }


    /**
     * Sets  if query statistics are enabled for this instance or not
     *
     * @param bool $statistics
     *
     * @return static
     */
    public function setStatistics(bool $statistics): static
    {
        $this->statistics = $statistics;

        return $this;
    }


    /**
     * Sets the database connector
     *
     * @note  If the specified $o_connector is NULL, it will be ignored
     * @param ConnectorInterface|null $o_connector
     * @param string|null             $database
     *
     * @return static
     */
    public function setConnectorObject(?ConnectorInterface $o_connector = null, ?string $database = null): static
    {
        if ($this->isConnected()) {
            throw new ConnectorException(tr('Cannot set connector ":connector", the database object ":database" is already connected', [
                ':connector' => $o_connector->getLogId(),
                ':database'  => $this->getConnectorLogPrefix()
            ]));
        }

        $this->configuration = $o_connector->getMysqlConfiguration();

        return $this->__setConnectorObject($o_connector, $database);
    }


    /**
     * Clears schema cache and returns a new SQL schema object for this instance
     *
     * @param bool $use_database
     *
     * @return SchemaInterface
     */
    public function resetSchema(bool $use_database = true): SchemaInterface
    {
        unset($this->schema);

        return $this->getSchemaObject($use_database);
    }


    /**
     * Returns an SQL schema object for this instance
     *
     * @param bool $use_database
     *
     * @return SchemaInterface
     */
    public function getSchemaObject(bool $use_database = true): SchemaInterface
    {
        if (empty($this->schema)) {
            $this->schema = new Schema($this->connector, $use_database);
        }

        return $this->schema;
    }


    /**
     * Use the specified database
     *
     * @param string|true|null $database The database to use. If none was specified, the configured system database will
     *                                   be used
     *
     * @return static
     * @throws SqlException
     */
    public function use(string|true|null $database = null): static
    {
        if ($database === true) {
            $database = $this->configuration['database'];
        }

        if (empty($database)) {
            $this->database = null;

            return $this;
        }

        $database = $this->getDatabaseName($database);

        if ($database === $this->getCurrentDatabase()) {
            // We're already using this database, no need to switch
            return $this;
        }

        Log::action(ts('(:uniqueid) Using database ":database"', [
            ':uniqueid' => $this->uniqueid,
            ':database' => $database,
        ]));

        try {
            $this->pdo->query('USE `' . $database . '`');
            $this->database = $database;

            return $this;

        } catch (Throwable $e) {
            // We failed to use the specified database, oh noes!
            switch ($e->getCode()) {
                case 1044:
                    // Access to database denied
                    throw new SqlException(tr('Cannot access database ":db", this user has no access to it', [
                        ':db' => $database,
                    ]), $e);

                case 1049:
                    throw new SqlException(tr('Cannot use database ":db", it does not exist', [
                        ':db' => $this->configuration['database'],
                    ]), $e);
            }

            throw new SqlException($e);
        }
    }


    /**
     * Returns the specified database name or the configured system database name
     *
     * @param string|null $database
     *
     * @return string
     */
    protected function getDatabaseName(?string $database): string
    {
        if ($database) {
            return $database;
        }

        return $this->configuration['database'];
    }


    /**
     * Insert the specified data row in the specified table, with "on dulplicate update" option
     *
     * This is a simplified insert method to speed up writing basic insert queries
     *
     * @note : PDO::lastInsertId() returns string|false, this method will return int
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     *
     * @param string            $table
     * @param array             $data
     * @param array|string|null $update
     *
     * @return Sql
     */
    public function insert(string $table, array $data, array|string|null $update = null): static
    {
        Core::checkReadonly('sql insert');

        $columns = SqlQueries::getPrefixedColumns($data);
        $values  = SqlQueries::getBoundValues($data);

        if ($update) {
            // Build bound variables for the query
            if (is_array($update)) {
                $data = array_merge($data, $update);
            }

            $keys   = SqlQueries::getBoundKeys($data);
            $update = SqlQueries::getUpdateKeyValues($update);

            $this->query('INSERT INTO            `' . $table . '` (' . $columns . ') 
                          VALUES                                  (' . $keys . ') 
                          ON DUPLICATE KEY UPDATE ' . $update, $values);

        } else {
            // Build bound variables for query
            $keys = SqlQueries::getBoundKeys($data);
            $this->query('INSERT INTO `' . $table . '` (' . $columns . ') VALUES (' . $keys . ')', $values);
        }

        return $this;
    }


    /**
     * Update the status for the specified data row in the specified table
     *
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     *
     * @param string      $table
     * @param string|null $status
     * @param array|null  $where
     *
     * @return Sql The number of rows that were updated
     */
    public function setStatus(string $table, ?string $status, array|null $where = null): static
    {
        return $this->update($table, ['status' => $status], $where);
    }


    /**
     * Update the specified data row in the specified table
     *
     * This is a simplified update method to speed up writing basic insert queries
     *
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     *
     * @param string     $table
     * @param array      $set
     * @param array|null $where
     *
     * @return Sql The number of rows that were updated
     */
    public function update(string $table, array $set, array|null $where = null): static
    {
        Core::checkReadonly('sql update');

        // Build bound variables for the query
        $values    = SqlQueries::getBoundValues(array_merge($set, Arrays::force($where)));
        $update    = SqlQueries::getUpdateKeyValues($set);
        $where     = SqlQueries::whereColumns($where);
        $statement = $this->query('UPDATE `' . $table . '`
                                         SET     ' . $update . $where, $values);

        return $this;
    }


    /**
     * Simple "Does a row with this value exist in that table" method
     *
     * @param string           $table
     * @param string|int|float $column
     * @param string|int|null  $value
     * @param int|null         $id
     * @param string           $id_column
     * @param bool             $ignore_deleted_status
     *
     * @return bool
     */
    public function exists(string $table, string|int|float $column, string|int|null $value, ?int $id = null, string $id_column = 'id', bool $ignore_deleted_status = false): bool
    {
        if ($id) {
            return (bool) $this->getRow('SELECT `' . $column . '` FROM `' . $table . '` WHERE `' . $column . '` = :' . $column . ' AND `' . $id_column . '` != :id', [
                ':' . $column => $value,
                ':id'         => $id,
            ]);
        }

        return (bool) $this->getRow('SELECT `' . $column . '` FROM `' . $table . '` WHERE `' . $column . '` = :' . $column, [
            ':' . $column => $value
        ]);
    }


    /**
     * Delete the specified table entry
     *
     * This is a simplified delete method to speed up writing basic delete queries
     *
     * @param string $table
     * @param array  $execute
     *
     * @return static
     */
    public function delete(string $table, array $execute): static
    {
        // This table is not a DataEntry table, delete the entry
        return $this->erase($table, $execute);
    }


    /**
     * Delete the row in the specified table
     *
     * This is a simplified delete method to speed up writing basic delete queries for DataEntry tables
     *
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     *
     * @param string $table
     * @param array  $where
     * @param string $separator
     *
     * @return static
     */
    public function erase(string $table, array $where, string $separator = 'AND'): static
    {
        Core::checkReadonly('sql erase');

        // Build bound variables for the query
        $variables = SqlQueries::getBoundValues($where);
        $update    = SqlQueries::filterColumns($where, ' ' . $separator . ' ');

        $this->query('DELETE FROM `' . $table . '`
                      WHERE        ' . $update, $variables);

        return $this;
    }


    /**
     * Truncates the specified table
     *
     * @param string $table
     *
     * @return void
     */
    public function truncate(string $table): void
    {
        Core::checkReadonly('sql truncate');
        $this->query('TRUNCATE `' . addslashes($table) . '`');
    }


    /**
     * Returns an integer variable from the SQL database
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     * @param string|null         $column
     *
     * @return int|null
     */
    public function getInteger(string|PDOStatement $query, ?array $execute = null, ?string $column = null): int|null
    {
        $result = static::getNumeric($query, $execute, $column);

        if ($result === null) {
            // Not found
            return null;
        }

        if (is_integer($result)) {
            return $result;
        }

        throw new OutOfBoundsException(tr('Query ":query" produced non-integer result ":result"', [
            ':query'  => $query,
            ':result' => $result,
        ]));
    }


    /**
     * Returns a numeric variable from the SQL database
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     * @param string|null         $column
     *
     * @return float|int|null
     * @throws OutOfBoundsException Thrown if the result is non numeric
     */
    public function getNumeric(string|PDOStatement $query, ?array $execute = null, ?string $column = null): float|int|null
    {
        $result = static::getColumn($query, $execute, $column);

        if ($result === null) {
            // Not found
            return null;
        }

        if (!is_numeric($result)) {
            throw new OutOfBoundsException(tr('Query ":query" produced non-numeric result ":result"', [
                ':query'  => $query,
                ':result' => $result,
            ]));
        }

        if (is_numeric_integer($result)) {
            return (int) $result;
        }

        return (float) $result;
    }


    /**
     * Returns a float variable from the SQL database
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     * @param string|null         $column
     *
     * @return float|null
     */
    public function getFloat(string|PDOStatement $query, ?array $execute = null, ?string $column = null): float|null
    {
        return (float) static::getNumeric($query, $execute, $column);
    }


    /**
     * Executes the single column query and returns array with only scalar values.
     *
     * Each key will be a numeric index starting from 0
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     *
     * @return array
     */
    public function listScalar(string|PDOStatement $query, ?array $execute = null): array
    {
        $return    = [];
        $statement = $this->getPdoStatement($query, $execute);

        while ($row = $this->fetch($statement)) {
            $return[] = $row[array_key_first($row)];
        }

        return $return;
    }


    /**
     * Returns PDO statement from the given query / execute
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     *
     * @return PDOStatement
     */
    protected function getPdoStatement(string|PDOStatement $query, ?array $execute = null): PDOStatement
    {
        if (is_object($query)) {
            return $query;
        }

        return $this->query($query, $execute);
    }


    /**
     * Executes the query and returns array with each complete row in a subarray
     *
     * Each subarray will have a numeric index key starting from 0
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     *
     * @return array
     */
    public function listArray(string|PDOStatement $query, ?array $execute = null): array
    {
        $return    = [];
        $statement = $this->getPdoStatement($query, $execute);

        while ($row = $this->fetch($statement)) {
            $return[] = $row;
        }

        return $return;
    }


    /**
     * Executes the query for two columns and will return the results as a key => static value array
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     *
     * @return array
     */
    public function listKeyValue(string|PDOStatement $query, ?array $execute = null): array
    {
        $return    = [];
        $statement = $this->getPdoStatement($query, $execute);

        while ($row = $this->fetch($statement)) {
            $return[$row[array_key_first($row)]] = $row[array_key_last($row)];
        }

        return $return;
    }


    /**
     * Executes the query for two or more columns and will return the results as a key => values-in-array array
     *
     * The key will be the first selected column but will be included in the value array
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     * @param string|null         $id_column
     *
     * @return array
     */
    public function listKeyValues(string|PDOStatement $query, ?array $execute = null, ?string $id_column = null): array
    {
        $return    = [];
        $statement = $this->getPdoStatement($query, $execute);

        while ($row = $this->fetch($statement)) {
            try {
                if (!$id_column) {
                    $key = $row[array_key_first($row)];

                } else {
                    $key = $row[$id_column];
                }

            } catch (Throwable $e) {
                throw OutOfBoundsException::new(tr('Specified column ":column" does not exist in result row', [
                    ':column' => $id_column,
                ]), $e)->addData([
                    'column' => $id_column,
                    'row'    => $row,
                ]);
            }

            $return[$key] = $row;
        }

        return $return;
    }


    /**
     * Executes the query for two or more columns and will return the results as a key => values-in-array array,
     * removing the key from the values
     *
     * The key will be the first selected column and will be removed from the value array
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     *
     * @return array
     */
    public function list(string|PDOStatement $query, ?array $execute = null): array
    {
        $return    = [];
        $statement = $this->getPdoStatement($query, $execute);

        while ($row = $this->fetch($statement)) {
            $return[array_shift($row)] = $row;
        }

        return $return;
    }


    /**
     * Close the connection for the specified connector
     *
     * @return void
     */
    public function close(): void
    {
        $this->pdo = null;
    }


    /**
     * Get the current last insert id for this SQL database connector
     *
     * @return ?int
     */
    public function getInsertId(): ?int
    {
        $insert_id = $this->pdo->lastInsertId();

        if ($insert_id) {
            return (int) $insert_id;
        }

        return null;
    }


    /**
     * Enable / Disable all query logging on mysql server
     *
     * @param bool $enable
     *
     * @return void
     */
    public function enableLog(bool $enable): void
    {
        if ($enable) {
            $this->query('SET global log_output = "FILE";');
            $this->query('SET global general_log_file="/var/log/mysql/queries.log";');
            $this->query('SET global general_log = 1;');

        } else {
            $this->query('SET global log_output = "OFF";');
        }
    }


    /**
     * Will return a count on the specified table
     *
     * NOTE: Use only on huge tables (> 1M rows)
     *
     * Return table row count by returning results count for SELECT `id`
     * Results will be cached in a counts table
     *
     * @param string     $table
     * @param string     $where
     * @param array|null $execute
     * @param string     $column
     *
     * @return int
     */
    public function count(string $table, string $where = '', ?array $execute = null, string $column = '`id`'): int
    {
        throw new UnderConstructionException();

        $expires = config()->get('databases.cache.expires');
        $hash    = hash('sha1', $table . $where . $column . json_encode($execute));
        $count   = $this->getColumn('SELECT `count` FROM `counts` WHERE `hash` = :hash AND `until` > NOW()', 'count', [
            ':hash' => $hash,
        ]);

        if ($count) {
            return $count;
        }

        // Count value was not found cached, count it directly
        $count = $this->getRow('SELECT COUNT(' . $column . ') AS `count` FROM `' . $table . '` ' . $where, 'count', $execute);

        // TODO Use a query cache class
        $this->query('INSERT INTO `counts` (`created_by`, `count`, `hash`, `until`)
                            VALUES               (:created_by , :count , :hash , NOW() + INTERVAL :expires SECOND)
         
                            ON DUPLICATE KEY UPDATE `count`      = :update_count,
                                                    `until`      = NOW() + INTERVAL :update_expires SECOND', [
            ':created_by'     => isset_get($_SESSION['user']['id']),
            ':hash'           => $hash,
            ':count'          => $count,
            ':expires'        => $expires,
            ':update_expires' => $expires,
            ':update_count'   => $count,
        ]);

        return $count;
    }


    /**
     * Returns what database currently is selected
     *
     * @return string|null
     */
    public function getCurrentDatabase(): ?string
    {
        return $this->getColumn('SELECT DATABASE() AS `database` FROM DUAL;');
    }


    /**
     * Returns information about the specified database
     *
     * @param string $database
     *
     * @return array
     */
    public function getDatabaseInformation(string $database): array
    {
        $return = $this->getRow('SELECT  `databases`.`id`,
                                            `databases`.`servers_id`,
                                            `databases`.`status`,
                                            `databases`.`replication_status`,
                                            `databases`.`name` AS `database`,
                                            `databases`.`error`,
       
                                            `servers`.`id` AS `servers_id`,
                                            `servers`.`hostname`,
                                            `servers`.`port`,
                                            `servers`.`replication_status` AS `servers_replication_status`,
       
                                            `database_accounts`.`username`      AS `replication_db_user`,
                                            `database_accounts`.`password`      AS `replication_db_password`,
                                            `database_accounts`.`root_password` AS `root_db_password`
       
                                  FROM      `databases`
       
                                  LEFT JOIN `servers`
                                  ON        `servers`.`id`           = `databases`.`servers_id`
       
                                  LEFT JOIN `database_accounts`
                                  ON        `database_accounts`.`id` = `servers`.`database_accounts_id`
       
                                  WHERE     `databases`.`id`         = :name
                                  OR        `databases`.`name`       = :name', [':name' => $database]);

        if (!$return) {
            throw new SqlException(tr('Specified database ":database" does not exist', [':database' => $database]));
        }

        return $return;
    }


    /**
     * Ensure that the specified limit is below or equal to the maximum configured limit
     *
     * @param int $limit
     *
     * @return int
     */
    public function getValidLimit(int $limit): int
    {
        $limit = force_natural($limit);

        if ($limit > $this->configuration['limit_max']) {
            return $this->configuration['limit_max'];
        }

        return $limit;
    }


    /**
     * Connects to this database and executes a test query
     *
     * @return static
     */
    public function test(): static
    {
        $result = $this->connect(true)
                       ->getColumn('SELECT 1');

        if ($result === 1) {
            return $this;
        }

        throw DatabaseTestException::new(tr('Database test for connector ":connector" should return "1" but returned ":result" instead', [
            ':connector' => $this->connector,
            ':result'    => $result,
        ]))
        ->setDatabase($this->getDatabase())
        ->setConnectorObject($this->getConnectorObject());
    }

//    /**
//     * Try to get single data entry from memcached. If not available, get it from
//     * MySQL and store results in memcached for future use
//     *
//     * @param string $key
//     * @param string $query
//     * @param bool $column
//     * @param array|null $execute
//     * @param int $expiration_time
//     * @return array|null
//     */
//    public function getCached(string $key, string $query, bool $column = false, ?array $execute = null, int $expiration_time = 86400): ?array
//    {
//        if (($value = Mc::db($this->getDatabase())->get($key, '$this->')) === false) {
//            /*
//             * Keyword data not found in cache, get it from MySQL with
//             * specified query and store it in cache for next read
//             */
//            if (is_array($column)) {
//                /*
//                 * Argument shift, no columns were specified.
//                 */
//                $tmp = $execute;
//                $execute = $column;
//                $column = $tmp;
//                unset($tmp);
//            }
//
//            if (is_numeric($column)) {
//                /*
//                 * Argument shift, no columns were specified.
//                 */
//                $tmp = $expiration_time;
//                $expiration_time = $execute;
//                $execute = $tmp;
//                unset($tmp);
//            }
//
//            $value = $this->get($query, $column, $execute, $this->configuration);
//
//            Mc::db($this->getDatabase())->set($value, $key, '$this->', $expiration_time);
//        }
//
//        return $value;
//    }
//    /**
//     * Try to get data list from memcached. If not available, get it from
//     * MySQL and store results in memcached for future use
//     *
//     * @param string $key
//     * @param string $query
//     * @param array|null $execute
//     * @param bool $numerical_array
//     * @param int $expiration_time
//     * @return array|null
//     */
//    public function listCached(string $key, string $query, ?array $execute = null, bool $numerical_array = false, int $expiration_time = 86400): ?array
//    {
//        if (($list = Mc::db($this->getDatabase())->get($key, '$this->')) === false) {
//            /*
//             * Keyword data not found in cache, get it from MySQL with
//             * specified query and store it in cache for next read
//             */
//            $list = $this->list($query, $execute, $numerical_array, $this->configuration);
//
//            Mc::db($this->getDatabase())->set($list, $key, '$this->', $expiration_time);
//        }
//
//        return $list;
//    }
    ///*
    // *
    // *
    // * @copyright Copyright © 2022 Sven Olaf Oostenbrink
    // * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
    // * @category Function reference
    // * @package sql
    // *
    // * @return array
    // */
    //public function exec_get($restrictions, $query, $root = false, $simple_quotes = false) {
    //    try {
    //
    //    } catch (Exception $e) {
    //        throw new SqlException(tr('Failed'), $e);
    //    }
    //}


    /**
     * Connects to this database and executes a test query
     *
     * @param PhoFileInterface $file
     *
     * @return static
     */
    public function export(PhoFileInterface $file): static
    {
        throw new UnderConstructionException();
        // TODO: Implement export() method.
    }


    /**
     * Import data from specified file
     *
     * @param PhoFileInterface $file
     *
     * @return static
     */
    public function import(PhoFileInterface $file): static
    {
        throw new UnderConstructionException();
        // TODO: Implement import() method.
    }


    /**
     * Logs the cache statistics when in debug mode
     *
     * @return void
     */
    public static function logStatistics(): void
    {
        if (Debug::isEnabled() and !QUIET) {
            Log::write(ts('STATISTIC SQL object executed ":count" queries in ":time" seconds', [
                ':count' => Timers::getCount('sql'),
                ':time'  => number_format(Timers::getTotal('sql'), 5),
            ]), 'debug', 9);
        }
    }
}
