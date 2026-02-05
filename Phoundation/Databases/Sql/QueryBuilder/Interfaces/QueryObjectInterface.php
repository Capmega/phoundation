<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql\QueryBuilder\Interfaces;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;

interface QueryObjectInterface
{
    /**
     * Make this a SELECT query by adding the select clause here
     *
     * @param string     $select
     * @param array|null $execute
     *
     * @return static
     */
    public function addSelect(string $select, ?array $execute = null): static;

    /**
     * Make this a DELETE query by adding the select clause here
     *
     * @param bool $delete
     *
     * @return static
     */
    public function setDelete(bool $delete): static;

    /**
     * Make this a UPDATE query by adding the select clause here
     *
     * @param string     $update
     * @param array|null $execute
     *
     * @return static
     */
    public function addUpdate(string $update, ?array $execute = null): static;

    /**
     * Add the FROM part of the query
     *
     * @param string     $from
     * @param array|null $execute
     *
     * @return static
     */
    public function addFrom(string $from, ?array $execute = null): static;

    /**
     * Add a JOIN part of the query
     *
     * @param string     $join
     * @param array|null $execute
     *
     * @return static
     */
    public function addJoin(string $join, ?array $execute = null): static;

    /**
     * Add a WHERE part of the query
     *
     * @param string     $where
     * @param array|null $execute
     *
     * @return static
     */
    public function addWhere(string $where, ?array $execute = null): static;

    /**
     * Add a GROUP BY part of the query
     *
     * @param string     $group_by
     * @param array|null $execute
     *
     * @return static
     */
    public function addGroupBy(string $group_by, ?array $execute = null): static;

    /**
     * Add a HAVING part of the query
     *
     * @param string     $having
     * @param array|null $execute
     *
     * @return static
     */
    public function addHaving(string $having, ?array $execute = null): static;

    /**
     * Sets the ORDER BY part of the query
     *
     * @param string|null $order_by
     * @param array|null  $execute
     *
     * @return static
     */
    public function setOrderBys(?string $order_by, ?array $execute = null): static;

    /**
     * Add a ORDER BY part of the query
     *
     * @param string     $order_by
     * @param array|null $execute
     *
     * @return static
     */
    public function addOrderBy(string $order_by, ?array $execute = null): static;

    /**
     * Add a JOIN part of the query
     *
     * @param string|float|int|null $value
     * @param string                $column
     *
     * @return static
     */
    public function addExecute(string|float|int|null $value, string $column): static;

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
     * Add the specified execute array to the internal execute array
     *
     * @param array|null $execute
     *
     * @return static
     */
    public function addExecuteArray(?array $execute): static;

    /**
     * Add the specified predefined column
     *
     * @param string   $name
     * @param callable $callback
     *
     * @return static
     */
    public function addPredefine(string $name, callable $callback): static;

    /**
     * Make this a SELECT query by adding the select clause here
     *
     * @param string     $select
     * @param array|null $execute
     *
     * @return static
     */
    public function setSelect(string $select, ?array $execute = null): static;

    /**
     * Sets the "FROM" part of the query
     *
     * @param string|null $from
     * @param array|null  $execute
     *
     * @return static
     */
    public function setFrom(?string $from, ?array $execute = null): static;

    /**
     * Sets the WHERE part of the query
     *
     * @param string|null $where
     * @param array|null  $execute
     *
     * @return static
     */
    public function setWhere(?string $where, ?array $execute = null): static;

    /**
     * Returns the WHERE parts of the query
     *
     * @return array
     */
    public function getWheres(): array;

    /**
     * Resets all query variables
     *
     * @return static
     */
    public function reset(): static;

    /**
     * Updates the source data from this QueryObject with the specified data
     *
     * @param array $source
     *
     * @return static
     */
    public function addSource(array $source): static;

    /**
     * Updates the source data from this QueryObject with the specified data
     *
     * @param array $source
     *
     * @return static
     */
    public function setSource(array $source): static;

    /**
     * Returns the source of this object
     *
     * @note: This object doesn't work with "source" data as such, so it will be constructed upon request
     *
     * @return array
     */
    public function getSource(): array;

    /**
     * Returns the first FROM table
     *
     * @return string|null
     */
    public function getFrom(): ?string;

    /**
     * Returns the WHERE parts of the query
     *
     * @return array
     */
    public function getFroms(): array;

    /**
     * Sets bound execution variables
     *
     * @param array $bound_variables
     *
     * @return static
     */
    public function setBoundVariables(array $bound_variables): static;

    /**
     * Returns the WHERE parts of the query
     *
     * @return array
     */
    public function getSelect(): array;

    /**
     * Returns the JOINS parts of the query
     *
     * @return array
     */
    public function getJoins(): array;

    /**
     * Clears the "WHERE" section
     *
     * @return static
     */
    public function clearWhere(): static;

    /**
     * Returns the GROUP BY parts of the query
     *
     * @return array
     */
    public function getGroupBys(): array;

    /**
     * Returns the HAVING parts of the query
     *
     * @return array
     */
    public function getHavings(): array;

    /**
     * Returns the ORDER BY parts of the query
     *
     * @return array
     */
    public function getOrderBys(): array;

    /**
     * Adds a WHERE = or WHERE IN depending on parameter type
     *
     * @param string                $column
     * @param array|string|int|null $value
     *
     * @return static
     */
    public function addWhereIn(string $column, array|string|int|null $value): static;

    /**
     * Returns all predefines for this query builder
     *
     * @return array
     */
    public function getPredefines(): array;

    /**
     * Returns the bound query variables
     *
     * @return array
     */
    public function getBoundVariables(): array;
}
