<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql;

use JetBrains\PhpStorm\NoReturn;
use PDO;
use PDOStatement;
use Phoundation\Cli\Cli;
use Phoundation\Cli\CliCommand;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Meta;
use Phoundation\Core\Timers;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Connectors\Connectors;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Connectors\Interfaces\ConnectorsInterface;
use Phoundation\Databases\Exception\DatabaseTestException;
use Phoundation\Databases\Sql\Exception\Interfaces\SqlExceptionInterface;
use Phoundation\Databases\Sql\Exception\SqlAccessDeniedException;
use Phoundation\Databases\Sql\Exception\SqlColumnDoesNotExistsException;
use Phoundation\Databases\Sql\Exception\SqlConnectException;
use Phoundation\Databases\Sql\Exception\SqlDatabaseDoesNotExistException;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Exception\SqlInvalidConfigurationException;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;
use Phoundation\Databases\Sql\Exception\SqlNoTimezonesException;
use Phoundation\Databases\Sql\Exception\SqlServerNotAvailableException;
use Phoundation\Databases\Sql\Exception\SqlTableDoesNotExistException;
use Phoundation\Databases\Sql\Interfaces\SqlDataEntryInterface;
use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Databases\Sql\Interfaces\SqlQueryInterface;
use Phoundation\Databases\Sql\Schema\Schema;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpModuleNotAvailableException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Enums\EnumFileOpenMode;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Servers\Servers;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Throwable;


