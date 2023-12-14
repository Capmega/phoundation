<?php

namespace Phoundation\Databases\Sql\Interfaces;

use Exception;
use PDO;
use PDOStatement;
use Phoundation\Databases\Interfaces\DatabaseInterface;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;
use Phoundation\Databases\Sql\Schema\Schema;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Throwable;


/**
 * Sql class
 *
 * This class is the main SQL database access class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
interface SqlInterface extends DatabaseInterface
{
    /**
     * Returns the configuration for this SQL object
     *
     * @return array
     */
    public function getConfiguration(): array;

    /**
     * Returns the name of the database that currently is in use by this database object
     *
     * @return string|null
     */
    public function getDatabase(): ?string;

    /**
     * Returns the name of this SQL instance
     *
     * @return string|null
     */
    public function getConnector(): ?string;

    /**
     * Returns an SQL schema object for this instance
     *
     * @param bool $use_database
     * @return Schema
     */
    public function schema(bool $use_database = true): Schema;

    /**
     * Clears schema cache and returns a new SQL schema object for this instance
     *
     * @param bool $use_database
     * @return Schema
     */
    public function resetSchema(bool $use_database = true): Schema;

    /**
     * Use the specified database
     *
     * @param string|null $database The database to use. If none was specified, the configured system database will be
     *                              used
     * @return void
     * @throws Throwable
     */
    public function use(?string $database = null): void;

    /**
     * Executes specified query and returns a PDOStatement object
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @return PDOStatement
     */
    public function query(string|PDOStatement $query, ?array $execute = null): PDOStatement;

    /**
     * Write the specified data row in the specified table
     *
     * This is a simplified insert / update method to speed up writing basic insert or update queries. If the
     * $update_row[id] contains a value, the method will try to update instead of insert
     *
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param string $table
     * @param array $insert_row
     * @param array $update_row
     * @param string|null $comments
     * @param string|null $diff
     * @return int
     * @throws Exception
     */
    public function dataEntryWrite(string $table, array $insert_row, array $update_row, ?string $comments, ?string $diff): int;

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
     * @return int
     * @throws Exception
     */
    public function dataEntryInsert(string $table, array $row, ?string $comments = null, ?string $diff = null): int;

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
     * @return int
     */
    public function dataEntryUpdate(string $table, array $row, string $action = 'update', ?string $comments = null, ?string $diff = null): int;

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
     * @return int
     */
    public function dataEntryDelete(string $table, array $row, ?string $comments = null): int;

    /**
     * Truncates the specified table
     *
     * @param string $table
     * @return void
     */
    public function truncate(string $table): void;

    /**
     * Update the status for the data row in the specified table to the specified status
     *
     * @param string|null $status
     * @param string $table
     * @param array $entry
     * @param string|null $comments
     * @return int
     */
    public function dataEntrySetStatus(?string $status, string $table, array $entry, ?string $comments = null): int;

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
    public function erase(string $table, array $where, string $separator = 'AND'): int;

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
    public function delete(string $table, string $where, array $execute): int;

    /**
     * Prepare specified query
     *
     * @param string $query
     * @return PDOStatement
     */
    public function prepare(string $query): PDOStatement;

    /**
     * Fetch data with default PDO::FETCH_ASSOC instead of PDO::FETCH_BOTH
     *
     * @param PDOStatement $resource
     * @param int $fetch_style
     * @return array|null
     * @throws Throwable
     */
    public function fetch(PDOStatement $resource, int $fetch_style = PDO::FETCH_ASSOC): ?array;

    /**
     * Execute query and return only the first row
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @return array|null
     * @throws SqlMultipleResultsException
     */
    public function get(string|PDOStatement $query, array $execute = null): ?array;

    /**
     * Get the value of a single column from a single row for the specified query
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param string|null $column
     * @return string|float|int|bool|null
     */
    public function getColumn(string|PDOStatement $query, array $execute = null, ?string $column = null): string|float|int|bool|null;

    /**
     * Returns a numeric variable from the SQL database
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param string|null $column
     * @return float|int|null
     * @throws OutOfBoundsException Thrown if the result is non numeric
     */
    public function getNumeric(string|PDOStatement $query, array $execute = null, ?string $column = null): float|int|null;

    /**
     * Returns an integer variable from the SQL database
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param string|null $column
     * @return int|null
     */
    public function getInteger(string|PDOStatement $query, array $execute = null, ?string $column = null): int|null;

    /**
     * Returns a float variable from the SQL database
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param string|null $column
     * @return float|null
     */
    public function getFloat(string|PDOStatement $query, array $execute = null, ?string $column = null): float|null;

    /**
     * Returns a float variable from the SQL database
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param string|null $column
     * @return bool|null
     */
    public function getBoolean(string|PDOStatement $query, array $execute = null, ?string $column = null): bool|null;

    /**
     * Execute query and return only the first row
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @return array
     * @throws Throwable
     */
    public function listScalar(string|PDOStatement $query, ?array $execute = null): array;

    /**
     * Execute query and return only the first row
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @return array
     * @throws Throwable
     */
    public function listArray(string|PDOStatement $query, ?array $execute = null): array;

    /**
     * Execute query and return only the first row
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @return array
     * @throws Throwable
     */
    public function listKeyValue(string|PDOStatement $query, ?array $execute = null): array;

    /**
     * Execute query and return only the first row
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @return array
     * @throws Throwable
     */
    public function listKeyValues(string|PDOStatement $query, ?array $execute = null): array;

    /**
     * Execute query and return only the first row
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @return array
     * @throws Throwable
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
     * @param string $file
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return void
     */
    public function import(string $file, RestrictionsInterface|array|string|null $restrictions): void;

    /**
     * Get the current last insert id for this SQL database instance
     *
     * @return ?int
     */
    public function insertId(): ?int;

    /**
     * Enable / Disable all query logging on mysql server
     *
     * @param bool $enable
     * @return void
     */
    public function enableLog(bool $enable): void;

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
    public function DataEntryExists(string $table, string $column, string|int|null $value, ?int $id = null): bool;

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
     * @return array
     */
    public function getDatabaseInformation(string $database): array;

    /**
     * Ensure that the specified limit is below or equal to the maximum configured limit
     *
     * @param int $limit
     * @return int
     */
    public function validLimit(int $limit): int;

    /**
     * Return a valid " LIMIT X, Y " string built from the specified parameters
     *
     * @param int|null $limit
     * @param int|null $page
     * @return string The SQL " LIMIT X, Y " string
     */
    public function getLimit(?int $limit = null, ?int $page = null): string;

    /**
     * Show the specified SQL query in a debug
     *
     * @param string|PDOStatement $query
     * @param ?array $execute
     * @param bool $return_only
     * @return mixed
     * @throws SqlException
     */
    public function show(string|PDOStatement $query, ?array $execute = null, bool $return_only = false): mixed;

    /**
     * Reads, validates structure and returns the configuration for the specified instance
     *
     * @param string $connector
     * @return array
     */
    public function readConfiguration(string $connector): array;

    /**
     * Apply configuration template over the specified configuration array
     *
     * @param array $configuration
     * @return array
     */
    public function applyConfigurationTemplate(array $configuration): array;
}
