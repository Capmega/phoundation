<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql\Interfaces;

use PDO;
use PDOStatement;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Databases\Interfaces\DatabaseInterface;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Schema\Interfaces\SchemaInterface;
use Phoundation\Databases\Sql\Schema\Schema;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;

interface SqlInterface extends DatabaseInterface
{
    /**
     * Returns the configuration for this SQL object
     *
     * @return array
     */
    public function getConfiguration(): array;


    /**
     * Returns if query printing is enabled for this instance or not
     *
     * @return bool
     */
    public function getDebug(): bool;


    /**
     * Sets if query printing is enabled for this instance or not
     *
     * @param bool $debug
     *
     * @return static
     */
    public function setDebug(bool $debug): static;


    /**
     * Returns if query statistics are enabled for this instance or not
     *
     * @return bool
     */
    public function getStatistics(): bool;


    /**
     * Sets  if query statistics are enabled for this instance or not
     *
     * @param bool $statistics
     *
     * @return static
     */
    public function setStatistics(bool $statistics): static;


    /**
     * Returns the name of the database that currently is in use by this database object
     *
     * @return string|null
     */
    public function getDatabase(): ?string;


    /**
     * Sets the name of the database that currently is in use by this database object
     *
     * @param string|null $database
     * @param bool        $use
     *
     * @return Sql
     */
    public function setDatabase(?string $database, bool $use = false): static;


    /**
     * Returns the name of this SQL instance
     *
     * @return string
     */
    public function getConnector(): string;


    /**
     * Returns an SQL schema object for this instance
     *
     * @param bool $use_database
     *
     * @return SchemaInterface
     */
    public function getSchemaObject(bool $use_database = true): SchemaInterface;


    /**
     * Clears schema cache and returns a new SQL schema object for this instance
     *
     * @param bool $use_database
     *
     * @return SchemaInterface
     */
    public function resetSchema(bool $use_database = true): SchemaInterface;


    /**
     * Use the specified database
     *
     * @param string|null $database The database to use. If none was specified, the configured system database will be
     *                              used
     *
     * @return static
     * @throws SqlException
     */
    public function use(?string $database = null): static;


    /**
     * Executes specified query and returns a PDOStatement object
     *
     * @param PDOStatement|SqlQueryInterface|string $query
     * @param array|null                            $execute
     *
     * @return PDOStatement
     * @throws SqlException
     */
    public function query(PDOStatement|SqlQueryInterface|string $query, ?array $execute = null): PDOStatement;


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
     * @return int
     */
    public function insert(string $table, array $data, array|string|null $update = null): int;


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
     * @return int The number of rows that were updated
     */
    public function update(string $table, array $set, array|null $where = null): int;


    /**
     * Delete the specified table entry
     *
     * This is a simplified delete method to speed up writing basic insert queries
     *
     * @param string $table
     * @param array  $execute
     *
     * @return int
     */
    public function delete(string $table, array $execute): int;


    /**
     * Truncates the specified table
     *
     * @param string $table
     *
     * @return void
     */
    public function truncate(string $table): void;


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
     * @return int
     */
    public function erase(string $table, array $where, string $separator = 'AND'): int;


    /**
     * Prepare specified query
     *
     * @param string $query
     *
     * @return PDOStatement
     */
    public function prepare(string $query): PDOStatement;


    /**
     * Fetch data with default PDO::FETCH_ASSOC instead of PDO::FETCH_BOTH
     *
     * @param PDOStatement $resource
     * @param int          $fetch_style
     *
     * @return array|null
     */
    public function fetch(PDOStatement $resource, int $fetch_style = PDO::FETCH_ASSOC): ?array;


    /**
     * Execute query and return only the first row
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     * @param bool                $meta_enabled
     *
     * @return array|null
     */
    public function get(string|PDOStatement $query, array $execute = null, bool $meta_enabled = true): ?array;


    /**
     * Get the value of a single column from a single row for the specified query
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     * @param string|null         $column
     *
     * @return string|float|int|bool|null
     */
    public function getColumn(string|PDOStatement $query, array $execute = null, ?string $column = null): string|float|int|bool|null;


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
    public function getNumeric(string|PDOStatement $query, array $execute = null, ?string $column = null): float|int|null;


    /**
     * Returns an integer variable from the SQL database
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     * @param string|null         $column
     *
     * @return int|null
     */
    public function getInteger(string|PDOStatement $query, array $execute = null, ?string $column = null): int|null;


    /**
     * Returns a float variable from the SQL database
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     * @param string|null         $column
     *
     * @return float|null
     */
    public function getFloat(string|PDOStatement $query, array $execute = null, ?string $column = null): float|null;


    /**
     * Returns a float variable from the SQL database
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     * @param string|null         $column
     *
     * @return bool|null
     */
    public function getBoolean(string|PDOStatement $query, array $execute = null, ?string $column = null): bool|null;


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
    public function listScalar(string|PDOStatement $query, ?array $execute = null): array;


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
    public function listArray(string|PDOStatement $query, ?array $execute = null): array;


    /**
     * Executes the query for two columns and will return the results as a key => static value array
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     *
     * @return array
     */
    public function listKeyValue(string|PDOStatement $query, ?array $execute = null): array;


    /**
     * Executes the query for two or more columns and will return the results as a key => values-in-array array
     *
     * The key will be the first selected column but will be included in the value array
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     *
     * @return array
     */
    public function listKeyValues(string|PDOStatement $query, ?array $execute = null, ?string $column = null): array;


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
    public function list(string|PDOStatement $query, ?array $execute = null): array;


    /**
     * Close the connection for the specified connector
     *
     * @return void
     */
    public function close(): void;


    /**
     * Import data from specified file
     *
     * @param string                                    $file
     * @param FsRestrictionsInterface|array|string|null $restrictions
     *
     * @return void
     */
    public function import(string $file, FsRestrictionsInterface|array|string|null $restrictions): void;


    /**
     * Get the current last insert id for this SQL database connector
     *
     * @return ?int
     */
    public function getInsertId(): ?int;


    /**
     * Enable / Disable all query logging on mysql server
     *
     * @param bool $enable
     *
     * @return void
     */
    public function enableLog(bool $enable): void;


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
    public function count(string $table, string $where = '', ?array $execute = null, string $column = '`id`'): int;


    /**
     * Returns what database currently is selected
     *
     * @return string|null
     */
    public function getCurrentDatabase(): ?string;


    /**
     * Returns information about the specified database
     *
     * @param string $database
     *
     * @return array
     */
    public function getDatabaseInformation(string $database): array;


    /**
     * Ensure that the specified limit is below or equal to the maximum configured limit
     *
     * @param int $limit
     *
     * @return int
     */
    public function getValidLimit(int $limit): int;


    /**
     * Reads, validates structure and returns the configuration for the specified instance
     *
     * @param string $connector
     *
     * @return array
     */
    public function readConfiguration(string $connector): array;


    /**
     * Connects to this database and executes a test query
     *
     * @return static
     */
    public function test(): static;


    /**
     * Simple "Does a row with this value exist in that table" method
     *
     * @param string           $table
     * @param string|int|float $column
     * @param string|int|null  $value
     * @param int|null         $id
     * @param string           $id_column
     *
     * @return bool
     */
    public function exists(string $table, string|int|float $column, string|int|null $value, ?int $id = null, string $id_column = 'id'): bool;
}