/**
 * Class Sql
 *
 * This class is the main SQL database access class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Sql implements SqlInterface
{
    /**
     * Dynamic database configurations
     *
     * @var array $configurations
     */
    protected static array $configurations = [];

    /**
     * Identifier of this instance
     *
     * @var string|null $connector
     */
    protected ?string $connector = null;

    /**
     * All SQL database configuration
     *
     * @var array $configuration
     */
    protected array $configuration = [];

    /**
     * Registers what database is in use
     *
     * @var string|null $using_database
     */
    protected ?string $using_database = null;

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
     * Sets if debug is enabled or disabled
     *
     * @var bool $debug
     */
    protected static bool $debug = false;

    /**
     * Sets if query logging enabled or disabled
     *
     * @var bool $log
     */
    protected bool $log = false;

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
     * SqlConnectors list
     *
     * @var ConnectorsInterface
     */
    protected static ConnectorsInterface $connectors;


    /**
     * Sql constructor
     *
     * @param ConnectorInterface|string|null $connector
     * @param bool $use_database
     */
    public function __construct(ConnectorInterface|string|null $connector = null, bool $use_database = true)
    {
        $this->uniqueid = Strings::getRandom();

        if ($connector instanceof ConnectorInterface) {
            // Connector specified directly. Take configuration from connector and connect
            $this->connector     = $connector->getName();
            $this->configuration = $connector->getSource();

        } else {
            // Connector specified by name (or null, default)
            if ($connector === null) {
                $connector = 'system';
            }

            // Read configuration and connect
            $this->connector     = $connector;
            $this->configuration = static::readConfiguration($connector);
        }

        if ($this->configuration['log'] === null) {
            $this->configuration['log'] = Config::getBoolean('databases.sql.log', false);
        }

        if ($this->configuration['statistics'] === null) {
            $this->configuration['statistics'] = Config::getBoolean('databases.sql.statistics', false);
        }

        $this->log        = $this->configuration['log'] or Config::getBoolean('databases.sql.log', false);
        $this->statistics = $this->configuration['statistics'];

        $this->connect($use_database);
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
     * Returns the SqlConnectors instance
     *
     * @return ConnectorsInterface
     */
    public static function getConnectors(): ConnectorsInterface
    {
        if (empty(static::$connectors)) {
            static::$connectors = Connectors::new()->load();
        }

        return static::$connectors;
    }


    /**
     * Returns an SqlDataEntry object for this SQL class
     *
     * @param DataEntryInterface $data_entry
     * @return SqlDataEntryInterface
     */
    public function getSqlDataEntryObject(DataEntryInterface $data_entry): SqlDataEntryInterface
    {
        return new SqlDataEntry($this, $data_entry);
    }


    /**
     * Returns if query printing is enabled for this instance or not
     *
     * @return bool
     */
    public function getQueryLogging(): bool
    {
        return $this->log;
    }


    /**
     * Sets if query printing is enabled for this instance or not
     *
     * @param bool $log
     * @return static
     */
    public function setQueryLogging(bool $log): static
    {
        $this->log = $log;
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
     * @return static
     */
    public function setStatistics(bool $statistics): static
    {
        $this->statistics = $statistics;
        return $this;
    }


    /**
     * Returns the name of the database that currently is in use by this database object
     *
     * @return string|null
     */
    public function getDatabase(): ?string
    {
        return $this->using_database;
    }


    /**
     * Returns the name of this SQL instance
     *
     * @return string|null
     */
    public function getConnector(): ?string
    {
        return $this->connector;
    }


    /**
     * Returns an SQL schema object for this instance
     *
     * @param bool $use_database
     * @return Schema
     */
    public function schema(bool $use_database = true): Schema
    {
        if (empty($this->schema)) {
            $this->schema = new Schema($this->connector, $use_database);
        }

        return $this->schema;
    }


    /**
     * Clears schema cache and returns a new SQL schema object for this instance
     *
     * @param bool $use_database
     * @return Schema
     */
    public function resetSchema(bool $use_database = true): Schema
    {
        unset($this->schema);
        return $this->schema($use_database);
    }


    /**
     * Use the specified database
     *
     * @param string|null $database The database to use. If none was specified, the configured system database will be
     *                              used
     * @return static
     * @throws SqlException
     */
    public function use(?string $database = null): static
    {
        if ($database === '') {
            $this->using_database = null;
            return $this;
        }

        $database = $this->getDatabaseName($database);

        Log::action(tr('(:uniqueid) Using database ":database"', [
            ':uniqueid' => $this->uniqueid,
            ':database' => $database
        ]));

        try {
            $this->pdo->query('USE `' . $database . '`');
            $this->using_database = $database;
            return $this;

        } catch (Throwable $e) {
            // We failed to use the specified database, oh noes!
            switch ($e->getCode()) {
                case 1044:
                    // Access to database denied
                    throw new SqlException(tr('Cannot access database ":db", this user has no access to it', [
                        ':db' => $database
                    ]), $e);

                case 1049:
                    throw new SqlException(tr('Cannot use database ":db", it does not exist', [
                        ':db' => $this->configuration['database']
                    ]), $e);
            }

            throw new SqlException($e);
        }
    }


    /**
     * Executes specified query and returns a PDOStatement object
     *
     * @param PDOStatement|SqlQueryInterface|string $query
     * @param array|null $execute
     * @return PDOStatement
     * @throws SqlException
     */
    public function query(PDOStatement|SqlQueryInterface|string $query, ?array $execute = null): PDOStatement
    {
        static $retry = 0;

        $log = false;

        try {
            if (!$query) {
                throw new SqlException(tr('No query specified'));
            }

            $this->counter++;

            // PDO statement can be specified instead of a query?
            if (is_object($query)) {
                if ($this->log or ($query->queryString[0] === ' ')) {
                    $log = true;
                }

                $timer = Timers::new('sql', static::getConnectorLogPrefix() . $query->queryString);

                // Are we going to write?
                SqlQueries::checkWriteAllowed($query->queryString);
                $query->execute($execute);

            } else {
                // Log query?
                if ($this->log or ($query[0] === ' ')) {
                    $log = true;
                }

                $timer = Timers::new('sql', static::getConnectorLogPrefix()  . $query);

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

            $this->processQueryException(SqlException::new($e)
                ->setQuery($query)
                ->setExecute($execute)
                ->setMessage($message)
                ->setSqlState($state));
        }
    }


    /**
     * Insert the specified data row in the specified table, with "on dulplicate update" option
     *
     * This is a simplified insert method to speed up writing basic insert queries
     *
     * @note: PDO::lastInsertId() returns string|false, this method will return int
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param string $table
     * @param array $data
     * @param array|string|null $update
     * @return int
     */
    public function insert(string $table, array $data, array|string|null $update = null): int
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

        return (int) $this->pdo->lastInsertId();
    }


    /**
     * Update the specified data row in the specified table
     *
     * This is a simplified update method to speed up writing basic insert queries
     *
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param string $table
     * @param array $set
     * @param array|null $where
     * @return int The number of rows that were updated
     */
    public function update(string $table, array $set, array|null $where = null): int
    {
        Core::checkReadonly('sql update');

        // Build bound variables for the query
        $values = SqlQueries::getBoundValues(array_merge($set, Arrays::force($where)));
        $update = SqlQueries::getUpdateKeyValues($set);
        $where  = SqlQueries::whereColumns($where);

        $statement = $this->query('UPDATE `' . $table . '`
                                         SET     ' . $update .
                                         $where, $values);

        return $statement->rowCount();
    }


    /**
     * Simple "Does a row with this value exist in that table" method
     *
     * @param string $table
     * @param string|int|float $column
     * @param string|int|null $value
     * @param int|null $id
     * @param string $id_column
     * @return bool
     */
    public function exists(string $table, string|int|float $column, string|int|null $value, ?int $id = null, string $id_column = 'id'): bool
    {
        if ($id) {
            return (bool) $this->get('SELECT `' . $column . '` FROM `' . $table . '` WHERE `' . $column . '` = :' . $column . ' AND `' . $id_column . '` != :id', [
                ':' . $column => $value,
                ':id'         => $id
            ]);
        }

        return (bool) $this->get('SELECT `' . $column . '` FROM `' . $table . '` WHERE `' . $column . '` = :' . $column, [$column => $value]);
    }


    /**
     * Delete the specified table entry
     *
     * This is a simplified delete method to speed up writing basic insert queries
     *
     * @param string $table
     * @param array $execute
     * @return int
     */
    public function delete(string $table, array $execute): int
    {
        // This table is not a DataEntry table, delete the entry
        return $this->erase($table, $execute);
    }


    /**
     * Truncates the specified table
     *
     * @param string $table
     * @return void
     */
    public function truncate(string $table): void
    {
        Core::checkReadonly('sql truncate');
        $this->query('TRUNCATE `' . addslashes($table) . '`');
    }


    /**
     * Delete the row in the specified table
     *
     * This is a simplified delete method to speed up writing basic delete queries for DataEntry tables
     *
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param string $table
     * @param array $where
     * @param string $separator
     * @return int
     */
    public function erase(string $table, array $where, string $separator = 'AND'): int
    {
        Core::checkReadonly('sql erase');

        // Build bound variables for the query
        $variables = SqlQueries::getBoundValues($where);
        $update    = SqlQueries::filterColumns($where, ' ' . $separator . ' ');

         return $this->query('DELETE FROM `' . $table . '`
                                    WHERE        ' . $update, $variables)->rowCount();
    }


    /**
     * Prepare specified query
     *
     * @param string $query
     * @return PDOStatement
     */
    public function prepare(string $query): PDOStatement
    {
        return $this->pdo->prepare($query);
    }


    /**
     * Fetch data with default PDO::FETCH_ASSOC instead of PDO::FETCH_BOTH
     *
     * @param PDOStatement $resource
     * @param int $fetch_style
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
     * Execute query and return only the first row
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param bool $meta_enabled
     * @return array|null
     */
    public function get(string|PDOStatement $query, array $execute = null, bool $meta_enabled = true): ?array
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
                    ':query' => SqlQueries::renderQueryString($result->queryString, $execute)
                ]))->setData([
                    'connector' => $this->connector
                ]);
        }
    }


    /**
     * Get the value of a single column from a single row for the specified query
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param string|null $column
     * @return string|float|int|bool|null
     */
    public function getColumn(string|PDOStatement $query, array $execute = null, ?string $column = null): string|float|int|bool|null
    {
        $result = $this->get($query,  $execute);

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
                ':query' => $query,
                ':column' => $column
            ]));
        } else {
            // No column was specified, so we MUST have received only one column!
            if (count($result) > 1) {
                // The query returned multiple columns
                throw SqlException::new(tr('The query ":query" returned ":count" columns while Sql::\getColumn() without $column specification can only select and return one single column', [
                    ':query' => $query,
                    ':count' => count($result)
                ]))->addData($result);
            }

            return Arrays::firstValue($result);
        }
    }


    /**
     * Returns a numeric variable from the SQL database
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param string|null $column
     * @return float|int|null
     * @throws OutOfBoundsException Thrown if the result is non numeric
     */
    public function getNumeric(string|PDOStatement $query, array $execute = null, ?string $column = null): float|int|null
    {
        $result = static::getColumn($query, $execute, $column);

        if ($result === null) {
            // Not found
            return null;
        }

        if (!is_numeric($result)) {
            throw new OutOfBoundsException(tr('Query ":query" produced non-numeric result ":result"', [
                ':query'  => $query,
                ':result' => $result
            ]));
        }

        if (is_numeric_integer($result)) {
            return (int) $result;
        }

        return (float) $result;
    }


    /**
     * Returns an integer variable from the SQL database
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param string|null $column
     * @return int|null
     */
    public function getInteger(string|PDOStatement $query, array $execute = null, ?string $column = null): int|null
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
            ':result' => $result
        ]));
    }


    /**
     * Returns a float variable from the SQL database
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param string|null $column
     * @return float|null
     */
    public function getFloat(string|PDOStatement $query, array $execute = null, ?string $column = null): float|null
    {
        return (float) static::getNumeric($query, $execute, $column);
    }


    /**
     * Returns a float variable from the SQL database
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param string|null $column
     * @return bool|null
     */
    public function getBoolean(string|PDOStatement $query, array $execute = null, ?string $column = null): bool|null
    {
        $result = static::getColumn($query, $execute, $column);

        if ($result === null) {
            // Not found
            return null;
        }

        return Strings::toBoolean($result);
    }


    /**
     * Returns PDO statement from the given query / execute
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
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
     * Executes the single column query and returns array with only scalar values.
     *
     * Each key will be a numeric index starting from 0
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
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
     * Executes the query and returns array with each complete row in a subarray
     *
     * Each subarray will have a numeric index key starting from 0
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
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
     * @param array|null $execute
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
     * @param array|null $execute
     * @param string|null $column
     * @return array
     */
    public function listKeyValues(string|PDOStatement $query, ?array $execute = null, ?string $column = null): array
    {
        $return    = [];
        $statement = $this->getPdoStatement($query, $execute);

        while ($row = $this->fetch($statement)) {
            try {
                if (!$column) {
                    $key = $row[array_key_first($row)];

                } else {
                    $key = $row[$column];
                }
            } catch (Throwable $e) {
                throw OutOfBoundsException::new(tr('Specified column ":column" does not exist in result row', [
                    ':column' => $column,
                ]), $e)->addData([
                    'column' => $column,
                    'row'    => $row
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
     * @param array|null $execute
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
     * Import data from specified file
     *
     * @param string $file
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return void
     */
    public function import(string $file, RestrictionsInterface|array|string|null $restrictions): void
    {
        throw new UnderConstructionException();

        $tel  = 0;
        $file = File::new($file, $restrictions)->open(EnumFileOpenMode::readOnly);

        while (($line = $file->readLine()) !== false) {
            $line = trim($line);

            if (!empty($line)) {
                $this->pdo->query($line);

                $tel++;
                // :TODO:SVEN:20130717: Right now it updates the display for each record. This may actually slow down import. Make display update only every 10 records or so
                echo 'Importing SQL data (' . $file . ') : ' . number_format($tel) . "\n";
                //one line up!
                echo "\033[1A";
            }
        }

        echo "\nDone\n";

        if (!$file->isEof()) {
            throw new SqlException(tr('Import of file ":file" unexpectedly halted', [':file' => $file]));
        }

        $file->close();
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
     * @param string $table
     * @param string $where
     * @param array|null $execute
     * @param string $column
     * @return int
     */
    public function count(string $table, string $where = '', ?array $execute = null, string $column = '`id`'): int
    {
        throw new UnderConstructionException();
        $expires = Config::get('databases.cache.expires');
        $hash    = hash('sha1', $table . $where . $column . json_encode($execute));
        $count   = $this->getColumn('SELECT `count` FROM `counts` WHERE `hash` = :hash AND `until` > NOW()', 'count', [
            ':hash' => $hash
        ]);

        if ($count) {
            return $count;
        }

        // Count value was not found cached, count it directly
        $count = $this->get('SELECT COUNT(' . $column . ') AS `count` FROM `' . $table . '` ' . $where, 'count', $execute);

        // TODO Use a query cache class
        $this->query('INSERT INTO `counts` (`created_by`, `count`, `hash`, `until`)
                            VALUES               (:created_by , :count , :hash , NOW() + INTERVAL :expires SECOND)
         
                            ON DUPLICATE KEY UPDATE `count`      = :update_count,
                                                    `until`      = NOW() + INTERVAL :update_expires SECOND',

                            [
                                ':created_by' => isset_get($_SESSION['user']['id']),
                                ':hash' => $hash,
                                ':count' => $count,
                                ':expires' => $expires,
                                ':update_expires' => $expires,
                                ':update_count' => $count
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
     * @return array
     */
    public function getDatabaseInformation(string $database): array
    {
        $return = $this->get('SELECT  `databases`.`id`,
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
                                  OR        `databases`.`name`       = :name',

                                  [':name' => $database]);

        if (!$return) {
            throw new SqlException(tr('Specified database ":database" does not exist', [':database' => $database]));
        }

        return $return;
    }


    /**
     * Ensure that the specified limit is below or equal to the maximum configured limit
     *
     * @param int $limit
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
     * Add the configuration for the specified instance name
     *
     * @param string $connector
     * @param array $configuration
     * @return void
     */
    public static function addConnector(string $connector, array $configuration): void
    {
        static::$configurations[$connector] = $configuration;
    }


    /**
     * Reads, validates structure and returns the configuration for the specified instance
     *
     * @param string $connector
     * @return array
     */
    public function readConfiguration(string $connector): array
    {
        // Read in the entire SQL configuration for the specified instance
        $this->connector = $connector;

        if ($connector === 'system') {
            $configuration = Config::getArray('databases.connectors.' . $connector);
            return $this->applyConfigurationTemplate($configuration);
        }

        return Connector::get($connector)->getSource();
    }


    /**
     * Apply configuration template over the specified configuration array
     *
     * @param array $configuration
     * @return array
     */
    protected function applyConfigurationTemplate(array $configuration): array
    {
        // Copy the configuration options over the template
        $configuration = Arrays::mergeFull(static::getConfigurationTemplate(), $configuration);

        switch ($configuration['driver']) {
            case 'mysql':
                // Do we have a MySQL driver available?
                if (!defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
                    // Whelp, MySQL library is not available
                    throw new PhpModuleNotAvailableException('Could not find the "MySQL" library for PDO. To install this on Ubuntu derivatives, please type "sudo apt install php-mysql');
                }

                // Build up ATTR_INIT_COMMAND
                $command = 'SET @@SESSION.TIME_ZONE="+00:00"; ';

                if ($configuration['charset']) {
                    // Set the default character set to use
                    $command .= 'SET NAMES ' . strtoupper($configuration['charset'] . '; ');
                }

                // Apply MySQL specific requirements that always apply
                $configuration['pdo_attributes'][PDO::ATTR_ERRMODE]                  = PDO::ERRMODE_EXCEPTION;
                $configuration['pdo_attributes'][PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = !$configuration['buffered'];
                $configuration['pdo_attributes'][PDO::MYSQL_ATTR_INIT_COMMAND]       = $command;
                break;

            default:
                // Here be dragons!
                Log::warning(static::getConnectorLogPrefix() . tr('WARNING: ":driver" DRIVER MAY WORK BUT IS NOT SUPPORTED', [
                    ':driver' => $configuration['driver']
                ]));
        }

        return $configuration;
    }


    /**
     * Returns an SQL connection configuration template
     *
     * @return array
     */
    protected static function getConfigurationTemplate(): array
    {
        return [
            'type'             => 'sql',
            'driver'           => 'mysql',
            'hostname'         => '127.0.0.1',
            'port'             => null,
            'database'         => '',
            'username'         => '',
            'password'         => '',
            'auto_increment'   => 1,
            'init'             => false,
            'buffered'         => false,
            'charset'          => 'utf8mb4',
            'collate'          => 'utf8mb4_general_ci',
            'limit_max'        => 10000,
            'mode'             => 'PIPES_AS_CONCAT,IGNORE_SPACE',
            'log'              => null,
            'statistics'       => null,
            'ssh_tunnel'       => [
                'required'    => false,
                'source_port' => null,
                'hostname'    => '',
                'usleep'      => 1200000
            ],
            'pdo_attributes'   => [],
            'version'          => '0.0.0',
            'timezones_name'   => 'UTC'
        ];
    }


    /**
     * Connect to the database and do a DB version check.
     * If the database was already connected, then ignore and continue.
     * If the database version check fails, then exception
     *
     * @param bool $use_database
     * @return static
     */
    protected function connect(bool $use_database = true): static
    {
        try {
            if (!empty($this->pdo)) {
                // Already connected to requested DB
                return $this;
            }

            // Does this connector require an SSH tunnel?
            if (isset_get($this->configuration['ssh_tunnel']['required'])) {
                $this->sshTunnel();
            }

            // Connect!
            $retries = 7;

            while (--$retries >= 0) {
                try {
                    $connect_string = $this->configuration['driver'] . ':host=' . $this->configuration['hostname'] . (empty($this->configuration['port']) ? '' : ';port=' . $this->configuration['port']) . (($use_database and $this->configuration['database']) ? ';dbname=' . $this->configuration['database'] : '');
                    $this->pdo = new PDO($connect_string, $this->configuration['username'], $this->configuration['password'], Arrays::force($this->configuration['pdo_attributes']));
                    $this->pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

                    Log::success(static::getConnectorLogPrefix() . tr('Connected to instance ":connector" with PDO connect string ":string"', [
                        ':connector' => $this->connector,
                        ':string'    => $connect_string
                    ]), 3);

                    break;

                } catch (Throwable $e) {
                    if (!$this->configuration['hostname']) {
                        throw new SqlInvalidConfigurationException(static::getConnectorLogPrefix() . tr('Failed to connect to database connector ":connector" with connection string ":string" and user ":user", the database configuration is invalid', [
                                ':connector' => $this->connector,
                                ':string'    => isset_get($connect_string),
                                ':user'      => $this->configuration['username']
                            ]));
                    }

                    switch ($e->getCode()) {
                        case 1045:
                            // Access  denied!
                            throw SqlAccessDeniedException::new(static::getConnectorLogPrefix() . tr('Failed to connect to database connector ":connector" with connection string ":string" and user ":user", access was denied by the database server', [
                                ':connector' => $this->connector,
                                ':string'    => isset_get($connect_string),
                                ':user'      => $this->configuration['username']
                            ]))->makeWarning();

                        case 1049:
                            // Database doesn't exist!
                            throw SqlDatabaseDoesNotExistException::new(static::getConnectorLogPrefix() . tr('Failed to connect to database connector ":connector" with connection string ":string" and user ":user" because the database ":database" does not exist', [
                                ':connector' => $this->connector,
                                ':string'    => isset_get($connect_string),
                                ':database'  => $this->configuration['database'],
                                ':user'      => $this->configuration['username']
                            ]))->makeWarning();

                        case 2002:
                            // Database service not available, connection refused!
                            throw SqlServerNotAvailableException::new(static::getConnectorLogPrefix() . tr('Failed to connect to database connector ":connector" with connection string ":string" and user ":user" because the connection was refused. The database server may be down, or the configuration may be incorrect', [
                                    ':connector' => $this->connector,
                                    ':string'    => isset_get($connect_string),
                                    ':database'  => $this->configuration['database'],
                                    ':user'      => $this->configuration['username']
                                ]))->makeWarning();
                    }

                    if ($e->getMessage() == 'could not find driver') {
                        if ($this->configuration['driver']) {
                            throw new PhpModuleNotAvailableException(tr('Failed to connect with ":driver" driver from connector ":connector", it looks like its not available', [
                                ':connector' => $this->connector,
                                ':driver'    => $this->configuration['driver']
                            ]));
                        }

                        throw new PhpModuleNotAvailableException(tr('Failed to connect connector ":connector", it has no SQL driver specified', [
                            ':connector' => $this->connector
                        ]));
                    }

                    Log::error(static::getConnectorLogPrefix() . tr('Failed to connect to instance ":connector" with PDO connect string ":string", error follows below', [
                        ':connector' => $this->connector,
                        ':string'    => isset_get($connect_string)
                    ]));

                    Log::error($e);

                    $message = $e->getMessage();

                    if (!str_contains($message, 'errno=32')) {
                        if ($e->getMessage() == 'ERROR 2013 (HY000): Lost connection to MySQL server at \'reading initial communication packet\', system error: 0') {
                            if (isset_get($this->configuration['ssh_tunnel']['required'])) {
                                // The tunneling server has "AllowTcpForwarding" set to "no" in the sshd_config, attempt
                                // auto fix
                                CliCommand::new($this->configuration['server'])->enableTcpForwarding($this->configuration['ssh_tunnel']['server']);
                                continue;
                            }
                        }

                        // Continue throwing the exception as normal, we'll retry to connect!
                        throw new SqlConnectException(tr('Failed to connect to SQL connector ":connector"', [
                            ':connector' => $this->connector
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

            // Yay, we're using the database!
            $this->using_database = $this->configuration['database'];

            if ($this->configuration['timezones_name']) {
                // Try to set MySQL timezone
                try {
                    $this->pdo->query('SET TIME_ZONE="' . $this->configuration['timezones_name'] . '";');

                } catch (Throwable $e) {
                    Log::warning(static::getConnectorLogPrefix() . tr('Failed to set timezone ":timezone" for database connector ":connector" with error ":e"', [
                            ':timezone'  => $this->configuration['timezones_name'],
                            ':connector' => $this->connector,
                            ':e'         => $e->getMessage()
                        ]));

                    if (!Core::readRegister('no_time_zone') and (Core::isExecutedPath('system/init'))) {
                        throw $e;
                    }

                    // Indicate that time_zone settings failed (this will subsequently be used by the init system to
                    // automatically initialize that as well)
                    // TODO Write somewhere else than Core "system" register as that will be readonly
                    throw SqlNoTimezonesException::new(tr('Failed to set timezone ":timezone" on connector ":connector", MySQL has not yet loaded any timezones', [
                        ':connector' => $this->connector,
                        ':timezone'  => $this->configuration['timezones_name']
                    ]), $e)->addData([
                        'connector' => $this->connector
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
                        ':db' => $this->configuration['database']
                    ]), $e);

                case 1049:
                    throw new SqlException(tr('Cannot use database ":db", it does not exist', [
                        ':db' => $this->configuration['database']
                    ]), $e);

                case 2002:
                    // Connection refused
                    if (empty($this->configuration['ssh_tunnel']['required'])) {
                        throw new SqlException(tr('Connection refused for host ":hostname::port"', [
                            ':hostname' => $this->configuration['hostname'],
                            ':port'     => $this->configuration['port']
                        ]), $e);
                    }

                    // This connection requires an SSH tunnel. Check if the tunnel process still exists
                    if (!Cli::PidGrep($tunnel['pid'])) {
                        $restrictions = servers_get($this->configuration['ssh_tunnel']['domain']);
                        $registered = ssh_host_is_known($restrictions['hostname'], $restrictions['port']);

                        if ($registered === false) {
                            throw new SqlException(tr('Connection refused for host ":hostname" because the tunnel process was canceled due to missing server fingerprints in the DIRECTORY_ROOT/data/ssh/known_hosts file and `ssh_fingerprints` table. Please register the server first', [
                                ':hostname' => $this->configuration['ssh_tunnel']['domain']
                            ]), $e);
                        }

                        if ($registered === true) {
                            throw new SqlException(tr('Connection refused for host ":hostname" on local port ":port" because the tunnel process either started too late or already died. The server has its SSH fingerprints registered in the DIRECTORY_ROOT/data/ssh/known_hosts file.', [
                                ':hostname' => $this->configuration['ssh_tunnel']['domain'],
                                ':port'     => $this->configuration['port']
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
                        ':port'     => $this->configuration['port']
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

                    $restrictions  = Servers::get($this->configuration['ssh_tunnel']['domain']);
                    $allowed = Cli::getSshTcpForwarding($restrictions);

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
                        'port' => $this->configuration['ssh_tunnel']['source_port']
                    ]);

// :TODO: Implement further error handling.. From here on, appearently inet_telnet() did NOT cause an exception, so we have a result.. We can check the result for mysql server data and with that confirm that it is working, but what would.. well, cause a problem, because if everything worked we would not be here...

                default:
                    throw $e;
            }
        }

        return $this;
    }


    /**
     * Returns the specified database name or the configured system database name
     *
     * @param string|null $database
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
     * @return void
     */
    protected function sshTunnel(): void
    {

    }


    /**
     * Returns the log prefix for this SQL connector
     *
     * @return string
     */
    public function getConnectorLogPrefix(): string
    {
        return '(' . $this->uniqueid . '-' . $this->getDatabase() . '-' . $this->counter . ') ';
    }


    /**
     * Connects to this database and executes a test query
     *
     * @return static
     */
    public function test(): static
    {
        $result = $this->connect(true)->getColumn('SELECT 1');

        if ($result === 1) {
            return $this;
        }

        throw new DatabaseTestException(tr('Database test for connector ":connector" should return "1" but returned ":result" instead', [
            ':connector' => $this->connector,
            ':result'    => $result
        ]));
    }


    /**
     * Process the specified query exception
     *
     * @param SqlExceptionInterface $e
     * @return never
     * @throws SqlExceptionInterface
     */
    #[NoReturn] protected function processQueryException(SqlExceptionInterface $e): never
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
                            ':value' => $value
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

        // Get error data from PDO
        $error = $this->pdo->errorInfo();

        // Check SQL state
        switch ($e->getSqlState()) {
            case 'denied':
                // no-break
            case 'invalidforce':

                // Some database operation has failed
                foreach ($e->getMessages() as $message) {
                    Log::error(static::getConnectorLogPrefix() . $message);
                }

                exit(1);

            case '42S02':
                throw new SqlTableDoesNotExistException(Strings::from($e->getMessage(), '1146'), $e);

            case '3D000':
                throw new SqlDatabaseDoesNotExistException(Strings::from($e->getMessage(), '1146'), $e);

            case 'HY093':
                // Invalid parameter number: number of bound variables does not match number of tokens
                // Get tokens from query
// TODO Check here what tokens do not match to make debugging easier
                preg_match_all('/:\w+/imus', $query, $matches);

                throw $e->addData(Arrays::renameKeys(Arrays::valueDiff($matches[0], array_flip($execute)), [
                    'add'    => 'variables missing in query',
                    'delete' => 'variables missing in execute'
                ]));

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
//                        // no-break
//                    case 1217:
//                        // no-break
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
//                        }catch(Exception $e) {
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
//            ->setUrl('developer/incidents.html')
//            ->setMode(DisplayMode::exception)
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
    // * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
}
