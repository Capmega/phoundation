<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Interfaces;

use Iterator;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Web\Http\Html\Components\DataTable;
use Phoundation\Web\Http\Html\Components\Table;
use ReturnTypeWillChange;


/**
 * Class DataList
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
interface InterfaceDataList extends Iterator
{
    /**
     * Return the object contents in JSON string format
     *
     * @return string
     */
    function __toString(): string;

    /**
     * Return the object contents in array format
     *
     * @return array
     */
    function __toArray(): array;

    /**
     * Returns new DataList object with an optional parent
     *
     * @param InterfaceDataEntry|null $parent
     * @param string|null $id_column
     * @return static
     */
    public static function new(?InterfaceDataEntry $parent = null, ?string $id_column = null): static;

    /**
     * Returns the amount of items in this list
     *
     * @return int
     */
    function getCount(): int;

    /**
     * Returns if the specified data entry exists in the data list
     *
     * @param InterfaceDataEntry|int $entry
     * @return bool
     */
    function exists(InterfaceDataEntry|int $entry): bool;

    /**
     * Returns a list of items that are specified, but not available in this DataList
     *
     * @param InterfaceDataList|array|string $list
     * @param string|null $always_match
     * @return array
     */
    function missesKeys(InterfaceDataList|array|string $list, string $always_match = null): array;

    /**
     * Returns if all (or optionally any) of the specified entries are in this list
     *
     * @param InterfaceDataList|array|string $list
     * @param bool $all
     * @param string|null $always_match
     * @return bool
     */
    function containsKey(InterfaceDataList|array|string $list, bool $all = true, string $always_match = null): bool;

    /**
     * Returns if all (or optionally any) of the specified entries are in this list
     *
     * @param InterfaceDataList|array|string $list
     * @param bool $all
     * @param string|null $always_match
     * @return bool
     */
    function containsValue(InterfaceDataList|array|string $list, bool $all = true, string $always_match = null): bool;

    /**
     * Returns the entire internal list
     *
     * @return array
     */
    function list(): array;

    /**
     * Returns the internal list filtered by the specified keyword
     *
     * @param string|null $keyword
     * @return array
     */
    function filteredList(?string $keyword): array;

    /**
     * Returns the list of internal ID's
     *
     * @return array
     */
    function idList(): array;

    /**
     * Returns all configured filters to apply when loading the data list
     *
     * @return array
     */
    function getFilters(): array;

    /**
     * Set the query for this object when shown as HTML table
     *
     * @param string $query
     * @param array|null $execute
     * @return static
     */
    function setHtmlQuery(string $query, ?array $execute = null): static;

    /**
     * Returns the query for this object when shown as HTML table
     *
     * @return string
     */
    function getHtmlQuery(): string;

    /**
     * Returns the table name that is the source for this DataList object
     *
     * @return string
     */
    function getTable(): string;

    /**
     * Add a filter to apply when loading the data list
     *
     * @param string $key
     * @return static
     */
    function removeFilter(string $key): static;

    /**
     * Returns the schema Table object for the table that is the source for this DataList object
     *
     * @return \Phoundation\Databases\Sql\Schema\Table
     */
    function getTableSchema(): \Phoundation\Databases\Sql\Schema\Table;

    /**
     * Clears multiple filters to apply when loading the data list
     *
     * @return static
     */
    function clearFilters(): static;

    /**
     * Set multiple filters to apply when loading the data list
     *
     * @note This will clear all already defined filters
     * @param array $filters
     * @return static
     */
    function setFilters(array $filters): static;

    /**
     * Add multiple filters to apply when loading the data list
     *
     * @param array $filters
     * @return static
     */
    function addFilters(array $filters): static;

    /**
     * Add a filter to apply when loading the data list
     *
     * @param string $key
     * @param array|string|int|null $value
     * @return static
     */
    function addFilter(string $key, array|string|int|null $value): static;

    /**
     * Returns the item with the specified identifier
     *
     * @param int $identifier
     * @return InterfaceDataEntry|null
     */
    #[ReturnTypeWillChange] function get(int $identifier): ?InterfaceDataEntry;

    /**
     * Returns the current item
     *
     * @return InterfaceDataEntry|null
     */
    #[ReturnTypeWillChange] function current(): ?InterfaceDataEntry;

    /**
     * Jumps to the next element
     *
     * @return static
     */
    #[ReturnTypeWillChange] function next(): static;

    /**
     * Jumps to the next element
     *
     * @return static
     */
    #[ReturnTypeWillChange] function previous(): static;

    /**
     * Returns the current iterator position
     *
     * @return int
     */
    function key(): int;

    /**
     * Returns if the current element exists or not
     *
     * @return bool
     */
    function valid(): bool;

    /**
     * Rewinds the internal pointer to 0
     *
     * @return static
     */
    #[ReturnTypeWillChange] function rewind(): static;

    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @return Table
     */
    function getHtmlTable(): Table;

    /**
     * Creates and returns a fancy HTML data table for the data in this list
     *
     * @return DataTable
     */
    function getHtmlDataTable(): DataTable;

    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|null $columns
     * @param array $filters
     * @param string|null $id_column
     * @return void
     */
    function CliDisplayTable(?array $columns = null, array $filters = [], ?string $id_column = 'id'): void;

    /**
     * Set the specified status for the specified entries
     *
     * @param string|null $status
     * @param array $entries
     * @param string|null $comments
     * @return int
     */
    function setStatus(?string $status, array $entries, ?string $comments = null): int;

    /**
     * Delete the specified entries
     *
     * @param array $entries
     * @param string|null $comments
     * @return int
     */
    function delete(array $entries, ?string $comments = null): int;

    /**
     * Undelete the specified entries
     *
     * @param array $entries
     * @param string|null $comments
     * @return int
     */
    function undelete(array $entries, ?string $comments = null): int;

    /**
     * Returns an array with all id's for the specified entry identifiers
     *
     * @param array $identifiers
     * @return array
     */
    function listIds(array $identifiers): array;

    /**
     * Save the data list elements to database
     *
     * @return static
     */
    function save(): static;
}