<?php

namespace Phoundation\Databases\Sql\Interfaces;


use PDOStatement;

/**
 * QueryBuilder class
 *
 * This class helps building queries with multiple variables
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
interface QueryBuilderInterface
{
    /**
     * Make this a SELECT query by adding the select clause here
     *
     * @param string $select
     * @param array|null $execute
     * @return static
     */
    public function addSelect(string $select, ?array $execute = null): static;

    /**
     * Make this a DELETE query by adding the select clause here
     *
     * @param string $delete
     * @param array|null $execute
     * @return static
     */
    public function addDelete(string $delete, ?array $execute = null): static;

    /**
     * Make this a UPDATE query by adding the select clause here
     *
     * @param string $update
     * @param array|null $execute
     * @return static
     */
    public function addUpdate(string $update, ?array $execute = null): static;

    /**
     * Add the FROM part of the query
     *
     * @param string $from
     * @param array|null $execute
     * @return static
     */
    public function addFrom(string $from, ?array $execute = null): static;

    /**
     * Add a JOIN part of the query
     *
     * @param string $join
     * @param array|null $execute
     * @return static
     */
    public function addJoin(string $join, ?array $execute = null): static;

    /**
     * Add a WHERE part of the query
     *
     * @param string $where
     * @param array|null $execute
     * @return static
     */
    public function addWhere(string $where, ?array $execute = null): static;

    /**
     * Add a GROUP BY part of the query
     *
     * @param string $group_by
     * @param array|null $execute
     * @return static
     */
    public function addGroupBy(string $group_by, ?array $execute = null): static;

    /**
     * Add a HAVING part of the query
     *
     * @param string $having
     * @param array|null $execute
     * @return static
     */
    public function addHaving(string $having, ?array $execute = null): static;

    /**
     * Add a ORDER BY part of the query
     *
     * @param string $order_by
     * @param array|null $execute
     * @return static
     */
    public function addOrderBy(string $order_by, ?array $execute = null): static;

    /**
     * Add a JOIN part of the query
     *
     * @param string $column
     * @param string|float|int|null $value
     * @return static
     */
    public function addExecute(string $column, string|float|int|null $value): static;

    /**
     * Add a ORDER BY part of the query
     *
     * @param int $count
     * @param int $offset
     * @return static
     */
    public function setLimit(int $count, int $offset = 0): static;

    /**
     * Returns a column comparison and adds the bound variable to the execute list
     *
     * @param string $column
     * @param array|string|int|null $value
     * @return string
     */
    public function compareQuery(string $column, array|string|int|null $value): string;

    /**
     * Returns the complete query that can be executed
     *
     * @param bool $debug
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
     * @return PDOStatement
     */
    public function execute(bool $debug = false): PDOStatement;

    /**
     * Executes the query and returns the single result
     *
     * @param bool $debug
     * @return array|null
     */
    public function get(bool $debug = false): ?array;

    /**
     * Executes the query and returns the single column from the single result
     *
     * @param bool $debug
     * @return string|float|int|bool|null
     */
    public function getColumn(bool $debug = false): string|float|int|bool|null;

    /**
     * Executes the query and returns the list of results
     *
     * @param bool $debug
     * @return array
     */
    public function list(bool $debug = false): array;

    /**
     * Add the specified execute array to the internal execute array
     *
     * @param array|null $execute
     * @return static
     */
    public function addExecuteArray(?array $execute): static;
}
