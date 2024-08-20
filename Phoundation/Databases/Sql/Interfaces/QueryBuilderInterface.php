<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql\Interfaces;

use PDOStatement;

interface QueryBuilderInterface
{
    /**
     * Make this a SELECT query by adding the select clause here
     *
     * @param string|null $select
     * @param array|null  $execute
     *
     * @return static
     */
    public function addSelect(?string $select, ?array $execute = null): static;


    /**
     * Make this a DELETE query by adding the select clause here
     *
     * @param string|null $delete
     * @param array|null  $execute
     *
     * @return static
     */
    public function addDelete(?string $delete, ?array $execute = null): static;


    /**
     * Make this a UPDATE query by adding the select clause here
     *
     * @param string|null $update
     * @param array|null  $execute
     *
     * @return static
     */
    public function addUpdate(?string $update, ?array $execute = null): static;


    /**
     * Add the FROM part of the query
     *
     * @param string|null $from
     * @param array|null  $execute
     *
     * @return static
     */
    public function addFrom(?string $from, ?array $execute = null): static;


    /**
     * Add a JOIN part of the query
     *
     * @param string|null $join
     * @param array|null  $execute
     *
     * @return static
     */
    public function addJoin(?string $join, ?array $execute = null): static;


    /**
     * Add a WHERE part of the query
     *
     * @param string|null $where
     * @param array|null  $execute
     *
     * @return static
     */
    public function addWhere(?string $where, ?array $execute = null): static;


    /**
     * Add a GROUP BY part of the query
     *
     * @param string|null $group_by
     * @param array|null  $execute
     *
     * @return static
     */
    public function addGroupBy(?string $group_by, ?array $execute = null): static;


    /**
     * Add a HAVING part of the query
     *
     * @param string|null $having
     * @param array|null  $execute
     *
     * @return static
     */
    public function addHaving(?string $having, ?array $execute = null): static;


    /**
     * Add a ORDER BY part of the query
     *
     * @param string|null $order_by
     * @param array|null  $execute
     *
     * @return static
     */
    public function addOrderBy(?string $order_by, ?array $execute = null): static;


    /**
     * Add a JOIN part of the query
     *
     * @param string                $column
     * @param string|float|int|null $value
     *
     * @return static
     */
    public function addExecute(string $column, string|float|int|null $value): static;


    /**
     * Add a ORDER BY part of the query
     *
     * @param int $count
     * @param int $offset
     *
     * @return static
     */
    public function setLimit(int $count, int $offset = 0): static;


    /**
     * Returns a column comparison and adds the bound variable to the execute list
     *
     * @param string                $column
     * @param array|string|int|null $value
     *
     * @return string
     */
    public function compareQuery(string $column, array|string|int|null $value): string;


    /**
     * Returns the complete query that can be executed
     *
     * @param bool $debug
     *
     * @return string
     */
    public function getQuery(bool $debug = false): string;


    /**
     * Returns the bound variables execute array
     *
     * @return array|null
     */
    public function getExecute(): ?array;


    /**
     * Executes the query and returns a PDO statement
     *
     * @param bool $debug
     *
     * @return PDOStatement
     */
    public function execute(bool $debug = false): PDOStatement;


    /**
     * Executes the query and returns the single result
     *
     * @param bool $debug
     *
     * @return array|null
     */
    public function get(bool $debug = false): ?array;


    /**
     * Executes the query and returns the single column from the single result
     *
     * @param string|null $column
     * @param bool        $debug
     *
     * @return string|float|int|bool|null
     */
    public function getColumn(?string $column = null, bool $debug = false): string|float|int|bool|null;


    /**
     * Executes the query and returns the list of results
     *
     * @param bool $debug
     *
     * @return array
     */
    public function list(bool $debug = false): array;


    /**
     * Returns the name of the database connector where this DataEntry is stored
     *
     * @param string $database_connector
     * return static
     */
    public function setDatabaseConnectorName(string $database_connector): static;


    /**
     * Returns if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @return bool
     */
    public function getMetaEnabled(): bool;


    /**
     * Sets if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @param bool $meta_enabled
     * return static
     */
    public function setMetaEnabled(bool $meta_enabled): static;

    /**
     * Returns the debug value
     *
     * @return bool
     */
    public function getDebug(): bool;

    /**
     * Sets the debug value
     *
     * @param bool $debug
     *
     * @return static
     */
    public function setDebug(bool $debug): static;
}
