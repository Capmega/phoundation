<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use PDO;
use PDOStatement;
use Phoundation\Cli\Cli;
use Phoundation\Cli\CliCommand;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Exception\LogException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Meta;
use Phoundation\Core\Sessions\Session;
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
use Phoundation\Databases\Sql\Exception\SqlDuplicateException;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Exception\SqlInvalidConfigurationException;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;
use Phoundation\Databases\Sql\Exception\SqlNoTimezonesException;
use Phoundation\Databases\Sql\Exception\SqlServerNotAvailableException;
use Phoundation\Databases\Sql\Exception\SqlTableDoesNotExistException;
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
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Throwable;


/**
 * Sql class
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
     * Sets how many times some failures may be retried until an exception is thrown
     *
     * @var int $maxretries
     */
    protected int $maxretries = 5;

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
        $this->uniqueid = Strings::randomSafe();

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


//    /**
//     * Create an SQL connector in $_CONFIG['db'][$this->instance_name] = $data
//     *
//     * @param string $instance_name
//     * @param array $configuration
//     * @return array The specified connector data, with all informatinon completed if missing
//     */
//    public function makeConfiguration(string $instance_name, array $configuration): array
//    {
//        if (empty($configuration['ssh_tunnel'])) {
//            $configuration['ssh_tunnel'] = array();
//        }
//
//        if ($this->getConfiguration($instance_name)) {
//            if (empty($configuration['overwrite'])) {
//                throw new SqlException(tr('The specified connector name ":name" already exists', [':name' => $instance_name]));
//            }
//        }
//
//        $configuration = $this->ensureConnector($configuration);
//
//        if ($configuration['ssh_tunnel']) {
//            $configuration['ssh_tunnel']['required'] = true;
//        }
//
//        Config::set('database.connectors.' . $instance_name, $configuration);
//        return $configuration;
//    }


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

                $timer = Timers::new('sql', static::getLogPrefix() . $query->queryString);

                // Are we going to write?
                $this->checkWriteAllowed($query->queryString);
                $query->execute($execute);

            } else {
                // Log query?
                if ($this->log or ($query[0] === ' ')) {
                    $log = true;
                }

                $timer = Timers::new('sql', static::getLogPrefix()  . $query);

                // Are we going to write?
                $this->checkWriteAllowed($query);

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
                Log::sql(static::getLogPrefix() . '[' . number_format($timer->getTotal() * 1000, 4) . ' ms] ' . $query->queryString, $execute);
            }

            if ($this->statistics) {
                 // Get current function / file@line. If current function is actually an include then assume this is the
                 // actual script that was executed by route()
                Debug::addStatistic()
                    ->setQuery($this->show($query, $execute, true))
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
     * Write the specified data row in the specified table
     *
     * This is a simplified insert / update method to speed up writing basic insert or update queries. If the
     * $update_row[id] contains a value, the method will try to update instead of insert
     *
     * @note This method assumes that the specified rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param string $table
     * @param array $insert_row
     * @param array $update_row
     * @param string|null $comments
     * @param string|null $diff
     * @param bool $meta_enabled
     * @return int
     */
    public function dataEntryWrite(string $table, array $insert_row, array $update_row, ?string $comments, ?string $diff, bool $meta_enabled = true): int
    {
        if (empty($update_row['id'])) {
            // New entry, insert
            $retry = 0;

            while ($retry++ < $this->maxretries) {
                try {
                    // Create a random table ID
                    $id = random_int(1, PHP_INT_MAX);

                } catch (Exception $e) {
                    throw SqlException::new(tr('Failed to create random table ID'), $e);
                }

                try {
                    // Insert the row
                    $insert_row = Arrays::prepend($insert_row, 'id', $id);
                    return $this->dataEntryInsert($table, $insert_row, $comments, $diff, $meta_enabled);

                } catch (SqlException $e) {
                    if ($e->getCode() !== 1062) {
                        // Some different error, keep throwing
                        throw $e;
                    }

                    // Duplicate entry, which?
                    $column = $e->getMessage();
                    $column = Strings::until(Strings::fromReverse($column, 'key \''), '\'');
                    $column = Strings::from($column, '.');
                    $column = trim($column);

                    if ($column === 'id') {
                        // Duplicate ID, try with a different random number
                        Log::warning(static::getLogPrefix() . tr('Wow! Duplicate ID entry ":rowid" encountered for insert in table ":table", retrying', [
                            ':rowid' => $insert_row['id'],
                            ':table' => $table
                        ]));

                        continue;
                    }

                    // Duplicate other column, continue throwing
                    throw new SqlDuplicateException(tr('Duplicate entry encountered for column ":column"', [
                        ':column' => $column
                    ]), $e);
                }
            }

            // If the randomly selected ID already exists, just try again
            throw new SqlException(tr('Could not find a unique id in ":retries" retries', [
                ':retries' => $this->maxretries
            ]));
        }

        // This is an existing entry, update!
        $this->dataEntryUpdate($table, $update_row, 'update', $comments, $diff, $meta_enabled);
        return $update_row['id'];
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

        $columns = $this->columns($data);
        $values  = $this->values($data);

        if ($update) {
            // Build bound variables for the query
            if (is_array($update)) {
                $data = array_merge($data, $update);
            }

            $keys   = $this->keys($data);
            $update = $this->updateColumns($update);

            $this->query('INSERT INTO            `' . $table . '` (' . $columns . ') 
                                VALUES                                  (' . $keys . ') 
                                ON DUPLICATE KEY UPDATE ' . $update, $values);

        } else {
            // Build bound variables for query
            $keys = $this->keys($data);

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
        $values = $this->values(array_merge($set, Arrays::force($where)));
        $update = $this->updateColumns($set);
        $where  = $this->whereColumns($where);

        $statement = $this->query('UPDATE `' . $table . '`
                                         SET     ' . $update .
                                         $where, $values);

        return $statement->rowCount();
    }


    /**
     * Insert the specified data row in the specified table
     *
     * This is a simplified insert method to speed up writing basic insert queries
     *
     * @note: PDO::lastInsertId() returns string|false, this method will return int
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param string $table
     * @param array $row
     * @param string|null $comments
     * @param string|null $diff
     * @param bool $meta_enabled
     * @return int
     */
    public function dataEntryInsert(string $table, array $row, ?string $comments = null, ?string $diff = null, bool $meta_enabled = true): int
    {
        Core::checkReadonly('sql data-entry-insert');

        // Set meta fields
        if (array_key_exists('meta_id', $row)) {
            $row['meta_id']    = ($meta_enabled ? Meta::init($comments, $diff)->getId() : null);
            $row['created_by'] = Session::getUser()->getId();
            $row['meta_state'] = Strings::random(16);

            unset($row['created_on']);
        }

        // Build bound variables for the query
        $columns = $this->columns($row);
        $values  = $this->values($row, null, true);
        $keys    = $this->keys($row);

        $this->query('INSERT INTO `' . $table . '` (' . $columns . ') VALUES (' . $keys . ')', $values);

        if (empty($row['id'])) {
            // No row id specified, get the insert id from SQL driver
            return (int) $this->pdo->lastInsertId();
        }

        // Use the given row id
        return $row['id'];
    }


    /**
     * Update the specified data row in the specified table
     *
     * This is a simplified insert method to speed up writing basic insert queries
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param string $table
     * @param array $row
     * @param string $action
     * @param string|null $comments
     * @param string|null $diff
     * @param bool $meta_enabled
     * @return int
     */
    public function dataEntryUpdate(string $table, array $row, string $action = 'update', ?string $comments = null, ?string $diff = null, bool $meta_enabled = true): int
    {
        Core::checkReadonly('sql data-entry-update');

        // Set meta fields
        if (array_key_exists('meta_id', $row)) {
            // Log meta_id action
            if ($meta_enabled) {
                Meta::get($row['meta_id'])->action($action, $comments, $diff);
            }

            $row['meta_state'] = Strings::random(16);

            // Never update the other meta-information
            unset($row['status']);
            unset($row['meta_id']);
            unset($row['created_by']);
            unset($row['created_on']);
        }

        // Build bound variables for the query
        $update = $this->updateColumns($row);
        $values = $this->values($row);

        $this->query('UPDATE `' . $table . '` 
                            SET     ' . $update  . '
                            WHERE  `id` = :id', $values);

        return (int) $this->pdo->lastInsertId();
    }


    /**
     * Update the status for the data row in the specified table to "deleted"
     *
     * This is a simplified insert method to speed up writing basic insert queries
     *
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param string $table
     * @param array $row
     * @param string|null $comments
     * @param bool $meta_enabled
     * @return int
     */
    public function dataEntryDelete(string $table, array $row, ?string $comments = null, bool $meta_enabled = true): int
    {
        Core::checkReadonly('sql data-entry-delete');

        // DataEntry table?
        if (array_key_exists('meta_id', $row)) {
            return $this->dataEntrySetStatus('deleted', $table, $row, $comments, $meta_enabled);
        }

        // This table is not a DataEntry table, just delete the entry
        return $this->erase($table, $row);
    }


    /**
     * Delete the specified table entry
     *
     * This is a simplified delete method to speed up writing basic insert queries
     *
     * @param string $table
     * @param string $where
     * @param array $execute
     * @return int
     */
    public function delete(string $table, string $where, array $execute): int
    {
        // This table is not a DataEntry table, just delete the entry
        return $this->erase($table, $where, $execute);
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
     * Update the status for the data row in the specified table to the specified status
     *
     * @param string|null $status
     * @param string $table
     * @param DataEntryInterface|array $entry
     * @param string|null $comments
     * @param bool $meta_enabled
     * @return int
     */
    public function dataEntrySetStatus(?string $status, string $table, DataEntryInterface|array $entry, ?string $comments = null, bool $meta_enabled = true): int
    {
        Core::checkReadonly('sql set-status');

        if (is_object($entry)) {
            $entry = [
                'id'      => $entry->getId(),
                'meta_id' => $entry->getMetaId(),
            ];
        }

        if (empty($entry['id'])) {
            throw new OutOfBoundsException(tr('Cannot set status, no row id specified'));
        }

        // Update the meta data
        if ($meta_enabled) {
            Meta::get($entry['meta_id'], false)->action(tr('Changed status'), $comments, Json::encode([
                'status' => $status
            ]));
        }

        // Update the row status
        return $this->query('UPDATE `' . $table . '` 
                                   SET `status` = :status
                                   WHERE   `id` = :id', [':status' => $status, ':id' => $entry['id']])->rowCount();
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

        // Build bound variables for query
        $values = $this->values($where);
        $update = $this->filterColumns($where, ' ' . $separator . ' ');

         return $this->query('DELETE FROM `' . $table . '`
                                    WHERE        ' . $update, $values)->rowCount();
    }


    /**
     * Builds and returns a query string from the specified query and execute parameters
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param bool $clean
     * @return string
     */
    public static function buildQueryString(string|PDOStatement $query, ?array $execute = null, bool $clean = false): string
    {
        if (is_object($query)) {
            if (!($query instanceof PDOStatement)) {
                throw new SqlException(tr('Object of unknown class ":class" specified where PDOStatement was expected', [':class' => get_class($query)]));
            }

            // Query to be logged is a PDO statement, extract the query
            $query = $query->queryString;
        }

        $query = trim($query);

        if ($clean) {
            $query = Strings::cleanWhiteSpace($query);
        }

        // Apply execution variables
        if (is_array($execute)) {
            /*
             * Reverse key sort to ensure that there are keys that contain at least parts of other keys will not be used
             * incorrectly
             *
             * example:
             *
             * array(category    => test,
             *       category_id => 5)
             *
             * Would cause the query to look like `category` = "test", `category_id` = "test"_id
             */
            krsort($execute);

            foreach ($execute as $key => $value) {
                if (is_string($value)) {
                    $value = addslashes($value);
                    $query = str_replace($key, '"' . Strings::Log($value) . '"', $query);

                } elseif (is_null($value)) {
                    $query = str_replace($key, ' ' . tr('NULL') . ' ', $query);

                } elseif (is_bool($value)) {
                    $query = str_replace($key, Strings::fromBoolean($value), $query);

                } else {
                    if (!is_scalar($value)) {
                        throw new LogException(tr('Query ":query" $execute key ":key" has non-scalar value ":value"', [
                            ':key'   => $key,
                            ':value' => $value,
                            ':query' => $query
                        ]));
                    }

                    $query = str_replace((string) $key, (string) $value, $query);
                }
            }
        }

        return $query;
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
                $this->ensureShowSelect($query, $execute);
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
                $this->ensureShowSelect($query, $execute);

                throw new SqlMultipleResultsException(tr('Failed for query ":query" to fetch single row, specified query result contains not 1 but ":count" results', [
                    ':count' => $result->rowCount(),
                    ':query' => static::buildQueryString($result->queryString, $execute)
                ]));
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
                throw SqlException::new(tr('The query ":query" returned ":count" columns while $this->getColumn() without $column specification can only select and return one single column', [
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
            $return[] = array_first($row);
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
            $return[array_first($row)] = array_last($row);
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
     * @return array
     */
    public function listKeyValues(string|PDOStatement $query, ?array $execute = null, ?string $column = null): array
    {
        $return    = [];
        $statement = $this->getPdoStatement($query, $execute);

        while ($row = $this->fetch($statement)) {
            if (!$column) {
                $key = array_first($row);
            } else {
                $key = $row[$column];
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
     * Merge database entry with new posted entry, overwriting the old DB values,
     * while skipping the values specified in $skip
     *
     * @param array|null $database_entry
     * @param array|null $post
     * @param array|string|null $skip
     * @param bool $recurse
     * @return array|null The specified datab ase entry, updated with all the data from the specified $_POST entry
     */
    public static function mergeRecord(?array $database_entry, ?array $post, array|string|null $skip = null, bool $recurse = true): ?array
    {
        if (!$post) {
            if (!is_array($post)) {
                throw new SqlException(tr('Specified post source data type should be an array but is a ":type"', [':type' => gettype($post)]));
            }

            // No post was done, there is nothing to merge
            return $database_entry;
        }

        if (!is_array($database_entry)) {
            if ($database_entry !== null) {
                throw new SqlException(tr('Specified database source data type should be an array or NULL but is a ":type"', [':type' => gettype($database_entry)]));
            }

            // Database entry is empty
            $database_entry = [];
        }

        // By default, do not copy the id, meta_id and status columns
        if ($skip === null) {
            $skip = 'id,meta_id,status';
        }

        $skip = Arrays::force($skip);

        // Copy all POST variables over DB. Skip POST variables that have NULL value
        foreach ($database_entry as $key => $value) {
            if (in_array($key, $skip)) {
                // This can be skipped
                continue;
            }

            if (!array_key_exists($key, $post)) {
                // This key doesn't exist in post, continue to the next
                continue;
            }

            if (is_array($value)) {
                // This entry is an array, do a recursive merge if post was specified too
                if (!is_array($post[$key])) {
                    // Whoops, $post format is invalid
                    throw new OutOfBoundsException(tr('Specified post entry key ":key" is invalid, it should be an array but is a ":type"', [
                        ':key' => $key,
                        ':type' => gettype($post[$key])
                    ]));
                }

                // Recurse
                if ($recurse) {
                    $database_entry[$key] = Sql::merge($value, $post[$key], $skip, $recurse);
                }
            } elseif (is_scalar($post[$key]) or ($post[$key] === null)) {
                if (is_scalar($value) or ($value === null)) {
                    // Copy post key to database entry
                    $database_entry[$key] = $post[$key];
                } else {
                    // Whoops, $post format is invalid
                    throw new OutOfBoundsException(tr('Specified post entry key ":key" is invalid, it should be an array but is a ":type"', [
                        ':key' => $key,
                        ':type' => gettype($post[$key])
                    ]));
                }

            } else {
                // Invalid datatype
                throw new OutOfBoundsException(tr('Specified post entry key ":key" has an invalid datatype, it should be one of NULL, string, int, float, or bool but is a ":type"', [
                    ':key' => $key,
                    ':type' => gettype($post[$key])
                ]));
            }
        }

        return $database_entry;
    }


    /**
     * Return a list of the specified $columns from the specified source
     *
     * @param array|string|null $source
     * @param string|null $prefix
     * @param string $separator
     * @return string
     */
    protected function updateColumns(array|string|null $source, ?string $prefix = null, string $separator = ', '): string
    {
        if (is_string($source)) {
            // Source has already been prepared, return it
            return $source;
        }

        $return = [];

        foreach ($source as $key => $value) {
            switch ($key) {
                case 'id':
                    // no-break
                case 'meta_id':
                    // NEVER update these!
                    break;

                default:
                    $return[] = '`' . $prefix . $key . '` = :' . $key;
            }
        }

        return implode($separator, $return);
    }


    /**
     * Return a list of the specified $columns from the specified source
     *
     * @param array|string|null $where
     * @param string|null $prefix
     * @param string $separator
     * @return string
     */
    protected function whereColumns(array|string|null $where, ?string $prefix = null, string $separator = ' AND '): string
    {
        if (!$where) {
            return '';
        }

        if (is_string($where)) {
            // The Source has already been prepared, return it
            return $where;
        }

        $return = [];

        foreach ($where as $key => $value) {
            switch ($key) {
                case 'meta_id':
                    // NEVER update these!
                    break;

                default:
                    $return[] = '`' . $prefix . $key . '` = :' . $key;
            }
        }

        return ' WHERE ' . implode($separator, $return);
    }


    /**
     * Return a list of the specified $columns from the specified source
     *
     * @param array $source
     * @param string $separator
     * @return string
     */
    protected function filterColumns(array $source, string $separator = ' AND '): string
    {
        $return = [];

        foreach ($source as $key => $value) {
            $return[] = '`' . $key . '` = :' . $key;
        }

        return implode($separator, $return);
    }


    /**
     * Return a list of the specified $columns from the specified source
     *
     * @param array $source
     * @param string|null $prefix
     * @return string
     */
    protected function columns(array $source, ?string $prefix = null): string
    {
        $return = [];

        foreach ($source as $key => $value) {
            $return[] = '`' . $prefix . $key . '`';
        }

        return implode(', ', $return);
    }


    /**
     * Converts the specified row data into a PDO bound variables compatible key > values array
     *
     * @param array|string $source
     * @param string|null $prefix
     * @return string
     */
    protected function keys(array|string $source, ?string $prefix = null): string
    {
        $return  = [];

        foreach ($source as $key => $value) {
            $return[':' . $prefix . $key] = $value;
        }

        $return = array_keys($return);
        $return = implode(', ', $return);

        return $return;
    }


    /**
     * Converts the specified row data into a PDO bound variables compatible key > values array
     *
     * @param array|string $source
     * @param string|null $prefix
     * @param bool $insert
     * @return array
     */
    protected function values(array|string $source, ?string $prefix = null, bool $insert = false): array
    {
        $return  = [];

        foreach ($source as $key => $value) {
            if (($key === 'meta_id') and !$insert) {
                // Only process meta_id on insert operations
                continue;
            }

            $return[':' . $prefix . $key] = $value;
        }

        return $return;
    }


    /**
     * Get the current last insert id for this SQL database connector
     *
     * @return ?int
     */
    public function insertId(): ?int
    {
        $insert_id = $this->pdo->lastInsertId();

        if ($insert_id) {
            return (int) $insert_id;
        }

        return null;
    }


    /**
     * Use correct SQL in case NULL is used in queries
     *
     * @param string $column
     * @param array|string|int|float|null $values
     * @param string $label
     * @param array|null $execute
     * @param string $glue
     * @return string
     */
    public static function is(string $column, array|string|int|float|null $values, string $label, ?array &$execute = null, string $glue = 'AND'): string
    {
        Arrays::ensure($execute);

        $label = Strings::startsWith($label, ':');
        $return = [];

        if (is_array($values)) {
            $in = [];
            $notin = [];

            foreach ($values as $value) {
                $not = false;

                if (str_starts_with((string) $value, '!')) {
                    // Make comparison NOT by prefixing ! to $value
                    $value = substr($value, 1);
                    $not = true;
                }

                if (($value === null) or (strtoupper(substr((string) $value, -4, 4)) === 'NULL')) {
                    $null = ($not ? '!NULL' : 'NULL');
                    continue;
                }

                if ($not) {
                    $notin[] = $value;

                } else {
                    $in[] = $value;
                }
            }

            if ($in) {
                $in = Sql::in($in);
                $execute = array_merge((array) $execute, $in);
                $return[] = ' ' . $column . ' IN (' . implode(', ', array_keys($in)) . ')';
            }

            if ($notin) {
                $notin = Sql::in($notin, start: count($execute));
                $execute = array_merge((array) $execute, $notin);

                if (!isset($null)) {
                    // (My)Sql curiosity: When comparing != string, NULL values are NOT evaluated
                    $return[] = ' (' . $column . ' NOT IN (' . implode(', ', array_keys($notin)) . ') OR ' . $column . ' IS NULL)';
                } else {
                    $return[] = ' ' . $column . ' NOT IN (' . implode(', ', array_keys($notin)) . ')';
                }
            }

            if (isset($null)) {
                $return[] = static::isSingle($column, $null, $label, $execute);
            }

            return implode(' ' . $glue . ' ', $return);
        }

        return static::isSingle($column, $values, $label, $execute);
    }


    /**
     * Use correct SQL in case NULL is used in queries
     *
     * @param string $column
     * @param string|int|float|null $value
     * @param string $label
     * @param array|null $execute
     * @return string
     */
    protected static function isSingle(string $column, string|int|float|null $value, string $label, ?array &$execute = null): string
    {
        $not = false;

        if (str_starts_with((string) $value, '!')) {
            // Make comparison opposite of $not by prepending the value with a ! sign
            $value = substr($value, 1);
            $not = true;
        }

        if (strtoupper(substr((string) $value, -4, 4)) === 'NULL') {
            $value = null;
        }

        if ($value === null) {
            $null = $not;
        }

        if (isset($null)) {
            // We have to do a NULL comparison
            return ' ' . $column . ' IS ' . ($null ? 'NOT ' : '') . 'NULL ';
        }

        // Add the label
        $execute[$label] = $value;

        if ($not) {
            // (My)Sql curiosity: When comparing != string, NULL values are NOT evaluated
            return ' (' . $column . ' != ' . Strings::startsWith($label, ':') . ' OR ' . $column . ' IS NULL)';
        }

        return ' ' . $column . ' = ' . Strings::startsWith($label, ':');
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
     * Simple "Does a row with this value exist in that table" method
     *
     * @param string $table
     * @param string $column
     * @param string|int|null $value
     * @param int|null $id ONLY WORKS WITH TABLES HAVING `id` column! (almost all do) If specified, will NOT select the
     *                     row with this id
     * @return bool
     */
    public function DataEntryExists(string $table, string $column, string|int|null $value, ?int $id = null): bool
    {
        if ($id) {
            return (bool) $this->get('SELECT `id` FROM `' . $table . '` WHERE `' . $column . '` = :' . $column . ' AND `id` != :id', [
                ':' . $column => $value,
                ':id'         => $id
            ]);
        }

        return (bool) $this->get('SELECT `id` FROM `' . $table . '` WHERE `' . $column . '` = :' . $column, [$column => $value]);
    }


    /**
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
        $hash = hash('sha1', $table . $where . $column . json_encode($execute));
        $count = $this->get('SELECT `count` FROM `counts` WHERE `hash` = :hash AND `until` > NOW()', 'count', array(':hash' => $hash));

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
    public function validLimit(int $limit): int
    {
        $limit = force_natural($limit);

        if ($limit > $this->configuration['limit_max']) {
            return $this->configuration['limit_max'];
        }

        return $limit;
    }


    /**
     * Return a valid " LIMIT X, Y " string built from the specified parameters
     *
     * @param int|null $limit
     * @param int|null $page
     * @return string The SQL " LIMIT X, Y " string
     */
    public function getLimit(?int $limit = null, ?int $page = null): string
    {
        $limit = Paging::getLimit($limit);

        if (!$limit) {
            // No limits, so show all
            return '';
        }

        return ' LIMIT ' . ((Paging::getPage($page) - 1) * $limit) . ', ' . $limit;
    }


    /**
     * Show the specified SQL query in a debug
     *
     * @param string|PDOStatement $query
     * @param ?array $execute
     * @param bool $return_only
     * @return mixed
     * @throws SqlException
     */
    public function show(string|PDOStatement $query, ?array $execute = null, bool $return_only = false): mixed
    {
        $query = static::buildQueryString($query, $execute, true);

        if ($return_only) {
            return $query;
        }

        if (!Core::readRegister('debug', 'clean')) {
            $query = str_replace("\n", ' ', $query);
            $query = Strings::nodouble($query, ' ', '\s');
        }

        // Debug::enabled() already logs the query, don't log it again
        if (!Debug::getEnabled()) {
            Log::debug(static::getLogPrefix() . Strings::endsWith($query, ';'));
        }

        return Debug::show(Strings::endsWith($query, ';'), 6);
    }


    /**
     * Ensure that the specified query is either a select query or a show query
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @return void
     */
    protected function ensureShowSelect(string|PDOStatement $query, ?array $execute): void
    {
        if (is_object($query)) {
            $query = $query->queryString;
        }

        $query = strtolower(substr(trim($query), 0, 10));

        if (!str_starts_with($query, 'select') and !str_starts_with($query, 'show')) {
            throw new SqlException(tr('Query ":query" is not a SELECT or SHOW query and as such cannot return results', [
                ':query' => Strings::log(static::getLogPrefix() . Log::sql($query, $execute), 4096)
            ]));
        }
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

//        try {
//            $configuration = Config::getArray('databases.connectors.' . $connector);
//
//        } catch (ConfigPathDoesNotExistsException) {
//            // Configuration not available in Config. Check if its stored in SQL database
//            $configuration = $this->readSqlConfiguration($connector);
//
//            if (!$configuration) {
//                // Okay, this instance doesn't exist in Config, nor SQL, maybe it's a dynamically configured instance?
//                if (!array_key_exists($connector, static::$configurations)) {
//                    // Yeah, it's not found
//                    throw new SqlException(tr('The specified SQL connector ":connector" is not configured', [
//                        ':connector' => $connector
//                    ]));
//                }
//
//                // This is a dynamically configured instance
//                $configuration = static::$configurations[$connector];
//            }
//        }
//
//        // Copy the configuration options over the template
//        return $this->applyConfigurationTemplate($configuration);
    }


    /**
     * Load SQL configuration from the database
     *
     * @param string $instance
     * @return array|null
     */
    protected function readSqlConfiguration(string $instance): ?array
    {
        // TODO Implement an Updates.php file that creates this table first
        return null;

        return $this->get('SELECT `id`,
                                         `created_on`,
                                         `created_by`,
                                         `meta_id`,
                                         `status`,
                                         `name`,
                                         `seo_name`,
                                         `servers_id`,
                                         `hostname`,
                                         `driver`,
                                         `database`,
                                         `user`,
                                         `password`,
                                         `auto_increment`,
                                         `buffered`,
                                         `charset`,
                                         `collate`,
                                         `limit_max`,
                                         `mode`,
                                         `ssh_tunnel_required`,
                                         `ssh_tunnel_source_port`,
                                         `ssh_tunnel_hostname`,
                                         `usleep`,
                                         `pdo_attributes`,
                                         `timezone`,
                                         `statistics`,
                                         `log`
    
                                  FROM   `sql_configurations`
    
                                  WHERE  `seo_name` = :seo_name', [':seo_name' => $seo_name]);
    }


    /**
     * Apply configuration template over the specified configuration array
     *
     * @param array $configuration
     * @return array
     */
    public function applyConfigurationTemplate(array $configuration): array
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
                Log::warning(static::getLogPrefix() . tr('WARNING: ":driver" DRIVER MAY WORK BUT IS NOT SUPPORTED', [
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
    public function connect(bool $use_database = true): static
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

                    Log::success(static::getLogPrefix() . tr('Connected to instance ":connector" with PDO connect string ":string"', [
                        ':connector' => $this->connector,
                        ':string'    => $connect_string
                    ]), 3);

                    break;

                } catch (Throwable $e) {
                    if (!$this->configuration['hostname']) {
                        throw new SqlInvalidConfigurationException(static::getLogPrefix() . tr('Failed to connect to database connector ":connector" with connection string ":string" and user ":user", the database configuration is invalid', [
                                ':connector' => $this->connector,
                                ':string'    => isset_get($connect_string),
                                ':user'      => $this->configuration['username']
                            ]));
                    }

                    switch ($e->getCode()) {
                        case 1045:
                            // Access  denied!
                            throw SqlAccessDeniedException::new(static::getLogPrefix() . tr('Failed to connect to database connector ":connector" with connection string ":string" and user ":user", access was denied by the database server', [
                                ':connector' => $this->connector,
                                ':string'    => isset_get($connect_string),
                                ':user'      => $this->configuration['username']
                            ]))->makeWarning();

                        case 1049:
                            // Database doesn't exist!
                            throw SqlDatabaseDoesNotExistException::new(static::getLogPrefix() . tr('Failed to connect to database connector ":connector" with connection string ":string" and user ":user" because the database ":database" does not exist', [
                                ':connector' => $this->connector,
                                ':string'    => isset_get($connect_string),
                                ':database'  => $this->configuration['database'],
                                ':user'      => $this->configuration['username']
                            ]))->makeWarning();

                        case 2002:
                            // Database service not available, connection refused!
                            throw SqlServerNotAvailableException::new(static::getLogPrefix() . tr('Failed to connect to database connector ":connector" with connection string ":string" and user ":user" because the connection was refused. The database server may be down, or the configuration may be incorrect', [
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

                    Log::error(static::getLogPrefix() . tr('Failed to connect to instance ":connector" with PDO connect string ":string", error follows below', [
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
                    Log::warning(static::getLogPrefix() . tr('Failed to set timezone ":timezone" for database connector ":connector" with error ":e"', [
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
                    throw new SqlNoTimezonesException(tr('Failed to set timezone ":timezone", MySQL has not yet loaded any timezones', [
                        ':timezone' => $this->configuration['timezones_name']
                    ]), $e);
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

                        Log::warning(static::getLogPrefix() . tr('Connector ":connector" requires SSH tunnel to server ":server", but that server does not allow TCP fowarding. Server allows SSH server configuration modification, attempting to resolve issue', [':server' => $this->configuration['ssh_tunnel']['domain']]));

                        // Now enable TCP forwarding on the server, and retry connection
                        linux_set_ssh_tcp_forwarding($restrictions, true);
                        Log::warning(static::getLogPrefix() . tr('Enabled TCP fowarding for server ":server", trying to reconnect to MySQL database', [':server' => $this->configuration['ssh_tunnel']['domain']]));

                        if ($this->configuration['ssh_tunnel']['pid']) {
                            Log::warning(static::getLogPrefix() . tr('Closing previously opened SSH tunnel to server ":server"', [':server' => $this->configuration['ssh_tunnel']['domain']]));
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
     * Return a sequential array that can be used in $this->in
     *
     * @param array|string $source
     * @param string $column
     * @param bool $filter_null
     * @param bool $null_string
     * @param int $start
     * @return array
     */
    public static function in(array|string $source, string $column = ':value', bool $filter_null = false, bool $null_string = false, int $start = 0): array
    {
        if (empty($source)) {
            throw new OutOfBoundsException(tr('Specified source is empty'));
        }

        $column = Strings::startsWith($column, ':');
        $source = Arrays::force($source);

        return Arrays::sequentialKeys($source, $column, $filter_null, $null_string, $start);
    }


    /**
     * Helper for building $this->in key value pairs
     *
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param array $in
     * @param string|int|null $column_starts_with
     * @return string a comma delimited string of columns
     */
    public static function inColumns(array $in, string|int|null $column_starts_with = null): string
    {
        if ($column_starts_with) {
            // Only return those columns that start with this string
            foreach ($in as $key => $column) {
                if (!Strings::startsWith($key, $column_starts_with)) {
                    unset($in[$key]);
                }
            }
        }

        return implode(', ', array_keys($in));
    }


    /**
     * Check if this query is a write query and if the system allows writes
     *
     * @param string|PDOStatement $query
     * @return void
     */
    protected function checkWriteAllowed(string|PDOStatement $query): void
    {
        $query = trim($query);
        $query = substr(trim($query), 0, 10);
        $query = strtolower($query);

        if (str_starts_with($query, 'insert') or str_starts_with($query, 'update')) {
            // This is a write query, check if we're not in readonly mode
            Core::checkReadonly('write query');
        }
    }


    /**
     * Returns the log prefix
     *
     * @return string
     */
    protected function getLogPrefix(): string
    {
        return '(' . $this->uniqueid . '-' . $this->getDatabase() . '-' . $this->counter . ') ';
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
                    Log::error(static::getLogPrefix() . $message);
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
//                            ':query' => static::buildQueryString($query, $execute, true)
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
//                            ':query'  => static::buildQueryString($query, $execute, true),
//                            ':column' => $column
//                        ]), $e)->addData([':column' => $column]);
//
//                    case 1064:
//                        // Syntax error or access violation
//                        if (str_contains(strtoupper($query), 'DELIMITER')) {
//                            throw new SqlException(tr('Query ":query" contains the "DELIMITER" keyword. This keyword ONLY works in the MySQL console, and can NOT be used over MySQL drivers in PHP. Please remove this keword from the query', [
//                                ':query' => static::buildQueryString($query, $execute, true)
//                            ]), $e);
//                        }
//
//                        throw SqlException::new(tr('Query ":query" has a syntax error: ":error"', [
//                            ':query' => static::buildQueryString($query, $execute),
//                            ':error' => Strings::from($error[2], 'syntax; ')
//                        ], false), $e)->addData(['query' => $query, 'execute' => $execute]);
//
//                    case 1072:
//                        // Adding index error, index probably does not exist
//                        throw new SqlException(tr('Query ":query" failed with error 1072 with the message ":message"', [
//                            ':query'   => static::buildQueryString($query, $execute, true),
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
//                                ':query' => static::buildQueryString($query, $execute, true)
//                            ]), $e);
//                        }
//
//                        throw new SqlException(tr('Query ":query" failed with error 1005 with the message "Foreign key error on :message"', [
//                            ':query'   => static::buildQueryString($query, $execute, true),
//                            ':message' => $fk
//                        ]), $e);
//
//                    case 1146:
//                        // Base table or view not found
//                        throw new SqlException(tr('Query ":query" refers to a base table or view that does not exist: :message', [
//                            ':query'   => static::buildQueryString($query, $execute, true),
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
//                query           : "' . Strings::Log(static::buildQueryString($query, $execute, true)) . '"
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
//            ':query'    => static::buildQueryString($query, $execute),
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
}
